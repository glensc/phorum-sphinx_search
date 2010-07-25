<?php

if (!defined("PHORUM_ADMIN")) return;

require_once 'defaults.php';

// save settings
if (count($_POST)) {
	$PHORUM['mod_sphinx_search'] = array(
		'hostname'  => $_POST['hostname'],
		'port'      => $_POST['port'],
	);

	if (!phorum_db_update_settings(array('mod_sphinx_search' => $PHORUM['mod_sphinx_search']))) {
		phorum_admin_error("Updating the settings in the database failed.");
	} else {
		phorum_admin_okmsg("Settings updated");
	}
}

?>
<div style="font-size: xx-large; font-weight: bold">Sphinx Search Module</div>
 This module uses the sphinx fulltext search engine to gather the results of the phorum-search.<br />
 On this page you can set the hostname and port of your sphinx search daemon.

<br style="clear:both" />
<?php

include_once PHORUM_INCLUDES_DIR.'/admin/PhorumInputForm.php';
$frm = new PhorumInputForm ("", "post", "Save");
$frm->hidden("module", "modsettings");
$frm->hidden("mod", "sphinx_search");

$frm->addbreak("Hostname and port");

$row = $frm->addrow("What is the hostname of the sphinx daemon? (e.g. 127.0.0.1)$warn", $frm->text_box("hostname", $PHORUM["mod_sphinx_search"]["hostname"], 30));
$row = $frm->addrow("What is the port of the sphinx daemon? (e.g. 9312)$warn", $frm->text_box("port", $PHORUM["mod_sphinx_search"]["port"], 30));
$frm->show();
