<?php
/* oh gut, git git git */

// passwords, keys, db-settings
require_once('settings.local.php');

// database, mysql, why not?
include('db.php');


// nieuwe artikelen eerst!
$artikelen_res = mysql_query('select *, artikelen.ID as artikelid from artikelen left outer join twitter on artikelen.id = twitter.art_id where twitter.art_id IS NULL');
echo 'Indexing fresh articles. ('.mysql_num_rows($artikelen_res).')'."\n";
$crawled = crawl($artikelen_res);

// dan de verhalen van vandaag
$artikelen_res = mysql_query('select *, artikelen.ID as artikelid from artikelen left outer join twitter on artikelen.id = twitter.art_id where year(artikelen.created_at) = year(now()) and month(artikelen.created_at) = month(now()) and day(artikelen.created_at) = day(now())');
echo 'Indexing fresh articles. ('.mysql_num_rows($artikelen_res).')'."\n";
$crawled += crawl($artikelen_res);

$limit = FACEBOOK_MAX_CRAWL - $crawled;
// vervolgens artikelen die lang geleden een update kregen
$artikelen_res = mysql_query('select *, artikelen.ID as artikelid from artikelen left outer join twitter on artikelen.id = twitter.art_id where twitter.id > 0 order by twitter.last_crawl limit 0,'.$limit);
echo "\n".'Updating articles. ('.mysql_num_rows($artikelen_res).')'."\n";

crawl($artikelen_res);
echo "Done crawling twitter \n\n";

function crawl($artikelen_res)
{
	$i = 0;
	while ($artikel = mysql_fetch_array($artikelen_res))
	{
		$i++;

		echo str_pad($i, 3, ' ', STR_PAD_LEFT).' Querying twitter for: '.$artikel['clean_url']."\n";
		$apicall = 'http://urls.api.twitter.com/1/urls/count.json?url='.$artikel['clean_url'];
		$json=file_get_contents($apicall);
		$response = json_decode($json);

		// now find the record for this article
		$tw_res = mysql_query('select ID from twitter where art_id = '.$artikel['artikelid']);
		if(mysql_num_rows($tw_res) > 0)
		{
			mysql_query('update twitter set twitter_count = '.$response->count.', last_crawl = now() where art_id = '.$artikel['artikelid']);
		}
		else
		{
			mysql_query('insert into twitter (art_id, twitter_count, last_crawl)
									 values
									 ('.$artikel['artikelid'].', '.$response->count.', now() )');
		}

	}
	return $i;
}
