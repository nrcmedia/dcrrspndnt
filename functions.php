<?php
// functions used in more than one file
function pager($tot_row, $qsa)
{
	$query = $_SERVER['PHP_SELF'];
	$path = pathinfo( $query );

	// how many pages?
	$pages = ceil($tot_row / ITEMS_PER_PAGE);

	$i = 0;
	if ($pages > 1)
	{
?>
		<ul id="pager">
			<li class="text">pagina:</li>
<?php
		while ($i < $pages)
		{
			$page = $i + 1;
			echo '			<li><a href="./'.$path['basename'].'?page='.$page.$qsa.'">'.$page.'</a></li>';
			$i++;
		}
?>
			<li class="text">(tot: <?php echo $tot_row;?>)</li>
		</ul>
<?php
	}
}


function show_table($res,
									  $table_header,
									  $show_selectors = false,
									  $fields = array('pubdate', 'title', 'author', 'section', 'tweets', 'fb_count'),
									  $extra_class = '')
{
	?>
		<table<?php echo $extra_class;?>>
			<tr>
				<?php echo $table_header;?>
			</tr>
<?php
$tot_tweets = 0;
$tot_fb = 0;
while($row = mysql_fetch_array($res) )
{
	$og = unserialize(stripslashes($row['og']));
	$titel = empty($og['title']) ? substr($row['clean_url'],18,50) : $og['title'];
	$description = isset($og['description']) ? $og['description'] : 'Een mysterieus artikel';
	$auth_res = mysql_query('select * from meta where meta.waarde = "'.$og['article:author'].'" and meta.type="article:author"');
	$author = mysql_fetch_array($auth_res);
	$section_res = mysql_query('select * from meta where meta.waarde = "'.$og['article:section'].'" and meta.type="article:section"');
	$section = mysql_fetch_array($section_res);
	$display_time = ! empty($og['article:published_time']) ? strftime('%e %b %H:%M', $og['article:published_time']) : substr($row['created_at'],8,2).'-'.substr($row['created_at'],5,2).' '.substr($row['created_at'],11,5);

	if(isset($og['article:published_time']) && $og['article:published_time'] < time() - 360 * 24 * 60 * 60)
	{
		$display_time = strftime('%e %b %Y', $og['article:published_time']);
	}
	$found_at = substr($row['created_at'],8,2).'-'.substr($row['created_at'],5,2).' '.substr($row['created_at'],11,5);
	$fb_abbr = 'Facebook, likes: '.$row['fb_like'].' shares: '.$row['fb_share'].' comments: '.$row['fb_comment'];
	$tot_tweets += $row['tweet_count'];
	?>
			<tr <?php if($i % 2 == 1) echo 'class="odd"'?>>
<?php
			if (in_array('pubdate',$fields))
			{
?>
				<td><abbr title="gevonden op: <?php echo $found_at;?>"><?php echo $display_time ?></abbr></td>
<?php
			}
			if (in_array('title', $fields)) {
?>
				<td style="max-width:400px"><strong><a href="<?php echo $row['share_url'];?>" title="<?php echo $description ?>"><?php echo $titel ;?></a></strong></td>
<?php
			}
			if(in_array('author', $fields)) {
?>
				<td><a href="./meta_art.php?id=<?php echo $author['ID'];?>" title="alle artikelen van deze auteur"><?php echo $author['waarde'];?></a></td>
<?php
			}
			if(in_array('section', $fields)) {
?>
				<td><a href="./meta_art.php?id=<?php echo $section['ID'];?>" title="alle artikelen in deze sectie"><?php echo $section['waarde'];?></a></td>
<?php
			}
			if(in_array('tweets', $fields))
			{
?>
				<td align="right"><abbr title="All time twitter: <?php echo $row['twitter_alltime'];?>"><?php echo $row['tweet_count']?></abbr></td>
<?php
			}
			if(in_array('fb_count', $fields))
			{
?>
				<td align="right"><abbr title="<?php echo $fb_abbr;?>"><?php echo $row['fb_total'];?></abbr></td>
<?php
		}
?>
			</tr>
	<?php
	$i++;
}
if ($show_selectors)
{
$disp = isset($_GET['disposition']) ? (int) $_GET['disposition'] : '';
?>
				<tr>
					<td colspan="4" align="right">totaal tweets:</td><td align="right"><strong><?php echo $tot_tweets;?></strong></td>
					<td align="right"></td>
				</tr>
			<tr>
				<td></td>
				<td colspan="4">per uur:
					<script>
						function goto_sel(selector) {
							var sel = document.getElementById(selector).selectedIndex;
							var uris = document.getElementById(selector).options;
							var goto = uris[sel].value;
							window.location=('top.php'+goto);
							return;
						}
					</script>
					<form class="disp_selector" method="GET" action="javascript:goto_sel('hour');" onsumbit="return goto_sel('hour')">
					<select id="hour">
						<option value="?mode=hour">afgelopen uur</option>
						<?php
						for ($i=1;$i<24;$i++)
						{
							$selected = ( $disp == $i ) ? ' selected="true" ' : '';
						?>
							<option value="?mode=hour&disposition=<?php echo $i;?>" <?php echo $selected;?>><?php echo $i;?> uur geleden</option>
						<?php
						}
						?>
					</select>
					<input type="submit" value="Toon"/>
					</form>
					per dag:
					<form class="disp_selector" method="GET" action="javascript:goto_sel('day');" onsumbit="return goto_sel('day')">
					<select id="day">
						<option value="?mode=day">afgelopen dag</option>
						<?php
						for ($i=1;$i<6;$i++)
						{
							$selected = ( $disp == $i ) ? ' selected="true" ' : '';
						?>
							<option value="?mode=day&disposition=<?php echo $i;?>" <?php echo $selected;?>><?php echo $i;?> dag geleden</option>
						<?php
						}
						?>
					</select>
					<input type="submit" value="Toon"/>
					</form>
				</td>
				<td></td>
			</tr>
<?php
	}
?>
		</table>
<?php

}

function get_tweet_benchmark()
{
	static $fenton;
	if (empty($fenton))
	{
		$arr = mysql_fetch_array(mysql_query('select cast(avg(twitter.twitter_count) as unsigned) as fenton from twitter'));
		$fenton = $arr['fenton'];
	}
	return $fenton;
}

if (! function_exists('gzdecode'))
{
	function gzdecode($data)
	{
		return gzinflate(substr($data,10,-8));
	}
}

/**
 * tweets_per_dag
 * geeft van de laatste 30 dagen de labels en de labels terug
 * optioneel: $mode = 'JSON', geeft enkel de data terug
 * Dit is grafiek 1 op de h-charts.php pagina
 */
function tweets_per_day($mode = '')
{
	$tot_tweets_res = mysql_query('select count(tweets.id) as tweet_count, day(tweets.created_at) as dag, month(tweets.created_at) as maand, 0 as stack from tweets where created_at > "2013-10-13 21:00"  group by maand, dag order by year(tweets.created_at) desc, month(tweets.created_at) desc, day(tweets.created_at) desc limit 0,30');

	$label  = array();
	$stack  = array();
	$tweets = array();
	$rows   = array();

	$i = 0;
	while ($row = mysql_fetch_array($tot_tweets_res))
	{
		if($i % 7 == 0)
		{
			$week = $i / 7;
			$till_now = mysql_fetch_array(mysql_query('select count(*) as tweets_then from tweets where date(created_at) = date_sub(curdate(), interval '.$week. ' week) and time(created_at) <= curtime() '
			                                         )
			                             );
			$stack_then = mysql_fetch_array(mysql_query('select count(*) as tweets_then from tweets where date(created_at) = date_sub(curdate(), interval '.$week. ' week) and time(created_at) > curtime() '
			                                       )
			                             );
			$row['tweet_count'] = $till_now['tweets_then'];
			$row['stack'] = $stack_then['tweets_then'];
		}

		$rows[] = $row;
		$i++;
	}
	$rows = array_reverse($rows);

	$cur_month = '';
	foreach($rows as $row)
	{
		$lab = $row['dag'];

		if ( (int)$row['maand'] != (int)$cur_month)
		{
			$lab .= '-'.$row['maand'];
			$cur_month = $row['maand'];
		}
		$label[] = $lab;
		$tweets[] = (int)$row['tweet_count'];
		$stack[] = (int)$row['stack'] > 0 ? (int)$row['stack'] : 'null';
	}

	$bar_label = '';
	foreach($label as $lab)
	{
		$bar_label .= '"'.$lab.'",';
	}
	$bar_label = substr($bar_label, 0, strlen($bar_label) - 1);

	$bar_tweet_data = '';
	foreach($tweets as $tweet_data)
	{
		$bar_tweet_data .= $tweet_data.',';
	}
	$bar_stack_data = '';
	$stack_json = array();
	foreach($stack as $stack_data)
	{
		$bar_stack_data .= $stack_data.',';
		$stack_json[] = $stack_data == 'null' ? NULL : $stack_data;
	}
	$bar_tweet_data = substr($bar_tweet_data, 0, strlen($bar_tweet_data) - 1);
	$bar_stack_data = substr($bar_stack_data, 0, strlen($bar_stack_data) - 1);

	$chart_data = array('data' => $bar_tweet_data, 'label' => $bar_label, 'stack' => $bar_stack_data);
	if (!$mode == 'JSON')
		return $chart_data;
	else
		return array($label, $tweets, $stack_json);
}

/** tweets_today

*/
function tweets_today($mode = '')
{
	$dagen_res = mysql_query("select day(tweets.created_at) as dagen from tweets group by dagen");
	$dagen = mysql_num_rows($dagen_res);

	$graph_res = mysql_query("select count(tweets.id) as tweet_count, hour(tweets.created_at) as the_uur from tweets  group by the_uur ");

	$hour_label = '';
	$hour_label_array = array();
	$hour_tweet_data = '';
	$hour_tweet_data_array = array();
	$uur_nu = date('H');
	$minuut_nu = date('i');
	$projection_data_array = array();
	$hour_today_data_array = array();

	while ($row = mysql_fetch_array($graph_res))
	{
		$hour_label .= $row['the_uur'].',';
		$deler = (int)$row['the_uur'] > (int)$uur_nu ? $dagen - 1 : $dagen;
		$tot = ceil($row['tweet_count'] / $deler);
		$hour_tweet_data .= $tot.',';
		$hour_tweet_data_array[] = $tot;
		$hour_label_array[] = $row['the_uur'];
	}

	$hour_label = substr($hour_label, 0, strlen($hour_label) - 1);
	$hour_tweet_data = substr($hour_tweet_data, 0, strlen($hour_tweet_data) - 1);

	// A la Chartbeat, de lijn wordt langer tijdens de dag
	// verschijnt in de uur-trend-grafiek
	$res_today = mysql_query("select count(tweets.ID) per_hour, hour(tweets.created_at) as the_hour, tweets.created_at from tweets
	where year(tweets.created_at) = year(now() )
	  and month(tweets.created_at) = month(now())
	  and day(tweets.created_at) = day(now() )
	group by the_hour
	order by created_at");

	// verwerken in grafiek-data
	$i=0;
	while ($row = mysql_fetch_array($res_today))
	{
		while ($i < (int)$row['the_hour'])
		{
			$hour_today_data .= '0,';
			$hour_today_data_array[] = 0;
			$i++;
		}
		$hour_today_data .= $row['per_hour'].',';
		$hour_today_data_array[] = (int)$row['per_hour'];
		$i++;
		if( (int)$row['the_hour'] == (int)$uur_nu)
		{ // make projection; 12 times per hour, which time are we?
			$hour_part = floor($minuut_nu / 5) + 1;
			$projection = floor((12 / $hour_part) * (int)$row['per_hour']);
			$j = 0;
			while($j < (int)$uur_nu) // naar de juiste plek brengen ...
			{
				$projection_data .= 'null,';
				$projection_data_array[] = NULL;
				$j++;
			}
			$projection_data .= $projection;
			$projection_data_array[] = $projection;
		}
	}

	$hour_today_data = substr($hour_today_data, 0, strlen($hour_today_data) - 1);

	$chart_data = array('label'           => $hour_label,
	                    'average_data'    => $hour_tweet_data,
	                    'current_data'    => $hour_today_data,
	                    'projection_data' => $projection_data);
	if (!$mode == 'JSON')
		return $chart_data;
	else
		return array($hour_label_array, $hour_tweet_data_array, $hour_today_data_array,$projection_data_array);
}

function tweets_per_minute($mode = '')
{
	// Tweets per 5 minuten, vandaag
	// vergelijken met vorige week
	$comp_year  = date('Y', time()-86400 * 7);
	$comp_month = date('m', time()-86400 * 7);
	$comp_day   = date('d', time()-86400 * 7);
	$minute_res = mysql_query("select count(*) as per_minute,
                                    minute(tweets.created_at) as the_minute,
                                    hour(tweets.created_at) as the_hour,
                                    tweets.created_at
                             from tweets
                             where year(tweets.created_at) = year(now() )
                               and month(tweets.created_at) = month(now())
                               and day(tweets.created_at) = day(now() )
                             group by the_minute , the_hour
                             order by created_at ");
	$comp_minute_res = mysql_query("select count(*) as per_minute,
                                         minute(tweets.created_at) as the_minute,
                                         hour(tweets.created_at) as the_hour,
                                         tweets.created_at
                                  from tweets
                                  where year(tweets.created_at) = ".$comp_year."
                                    and month(tweets.created_at) = ".$comp_month."
                                    and day(tweets.created_at) = ".$comp_day."
                                  group by the_minute , the_hour
                                  order by created_at");
	// derde lijn, het gemiddelde van alle dagen op die minuut ....
	$avg_res = mysql_query('select avg(tweet_count) as per_minute,
	                               minute(created_at) as the_minute,
	                               hour(created_at) as the_hour,
	                               created_at
	                        from  (select count(*) as tweet_count, created_at
	                               from tweets
	                               group by year(created_at),
	                                        month(created_at),
	                                        day(created_at),
	                                        hour(created_at),
	                                        minute(created_at)
	                              ) temp_table
	                        group by hour(created_at),
	                                 minute(created_at)
	                        order by hour(created_at), minute(created_at)');

	// labels klaarzetten
	$labels = array();
	$labels_json = array();
	$values = array();
	$values_json = array();
	$comp_values = array();
	$comp_values_json = array();
	$avg_values = array();
	$avg_values_json = array();

	// draaien om 24 uur vol te krijgen
	$hour = 0;
	while($hour < 24)
	{
		$str_hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
		$minute = 0;
		while($minute < 60)
		{
			$str_minute = str_pad($minute,2, '0', STR_PAD_LEFT);
			$label = $str_hour.'.'.$str_minute;
			$labels[$str_hour.':'.$str_minute] = $label;
			$labels_json[] = $label;
			$values[$str_hour.':'.$str_minute] = 0;
			$comp_values[$str_hour.':'.$str_minute] = 0;
			$avg_values[$str_hour.':'.$str_minute] = 0;
			$minute = $minute + 5;
		}
		$hour++;
	}

	while($row = mysql_fetch_array($minute_res))
	{
		$str_hour   = str_pad($row['the_hour'], 2, '0', STR_PAD_LEFT);
		$str_minute = str_pad($row['the_minute'], 2, '0', STR_PAD_LEFT);
		$values[$str_hour.':'.$str_minute] = $row['per_minute'];
	}
	while($comp_row = mysql_fetch_array($comp_minute_res))
	{
		$str_hour   = str_pad($comp_row['the_hour'], 2, '0', STR_PAD_LEFT);
		$str_minute = str_pad($comp_row['the_minute'], 2, '0', STR_PAD_LEFT);
		$comp_values[$str_hour.':'.$str_minute] = $comp_row['per_minute'];
	}
	while($avg_row = mysql_fetch_array($avg_res))
	{
		$str_hour   = str_pad($avg_row['the_hour'], 2, '0', STR_PAD_LEFT);
		$str_minute = str_pad($avg_row['the_minute'], 2, '0', STR_PAD_LEFT);
		$avg_values[$str_hour.':'.$str_minute] = $avg_row['per_minute'];
	}

	// transform this to javascrript
	$i = 0;
	foreach($labels as $time => $label)
	{
		$tweets_per_minute_label .= '"'.htmlspecialchars($label).'",';
		$tweets_per_minute_value .= $values[$time].',';
		$values_json[] = (int)$values[$time];
		$comp_tweets_per_minute_value .= $comp_values[$time].',';
		$comp_values_json[] = (int)$comp_values[$time];
		$avg_tweets_per_minute_value .= $avg_values[$time].',';
		$avg_values_json[] = (float)$avg_values[$time];
		$i++;
	}
	$tweets_per_minute_value = substr($tweets_per_minute_value, 0, strlen($tweets_per_minute_value) - 1);
	$tweets_per_minute_label = substr($tweets_per_minute_label, 0, strlen($tweets_per_minute_label) - 1);
	$comp_tweets_per_minute_value = substr($comp_tweets_per_minute_value, 0, strlen($comp_tweets_per_minute_value) - 1);
	$avg_tweets_per_minute_value = substr($avg_tweets_per_minute_value, 0, strlen($avg_tweets_per_minute_value) - 1);

	$chart_data = array('label'           => $tweets_per_minute_label,
										  'today_value'     => $tweets_per_minute_value,
										  'last_week_value' => $comp_tweets_per_minute_value,
										  'average_value'   => $avg_tweets_per_minute_value);

	if (!$mode == 'JSON')
		return $chart_data;
	else
		return array($labels_json, $values_json, $comp_values_json,$avg_values_json);
}

function tweets_per_article($mode = '')
{
	$art_res = mysql_query('select count(tweets.id) as tweets_today, artikelen.*
	                        from artikelen
	                        left join tweets on tweets.art_id = artikelen.id
	                        join (
	                               select artikelen.id
	                               from artikelen
	                               left join tweets on tweets.art_id = artikelen.id
	                               where date(artikelen.created_at) = curdate()
	                               group by artikelen.id
	                               order by count(tweets.id) desc
	                               limit 0,25
	                             ) top_arts
	                        where artikelen.ID = top_arts.ID
	                        group by artikelen.ID
	                        order by artikelen.created_at	' );
	$today_tweets_title = 'Artikelen van vandaag';
	// hiermee zetten we de labels en de x-as waardes
	$num_arts = mysql_num_rows($art_res);
	// zolang we nog niks terugkrijgen ('snacht ;-))
	// gisteren halen
	if($num_arts == 0)
	{
		$art_res = mysql_query('select count(tweets.id) as tweets_today, artikelen.*
		                        from artikelen
		                        left join tweets on tweets.art_id = artikelen.id
		                        join (
		                               select artikelen.id
		                               from artikelen
		                               left join tweets on tweets.art_id = artikelen.id
		                               where date(artikelen.created_at) = date_sub(curdate(), interval 1 day )
		                               group by artikelen.id
		                               order by count(tweets.id) desc
		                               limit 0,25
		                             ) top_arts
		                        where artikelen.ID = top_arts.ID
		                        group by artikelen.ID
		                        order by artikelen.created_at	' );
		$today_tweets_title = 'Artikelen van gisteren';
	}

	$art_today_label = '';
	$art_today_label_json = array();
	$art_today_count = '';
	$art_today_count_json = array();
	$art_today_fenton_json = array();

	$i = 1;

	while ($row = mysql_fetch_array($art_res))
	{
		$og = unserialize($row['og']);
		$art_today_label .= '"'.trim(preg_replace('/\s\s+/', ' ', $og['title'])).'",';
		$art_today_label_json[] = trim(preg_replace('/\s\s+/', ' ', $og['title']));

		$i++;
		$art_today_count .= $row['tweets_today'].',';
		$art_today_count_json[] = (int)$row['tweets_today'];
		$art_today_fenton .= floor(get_tweet_benchmark()).',';
		$art_today_fenton_json[] = (int) floor(get_tweet_benchmark());
	}

	$art_today_label  = substr($art_today_label,  0, strlen($art_today_label)  - 1);
	$art_today_count  = substr($art_today_count,  0, strlen($art_today_count)  - 1);
	$art_today_fenton = substr($art_today_fenton, 0, strlen($art_today_fenton) - 1);

	$chart_data = array('label'           => $art_today_label,
										  'today_value'     => $art_today_count,
										  'average_value'   => $art_today_fenton);

	if (!$mode == 'JSON')
		return $chart_data;
	else
		return array($art_today_label_json, $art_today_count_json, $art_today_fenton_json);
}

// not used ATM
function tweets_per_day_stacked($mode = '')
{
	$today_tweets = mysql_fetch_array(mysql_query('select count(*) as tweets_today from tweets where date(created_at) = curdate() '));
	// vergelijk met de vorige 4 weken ...
	$i = 4;
	$x_week_ago = array();
	$x_week_ago_later = array();
	$labels = '';
	$label_json = array();
	while ($i > 0)
	{
		$x_week_ago[] = mysql_fetch_array(mysql_query(
			'select count(*) as tweets_then
			   from tweets
			  where date(created_at) = date_sub(curdate(), interval '.$i. ' week) and time(created_at) <= curtime() '));
		$x_week_ago_later[] = mysql_fetch_array(mysql_query(
			'select count(*) as tweets_then
			   from tweets
			  where date(created_at) = date_sub(curdate(), interval '.$i. ' week) and time(created_at) > curtime() '));
		$labels .= '"'.date('Y-m-d', time() - 7 * $i * 24 * 60 * 60) .'",';
		$label_json[] = date('Y-m-d', time() - 7 * $i * 24 * 60 * 60);
		$i--;
	}
	$labels .= '"'.date('Y-m-d').'"';
	$label_json[] = date('Y-m-d');

	// voor highcharts een stacked column, 5 columns
	$i = 0;
	$tot_till_now = '';
	$tot_stack    = '';
	foreach($x_week_ago as $x)
	{
		$tot_till_now .= $x['tweets_then'].',';
		$till_now_json[] = $x['tweets_then'];
		$tot_stack    .= $x_week_ago_later[$i]['tweets_then'].',';
		$stack_json[] = $x_week_ago_later[$i]['tweets_then'];
		$i++;
	}
	$tot_till_now .= $today_tweets['tweets_today'];
	$till_now_json[] = $today_tweets['tweets_today'];

	$tot_stack = substr($tot_stack, 0, strlen($tot_stack) - 1);

	$chart_data = array('label'    => $labels,
											'till_now' => $tot_till_now,
											'stack'    => $tot_stack);
	if (!$mode == 'JSON')
		return $chart_data;
	else
		return array($label_json, $till_now_json, $stack_json);
}