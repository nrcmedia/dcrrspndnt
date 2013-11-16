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
	$tot_tweets_res = mysql_query('select count(tweets.id) as tweet_count, day(tweets.created_at) as dag, month(tweets.created_at) as maand from tweets where created_at > "2013-10-13 21:00"  group by maand, dag order by year(tweets.created_at) desc, month(tweets.created_at) desc, day(tweets.created_at) desc limit 0,30');

	$label = array();
	$tweets = array();
	$rows = array();

	while ($row = mysql_fetch_array($tot_tweets_res))
	{
		$rows[] = $row;
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
	$bar_tweet_data = substr($bar_tweet_data, 0, strlen($bar_tweet_data) - 1);

	$chart_data = array('data' => $bar_tweet_data, 'label' => $bar_label);
	if (!$mode == 'JSON')
		return $chart_data;
	else
		return array($label, $tweets);
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