<?php
	// live-data.php
	// return JSON for live charts
require_once('settings.local.php');
require_once('functions.php');
include('db.php');


header('Content-type: application/json');

if(isset($_REQUEST['type']))
{
	$type = $_REQUEST['type'];
	switch($type)
	{
		case 'per_day':
			$data = tweets_per_day('JSON');
			break;
		case 'per_hour':
			$data = tweets_today('JSON');
			break;
		case 'per_minute':
			$data = tweets_per_minute('JSON');
			break;
		case 'per_article':
			$data = tweets_per_article('JSON');
			break;
		case 'day_stacked':
			$data = tweets_per_day_stacked('JSON');
			break;
		deafult:
			$data = array();
	}
	echo json_encode($data);
}