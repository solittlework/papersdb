<?php

/**
 * Project Constants
 *
 * Defines contants to connect to the PapersDB database.
 *
 * @package PapersDB
 */
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

ini_set("include_path", ini_get("include_path") . ":./pear:../pear");

$wgSitename = "PapersDB";
$wgServer = "www.cs.ualberta.ca";

define('SITE_NAME', 'papersdb');

/**
 * The name of the database and the path on the fileserver where documents are 
 * stored.
 */
if (strpos($_SERVER['PHP_SELF'], '~papersdb') === false) {
    define('FS_PATH', '/usr/host/loyola/htdocs/papersdb');
}
else {
    if (isset($_ENV['HOSTNAME']) && ($_ENV['HOSTNAME'] == 'levante'))
       define('FS_PATH', '/home/nelson/public_html/papersdb');
    else
        define('FS_PATH', '/usr/host/loyola/htdocs/papersdb');
}

define('FS_PATH_UPLOAD', FS_PATH . '/uploaded_files/');

$relative_files_path = "uploaded_files/";
$absolute_files_path = FS_PATH . $relative_files_path;

define('MAINTENANCE', 0);

// required for Database.php
define( 'DBO_DEBUG', 1 );
define( 'DBO_NOBUFFER', 2 );
define( 'DBO_IGNORE', 4 );
define( 'DBO_TRX', 8 );
define( 'DBO_DEFAULT', 16 );
define( 'DBO_PERSISTENT', 32 );

?>
