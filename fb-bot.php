﻿<?php

// passwords, keys, db-settings
require_once('settings.local.php');
// Create our twitter API object
require_once("twitteroauth.php");
include_once ('simple_html_dom.php');

// database, mysql, why not?
include('db.php');


$artikelen_res = mysql_query('select * from artikelen');
$i = 0;
while ($artikel = mysql_fetch_array($artikelen_res))
{
	$fql  = "SELECT url, normalized_url, share_count, like_count, comment_count, ";
	$fql .= "total_count, commentsbox_count, comments_fbid, click_count FROM ";
	$fql .= "link_stat WHERE url = '".$artikel['clean_url']."'";
echo 'Querying facebook for: '.$artikel['clean_url']."\n";
	$apifql="https://api.facebook.com/method/fql.query?format=json&query=".urlencode($fql);
	$json=file_get_contents($apifql);
	//print_r($json);
	$response = json_decode($json);

	// now find the record for this article
	$fb_res = mysql_query('select ID from facebook where art_id = '.$artikel['ID']);
	if(mysql_num_rows($fb_res) > 0)
	{
		mysql_query('update facebook set share_count = '.$response[0]->share_count.', comment_count = '.$response[0]->comment_count.', like_count = '.$response[0]->like_count.', total_count = '.$response[0]->total_count.', click_count = '.$response[0]->click_count.' where art_id = '.$artikel['ID']);
	}
	else
	{
		mysql_query('insert into facebook (art_id, share_count, comment_count, like_count, total_count, click_count)
								 values
								 ('.$artikel['ID'].', '.$response[0]->share_count.', '.$response[0]->comment_count.', '.$response[0]->like_count.', '.$response[0]->total_count.', '.$response[0]->click_count.')');
	}
}