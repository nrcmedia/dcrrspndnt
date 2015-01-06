<?php
$vkdb = mysql_connect(DB_HOST, DB_USER, DB_PASSWD);
mysql_select_db(VKDB_NAME, $vkdb);

$db = mysql_connect(DB_HOST, DB_USER, DB_PASSWD, true);
mysql_select_db(DB_NAME, $db);

$GLOBALS['db'] = $db;
$GLOBALS['vkdb'] = $vkdb;

