<?php

// passwords, keys, db-settings
require_once('settings.local.php');

// database, mysql, why not?
include('db.php');

// nieuwe artikelen eerst!
$artikelen_res = mysql_query('select *, artikelen.ID as artikelid from artikelen left outer join facebook on artikelen.id = facebook.art_id where facebook.art_id IS NULL limit 0,50');
echo 'Indexing new articles. ('.mysql_num_rows($artikelen_res).')'."\n";
$crawled = crawl($artikelen_res);

// dan de verhalen van vandaag
$artikelen_res = mysql_query('select *, artikelen.ID as artikelid from artikelen left outer join facebook on artikelen.id = facebook.art_id where artikelen.created_at > date_add(now(), interval -3 day) and date_add(now(), interval -1 hour) > last_crawl');
echo 'Indexing fresh articles. ('.mysql_num_rows($artikelen_res).')'."\n";
$crawled += crawl($artikelen_res);

$limit = FACEBOOK_MAX_CRAWL - $crawled;
// vervolgens artikelen die lang geleden een update kregen
$artikelen_res = mysql_query('select *, artikelen.ID as artikelid from artikelen left outer join facebook on artikelen.id = facebook.art_id where facebook.id > 0 order by facebook.last_crawl limit 0,'.$limit);
echo "\n".'Updating articles. ('.mysql_num_rows($artikelen_res).')'."\n";

crawl($artikelen_res);
echo "\nDone crawling facebook \n\n";

function crawl($artikelen_res)
{
	$i = 0;
	$access_token = FACEBOOK_APP_ID.'|'.FACEBOOK_APP_SECRET;

	while ($artikel = mysql_fetch_array($artikelen_res))
	{
		$i++;

		echo "\n".str_pad($i, 3, ' ', STR_PAD_LEFT).' Querying facebook for: '.$artikel['clean_url'];
		$apicall = "https://graph.facebook.com/v2.8/?access_token={$access_token}&fields=og_object{likes.summary(true).limit(0),engagement.limit(0)},share&id=".urlencode(str_replace('%5C', '', addslashes($artikel['clean_url'])));
		$json=file_get_contents($apicall);
		$response = json_decode($json, true);

		// now find the record for this article
		$fb_res = mysql_query('select ID from facebook where art_id = '.$artikel['artikelid']);
		if (isset ($response['share'])) {
                        $likes = isset($response['og_object']) ? $response['og_object']['likes']['summary']['total_count'] : 0;
			$total = (int)$response['share']['share_count'];

			if(mysql_num_rows($fb_res) > 0)
			{
				mysql_query('update facebook set share_count = 0, comment_count = '.$response['share']['comment_count'].', like_count = '.$likes.', total_count = '.$total.', last_crawl = now() where art_id = '.$artikel['artikelid']);
			}
			else
			{
				mysql_query('insert into facebook (art_id, share_count, comment_count, like_count, total_count, last_crawl)
									 values
									 ('.$artikel['artikelid'].', 0, '.$response['share']['comment_count'].', '.$likes.', '.$total.', now() )');
			}
		} elseif (mysql_num_rows($fb_res) === 0) {
			 mysql_query('insert into facebook (art_id, last_crawl) values ('.$artikel['artikelid'].', now()) ');
			 echo "\n --inserting for last-crawl. None-response";
		} else {
			mysql_query ('update facebook set last_crawl = now() where art_id = '. $artikel['artikelid']);
			echo "\n --updated last-crawl. None-response";
		}

	}
	return $i;
}
