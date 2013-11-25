<?php
/* oh gut, git git git */

// passwords, keys, db-settings
require_once('settings.local.php');

// database, mysql, why not?
include('db.php');

// nieuwe artikelen eerst!
$artikelen_res = mysql_query('select *, artikelen.ID as artikelid from artikelen left outer join facebook on artikelen.id = facebook.art_id where facebook.art_id IS NULL');
echo 'Indexing fresh articles. ('.mysql_num_rows($artikelen_res).')'."\n";
$crawled = crawl($artikelen_res);

// dan de verhalen van vandaag
$artikelen_res = mysql_query('select *, artikelen.ID as artikelid from artikelen left outer join facebook on artikelen.id = facebook.art_id where year(artikelen.created_at) = year(now()) and month(artikelen.created_at) = month(now()) and day(artikelen.created_at) = day(now())');
echo 'Indexing fresh articles. ('.mysql_num_rows($artikelen_res).')'."\n";
$crawled += crawl($artikelen_res);

$limit = FACEBOOK_MAX_CRAWL - $crawled;
// vervolgens artikelen die lang geleden een update kregen
$artikelen_res = mysql_query('select *, artikelen.ID as artikelid from artikelen left outer join facebook on artikelen.id = facebook.art_id where facebook.id > 0 order by facebook.last_crawl limit 0,'.$limit);
echo "\n".'Updating articles. ('.mysql_num_rows($artikelen_res).')'."\n";

crawl($artikelen_res);
echo "Done crawling facebook \n\n";

function crawl($artikelen_res)
{
	$i = 0;
	while ($artikel = mysql_fetch_array($artikelen_res))
	{
		$i++;

		echo str_pad($i, 3, ' ', STR_PAD_LEFT).' Querying facebook for: '.$artikel['clean_url']."\n";
		$apicall = "https://api.facebook.com/method/links.getStats?urls=".addslashes($artikel['clean_url'])."&format=json";
		$json=file_get_contents($apicall);
		$response = json_decode($json);
		
		// now find the record for this article
		$fb_res = mysql_query('select ID from facebook where art_id = '.$artikel['artikelid']);
		if(mysql_num_rows($fb_res) > 0)
		{
			mysql_query('update facebook set share_count = '.$response[0]->share_count.', comment_count = '.$response[0]->comment_count.', like_count = '.$response[0]->like_count.', total_count = '.$response[0]->total_count.', click_count = '.$response[0]->click_count.', last_crawl = now() where art_id = '.$artikel['artikelid']);
		}
		else
		{
			mysql_query('insert into facebook (art_id, share_count, comment_count, like_count, total_count, click_count, last_crawl)
									 values
									 ('.$artikel['artikelid'].', '.$response[0]->share_count.', '.$response[0]->comment_count.', '.$response[0]->like_count.', '.$response[0]->total_count.', '.$response[0]->click_count.', now() )');
		}

	}
	return $i;
}
