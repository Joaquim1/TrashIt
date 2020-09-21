<?php

$DBCONNECTION = "127.0.0.1";
$DBUSER = "root";
$DBPASS = "";
$DBNAME = "garbage";

try
{
	$db = new PDO('mysql:host=' . $DBCONNECTION . ';dbname=' . $DBNAME, $DBUSER, $DBPASS);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e)
{
    echo 'Connection failed: ' . $e->getMessage();
}