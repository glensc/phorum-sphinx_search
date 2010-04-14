CREATE TABLE `sph_counter` (
  `counter_id` int(11) unsigned NOT NULL default '0',
  `type` enum('author','message') NOT NULL default 'message',
  `max_doc_id` int(11) NOT NULL,
  PRIMARY KEY  (`counter_id`,`type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
