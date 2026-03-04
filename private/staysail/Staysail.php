<?php
/**
 * This file brings in the Staysail classes and tools.  It's meant to be
 * used in the StaysailModule and StaysailPublic child classes.
 * 
 * The file that requires Staysail should define some named constants:
 * define('STAYSAIL', $path_to_the_staysail_directory);
 * define('DOCROOT', $path_to_the_web_server_document_root);
 * 
 * @package Staysail
 */

require_once 'class.StaysailIO.php';
//require_once 'class.StaysailIO_PDO.php';
require_once 'class.Filter.php';
require_once 'class.StaysailWriter.php';
require_once 'class.StaysailForm.php';
require_once 'class.StaysailTable.php';
require_once 'class.StaysailEntity.php';
require_once 'class.StaysailPublic.php';

// These are optional, and can be used if the application uses a css grid layout
require_once 'class.StaysailLayout.php';
require_once 'class.StaysailContainer.php';

// This is optional, and can be used if the application uses reports
require_once 'class.StaysailReport.php';

define('STAYSAIL_DIAGNOSTIC', false);