<?php
/* Database credentials */
define('DB_SERVER', 'localhost:3306');
define('DB_USERNAME', 'pawan');
define('DB_PASSWORD', 'pass@123');
define('DB_DATABASE', 'php_project');

/* Attempt to connect to MySQL database */
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
 
// Check connection
if($mysqli === false){
    die("ERROR: Could not connect. " . $mysqli->connect_error);
}
?>