<?php

/*
 * Module for using sphinx as fulltext search-engine
 * sphinx can be downloaded and installed from
 * http://sphinxsearch.com/
 *
 * mod written by Thomas Seifert (thomas@phorum.org)
 * fixes/enhancements by Maurice Makaay (maurice@phorum.org)
 * update for Phorum 5.2 by Martijn van Maasakkers (martijn@vanmaasakkers.net)
 *
 * see README for version history
 *
 */

if (!defined("PHORUM")) return;

require_once("./mods/sphinx_search/defaults.php");

function sphinx_search_action($arrSearch)
{
	global $PHORUM;

    include './mods/sphinx_search/sphinxclient.php';
	
    // these are the index-names set in sphinx.conf - one for searching messages, the other for searching by authors only
    // both contain an additional index for the deltas - changes done after the last full reindex
    $index_name_msg = "phorum5_msg_d phorum5_msg";
    $index_name_author = "phorum5_author phorum5_author_delta";
    
    // excerpts_index is just one index as that function only accepts one, it used for determining charsets / mapping tables, nothing more
    $excerpts_index = "phorum5_msg";  
    
    $index = $index_name_msg;
    
	if($arrSearch['match_type'] == 'ALL') {
            $match_mode = SPH_MATCH_ALL;
    } elseif($arrSearch['match_type'] == 'ANY') {
            $match_mode = SPH_MATCH_ANY;
    } elseif($arrSearch['match_type'] == 'PHRASE') {
            $match_mode = SPH_MATCH_PHRASE;
    } elseif($arrSearch['match_type'] == 'AUTHOR') {
            $match_mode = SPH_MATCH_PHRASE;
            $index = $index_name_author;
    } else {
            // Return search control to Phorum in case the search type isn't handled by the module.
            return $arrSearch;
    }
    
    $sphinx = new SphinxClient($PHORUM["mod_sphinx_search"]["hostname"], $PHORUM["mod_sphinx_search"]["port"]);
    $sphinx->SetMatchMode($match_mode);
    
    // set the limits for paging
    $sphinx->SetLimits($arrSearch['offset'],$arrSearch['length']);
    
	// set the timeframe to search
    if($arrSearch['match_dates'] > 0) {
        $min_ts = time() - 86400 * $arrSearch['match_dates'];
        $max_ts = time();
        $sphinx->SetFilterRange("datestamp",$min_ts,$max_ts);

    }

    // add the forum(s) to search
    if($arrSearch['match_forum'] == 'THISONE') {
		$forumid_clean = (int)$PHORUM['forum_id'];
		$sphinx->SetFilter ( "forum_id", array($forumid_clean) );

    } else {

        // have to check what forums they can read first.
		// $allowed_forums=phorum_user_access_list(PHORUM_USER_ALLOW_READ);
//		$allowed_forums=phorum_user_access_list(PHORUM_USER_ALLOW_READ);
		$allowed_forums = phorum_api_user_check_access(PHORUM_USER_ALLOW_READ, PHORUM_ACCESS_LIST);
		
        // if they are not allowed to search any forums, return the emtpy $arr;
        if(empty($allowed_forums) || ($PHORUM['forum_id']>0 && !in_array($PHORUM['forum_id'], $allowed_forums)) ) return $arr;

		$sphinx->SetFilter ( "forum_id", $allowed_forums );
    }
    
    // set the sort-mode
    $sphinx->SetSortMode(SPH_SORT_ATTR_DESC,"datestamp");

    // do the actual query
    $results = $sphinx->Query($arrSearch['search'],$index);
    
	// if no messages were found, then return empty handed.
    if (! isset($results["matches"])) {
        $arrSearch['results']=array();
        $arrSearch['totals']=0;
        $arrSearch['continue']=0;
        $arrSearch['raw_body']=1;
        return $arrSearch;
    }
    
    
    $search_msg_ids = $results['matches'];
    
    // get the messages we found
    $found_messages = phorum_db_get_message(array_keys($search_msg_ids),'message_id',true);

    // sort them in reverse order of the message_id to automagically sort them by date desc this way
    krsort($found_messages);
    reset($found_messages);
    
//    print_r($found_messages);/
    
	
    // prepare the array for building highlighted excerpts
    $docs=array();
    foreach($found_messages as $id => $data) {
        // remove hidden text in the output - only added by the hidden_msg module
        $data['body']=preg_replace("/(\[hide=([\#a-z0-9]+?)\](.+?)\[\/hide\])/is", "", $data['body']);

        $docs[] = htmlspecialchars(phorum_strip_body($data['body']));
    }
    
    $words=implode(" ",array_keys($results['words']));

    $opts=array('chunk_separator'=>' [...] ');

    // build highlighted excerpts
    $highlighted = $sphinx->BuildExcerpts($docs,$excerpts_index,$words,$opts);

    print $sphinx->GetLastError();

    $cnt=0;
    foreach($found_messages as $id => $content) {
    // foreach($found_messages as $id => $content) {
        $found_messages[$id]['short_body'] = $highlighted[$cnt];
        $cnt++;
    }

    $arrSearch['results']=$found_messages;
    // we need the total results
    $arrSearch['totals']=$results['total_found'];
    
    if($arrSearch['totals'] > 1000) {
    	$arrSearch['totals'] = 1000;
    }
    
    // don't run the default search
    $arrSearch['continue']=0;
    // tell it to leave the body alone
    $arrSearch['raw_body']=1;
    
	return $arrSearch;
}


?>