<?php
// These are the default settings for the Sphinx Search module.

if (! isset($GLOBALS["PHORUM"]["mod_sphinx_search"]))
    $GLOBALS["PHORUM"]["mod_sphinx_search"] = array();

if (! isset($GLOBALS["PHORUM"]["mod_sphinx_search"]["hostname"]))
    $GLOBALS["PHORUM"]["mod_sphinx_search"]["hostname"] = "127.0.0.1";

if (! isset($GLOBALS["PHORUM"]["mod_sphinx_search"]["port"]))
    $GLOBALS["PHORUM"]["mod_sphinx_search"]["port"] = 3312;

?>
