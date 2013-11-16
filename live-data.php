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
			$data = tweets_per_day();
			break;
		case 'per_hour':
			$data = tweets_today('JSON');
			break;
		deafult:
			$data = array();
	}
	echo json_encode($data);
}