<?php
$query = 'decorrespondent.nl/';

// passwords, keys, db-settings
require_once('settings.local.php');
// Create our twitter API object
require_once("twitteroauth.php");
include_once ('simple_html_dom.php');

// database, mysql, why not?
include('db.php');

$res = mysql_query ('select ID from artikelen');
while ($artikel = mysql_fetch_row($res) ) {
	$tweets = mysql_fetch_row(mysql_query('select count(*) from tweets where art_id = '.$artikel[0]));
	mysql_query('update artikelen set tweet_count = '.$tweets[0].' where ID = '.$artikel[0]);
}