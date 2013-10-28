<?php
$query = 'nrc.nl/';

// passwords, keys, db-settings
require_once('settings.local.php');
// Create our twitter API object
require_once("twitteroauth.php");
include_once ('simple_html_dom.php');

// database, mysql, why not?
include('db.php');


$since = get_since();

echo "\n\n".'sinds: '.$since."\n";
// go to https://dev.twitter.com/apps and create new application
// and obtain [CONSUMER_KEY], [CONSUMER_SECRET], [oauth_key], [oauth_secret]
// then put them in settings.local.php
$oauth = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, OAUTH_KEY, OAUTH_SECRET);

// Make up a useragent
$oauth->useragent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/13.6.0.9';

$tweets_found = json_decode(
                  $oauth->get( 'http://api.twitter.com/1.1/search/tweets.json',
                                array('q' => $query,
                                      'count' => 100,
                                      'since_id' => $since,
                                      'result_type' => 'recent')
                              )
                            );
if(is_object($tweets_found)) foreach ($tweets_found->statuses as $tweet){
	//print_r($tweet->entities->urls);
	update_since($tweet->id);

	foreach($tweet->entities->urls as $url)
	{
		$tco = $url->url;

		$share = $url->expanded_url;
		if(! strstr($share, 'nrc.nl'))
		{
			$short = $share;
			$short_res = mysql_query('select * from unshorten where short_url = "'.addslashes($short).'"');
			if(mysql_num_rows($short_res) == 0)
			{
				echo substr($short, 0, 30);
				$share = unshorten_url($short);
				echo ' => '.substr($share, 0, 45)."\n";
				// opslaan opdat we deze niet nogmaals opvragen
				mysql_query('insert into unshorten (short_url, url) values ("'.addslashes($short).'","'.addslashes($share).'")');
			}
			else
			{ // misschien moeten we hem tellen?
				$short_arr = mysql_fetch_array($short_res);
				$share = $short_arr['url'];
			}
		}
		if(strstr($share, 'nrc.nl'))
		{

			$parsed = parse_url ($share);
			if (isset($parsed['path']))
			{
				if ( !strstr($parsed['host'], 'nrc.nl')
						 || strstr($parsed['host'], 'actie.nrc.nl')
						 || strstr($parsed['host'], 'zoeken.nrc.nl' )
						 || strstr($parsed['host'], 'login.nrc.nl')
						 || strstr($parsed['host'], 'service.nrc.nl')
						 || strstr($parsed['host'], 'klik.nrc.nl')
						 || strstr($parsed['host'], 'digitaleeditie.nrc.nl') )
				{
					echo 'skipping: '.substr($share,0,70)."\n";
					continue;
				}
				$path = $parsed['path'];
				$path_p = explode('/', $path);
				if(isset($path_p[1]))
				{
					if($parsed['host'] == 'm.nrc.nl')
						$parsed['host'] = 'www.nrc.nl';
					if($parsed['host'] == 'nrc.nl')
						$parsed['host'] = 'www.nrc.nl';

					$pubdate = $path_p[2].'-'.$path_p[3].'-'.$path_p[4];

					$clean = $parsed['scheme'].'://'.$parsed['host'].$path;
					// en als 't laatste teken nou eens geen '/' is? anders krijgen we
					// http://www.nrc.nl/boeken/2013/10/27/eerste-jan-wolkers-prijs-naar-simon-van-der-geest/ en
					// http://www.nrc.nl/boeken/2013/10/27/eerste-jan-wolkers-prijs-naar-simon-van-der-geest in de database :-(
					$clean_plus = $clean;
					if ('/' != $clean[strlen($clean)-1])
					{
						$clean_plus = $clean.'/';
					}
					$artikel_id = 0;
					$query = 'select * from artikelen where clean_url in ("'.$clean.'", "'.$clean_plus.'")';
					$res = mysql_query($query);
					if(mysql_num_rows($res))
					{
						$art_row = mysql_fetch_array($res);
						$artikel_id = $art_row['ID'];

						if (COUNT_TWEETS == 1)
						{
							$tweet_res = mysql_query('select * from tweets where art_id = '.$art_row['ID'].' and tweet_id = "'.$tweet->id.'"');
							if (mysql_num_rows($tweet_res) == 0)
							{
								echo 'Make it count! tweet: '.$tweet->id."\n";
								mysql_query('insert into tweets (tweet_id, art_id) values ("'.$tweet->id.'", '.$art_row['ID'].')');
							}
						}

						$og = unserialize(stripslashes($art_row['og']));
						// deze staat al goed, maar in het begin kwam de publicatietijd en de auteur of de sectie niet altijd goed door
						// dat kunnen we rustig herstellen ...
						if (! empty($og['article:published_time']) && ! empty($og['article:author']))
							continue;
						echo 'Marked for update. id: '.$artikel_id."\n";

						// 27-10-2013, door laten lopen en de artikelen updaten die geen auteur of sectie hebben
						// verwijder de meta_artikel rijen van dit artikel
						mysql_query('delete from meta_artikel where art_id = '.$artikel_id);
					}

					// even de url opvragen om de auteur te vinden
					$html = file_get_html($share);
					$og = array();
					if (is_object($html))
					{
						foreach( $html->find('meta[property^=og:], meta[name^=twitter:], meta[property^=twitter:]') as $meta )
						{
							if(strstr($meta->property, 'og:'))
							{
								$key = substr($meta->property,3);
								$og[$key] = stripslashes($meta->content);
							}
						}
						$author_found = 0;
						foreach( $html->find('div[class=author]') as $author)
						{ // this works for nrc.nl !! :-)
							// <span>door<a href="">auterusnaam</a></span>
							$og['article:author'] = $author->first_child()->first_child()->innertext;
							echo 'Found author: '.$og['article:author']."\n";
							$author_found = 1;
						}
						if($author_found == 0)
						{
							// we hebben ook nog chartbeat info:
							// <div id="chartbeat-config" data-sections="" data-authors="Youp"></div>
							foreach($html->find('div[id=chartbeat-config]') as $chartbeatconfig)
							{
								$og['article:author'] = $chartbeatconfig->{"data-authors"};
								echo 'Found author via chartbeat: '.$og['article:author']."\n";
								$author_found = 1;
							}
						}
						foreach ($html->find('meta[property^=ad:categories]') as $categories)
						{
							$cats = explode(',', $categories->content);
							echo 'Found categories: '.$categories."\n";
							foreach($cats as $cat)
							{
								$cat = trim($cat);
								if($cat == 'Nieuws' || $cat == 'Beste van het web')
									continue;
								$og['article:section'] = $cat;
							}
						}
						if (empty($og['article:section']))
						{ // blog-slug dan als categorie gebruiken, als die er ook niet is, dan is er altijd nog een auteur
							if( $parsed['host'] == 'archief.nrc.nl' ) {
								$og['article:section'] = 'archief';
								if (empty($og['article:author']))
									$og['article:author'] = 'Een onzer redacteuren';
							}
							elseif( $parsed['host'] == 'vorige.nrc.nl' )
							{
								// Okay, vorige is weird, die stuurt compressed zonder te zeggen dat 't compressed is!
								// ofzo;
								$og['article:section'] = 'vorige';
								$ch = curl_init($share);
								curl_setopt_array($ch, array(
								                CURLOPT_FOLLOWLOCATION => TRUE,
                                CURLOPT_RETURNTRANSFER => TRUE
                                ));

								$plaintext_html = curl_exec($ch);
								curl_close($ch);
								$plaintext_html = gzdecode($plaintext_html);
								$html = str_get_html($plaintext_html);
								$og['title'] = $html->find('title',0)->innertext;
								// vorige is nog weirder, de html laat zich niet correct parsen met simple_html_dom
								// de class is vindbaar, maar niet als object benaderbaar, dan maar met het handje ...
								$author = explode('<div class="author">',$html->innertext);
								$author = $author[1];
								$author = explode('</div>', $author);
								$author = $author[0];
								$author = explode('span>', $author);
								$author = substr(trim($author[1]), 0, strlen(trim($author[1])) - 2);
								echo 'Found author: '.$author."\n";
								$og['article:author'] = $author;
							}
							elseif( $parsed['host'] == 'retro.nrc.nl' )
							{
								$og['article:section'] = 'retro';
								if (empty($og['article:author']))
									$og['article:author'] = 'Een onzer redacteuren';
							}
							else // gewoon op nrc.nl
							{
								if(is_object($html->find('article[id=artikel]'))) foreach($html->find('article[id=artikel]') as $artinfo)
								{
									$og['article:section'] = $artinfo->{"data-blog-slug"};
								}
								if (empty($og['article:author']))
								{ // een Berry! deze code werkt voor de kunsthal, niet voor Lehman of Berry ...
									$author = explode('class="byline"', $html->innertext);
									$author = $author[1];
									$author = explode('</div>', $author);
									$author = $author[0];
									preg_match_all('/<h3>.*<a.*>(.*)<\/a><\/h3>/', $author, $matches);
									if(! empty($matches[1][0]))
									{
										$og['article:author'] = $matches[1][0];
										$og['article:section'] = 'Berry';
									}
								}

								if (empty($og['article:section'])) // last resort
									$og['article:section'] = $og['article:author'];
							}
							echo 'Assigned section: '.$og['article:section']."\n";
						}
						// publicatiedatum en tijd:
						// <strong><time datetime="2013-10-28">28 oktober 2013</time></strong>, 20:52</a>
						$doc = $html->innertext;
						// nieuwsartikelen op www.nrc.nl
						preg_match_all('/<time.datetime="(.*)">.*<\/time><\/strong>..(.*)<\/a>/uU', $doc, $matches);
						if (! empty($matches[1][0]) && ! empty ($matches[2][0]))
						{
							$pubdate = $matches[1][0].' '.$matches[2][0];
						}
						else
						{ // op een weblog zitten we in een multiline datumstempel
							//<div class="datumstempel" title="27 oktober">
							//<time pubdate datetime="2013-10-27T01:00:00+02:00" itemprop="datePublished">
							preg_match_all('/<time.pubdate.datetime="(.*)T(..:..).*".itemprop/uU', $doc, $matches);
							if (! empty($matches[1][0]) && ! empty ($matches[2][0]))
							{
								$pubdate = $matches[1][0].' '.$matches[2][0];
							}
						}
						if($parsed['host'] == 'retro.nrc.nl')
						{
							//<FONT SIZE=-3> NRC Webpagina's<BR> 10 JANUARI 2001 </FONT>
							$months = array('januari' => 1 ,'februari' => 2, 'maart' => 3, 'april' => 4, 'mei' => 5, 'juni' => 6, 'juli' => 7, 'augustus' => 8, 'september' => 9, 'oktober' => 10,'november' => 11,'december' => 12);
							preg_match_all('%<font size=-3>.*<br>.*(\d+)\s(.*)\s(\d+)\s</font>%imU', $doc, $matches);
							if (! empty($matches[1][0]) && ! empty($matches[2][0]) && ! empty($matches[3][0]))
							{
								$maand = $months[strtolower($matches[2][0])];
								$pubdate = $matches[3][0].'-'.$maand.'-'.$matches[1][0];
							}
						}
						// $pubdate, whichever value it has into og['article:publish_time']
						echo 'Publicatietijd: '.$pubdate.' -> '.strtotime($pubdate)."\n";
						if($pubdate > '')
							$og['article:published_time'] = strtotime($pubdate);
						// herstel &amp;amp;
						foreach($og as $key => $value)
						{
							$og[$key] = str_replace('&amp;amp;', '&amp;', $value);
						}
					}
					else
					{ // geen html object? Skip this article!
						echo 'Not an article!! '.$clean."\n";
						continue;
					}
					// nu mogen we serializen
					$og = serialize($og);
					// share url ook van m.nrc.nl ontdoen...
					if( strstr($share,'m.nrc.nl') )
						$share = str_replace('m.nrc.nl', 'www.nrc.nl', $share);
					if( strstr($share, 'http://nrc.nl/'))
						$share = str_replace('http://nrc.nl', 'http://www.nrc.nl', $share);
					if($artikel_id > 0)
					{
						echo 'Updating article: '.$artikel_id."\n";
						mysql_query('update artikelen set og = "'.addslashes($og).'" where id = '.$artikel_id);
					}
					else
					{
						echo 'Adding article: '.$clean."\n";
//					echo 'inserting: insert into artikelen (t_co, clean_url, share_url, og) values ("'.$tco.'", "'.$clean.'", "'.$share.'", "'.substr($og,0,20).'")'."\n";
						mysql_query('insert into artikelen (t_co, clean_url, share_url, og) values ("'.$tco.'", "'.$clean.'", "'.$share.'", "'.addslashes($og).'")');
					}
					if (COUNT_TWEETS == 1)
					{
						echo 'counting tweet '.$tweet->id."\n";
						mysql_query('insert into tweets (tweet_id, art_id) values ("'.$tweet->id.'", '.mysql_insert_id().')');
					}
				}
			}
		}
	}
}

// alle meta-waardes wegschrijven in de meta-table voor makkelijker cross-linken:
// selecteer alle artikelen die geen meta_artikel rows bezitten
$res = mysql_query ('select artikelen.ID as art_id, og from artikelen left outer join meta_artikel on artikelen.ID = meta_artikel.art_id where meta_artikel.art_id IS NULL');
$skip_keys = array('url', 'locale', 'site_name');
while ($row = mysql_fetch_array($res))
{
	$og = unserialize($row['og']);
	$art_id = $row['art_id'];
	foreach($og as $key => $value)
	{
		if(in_array($key, $skip_keys))
			continue;

		$meta_res = mysql_query('select * from meta where `type` = "'.$key.'" and waarde = "'.$value.'"');
		if (mysql_num_rows($meta_res) == 0)
		{
			mysql_query('insert into meta (waarde, type) values ("'.$value.'", "'.$key.'")');
			$meta_id = mysql_insert_id();
		}
		else
		{
			$meta_arr = mysql_fetch_array($meta_res);
			$meta_id = $meta_arr['ID'];
		}
		// koppel aan het gevonden artikel
		$link_res = mysql_query('select * from meta_artikel where art_id = '.$art_id.' and meta_id = '.$meta_id);
		if( mysql_num_rows($link_res) == 0)
		{ // en maak de meta-link
			mysql_query('insert into meta_artikel (art_id, meta_id) values ('.$art_id.', '.$meta_id.')');
		}
	}
}

function update_since($since)
{
	$query = 'update app_keys set app_keys.app_value = "'.$since.'" where app_key = "since"';
	mysql_query($query);
}

function get_since()
{
	$res = mysql_query('select app_value from app_keys where app_key = "since"');
	$row = mysql_fetch_array($res);
	return $row['app_value'];
}


function unshorten_url($url)
{
	$ch = curl_init($url);
	curl_setopt_array($ch, array(
                                CURLOPT_FOLLOWLOCATION => TRUE,  // the magic sauce
                                CURLOPT_RETURNTRANSFER => TRUE,
                                CURLOPT_SSL_VERIFYHOST => FALSE, // suppress certain SSL errors
                                CURLOPT_SSL_VERIFYPEER => FALSE,
                               )
                    );
	curl_exec($ch);
	$url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
	curl_close($ch);
	return $url;
}
