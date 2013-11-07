<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="author" content="xiffy">
		<title>nrc.nl, de tweets in grafieken</title>
		<link rel="stylesheet" href="./style2.css" />
		<link rel="alternate" type="application/rss+xml" title="Artikelen van nrc.nl" href="./rss.php">
		<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
		<script src="highcharts.js"></script>
	</head>
	<body>


<?php
require_once('settings.local.php');
require_once('functions.php');
include('db.php');
$tot_tweets_res = mysql_query('select count(tweets.id) as tweet_count, day(tweets.created_at) as dag, month(tweets.created_at) as maand from tweets where created_at > "2013-10-13 21:00"  group by maand, dag order by year(tweets.created_at) desc, month(tweets.created_at) desc, day(tweets.created_at) desc limit 0,30');

$label = array();
$tweets = array();
$high = 0;
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
	$tweets[] = $row['tweet_count'];
	$high = max($high, $row['tweet_count'] + 10);
}
$scaleWidth = ceil($high / 10);

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

// Grafiek 2;
// Tweets per uur en de dagtrend daar op afgezet
$dagen_res = mysql_query("select day(tweets.created_at) as dagen from tweets group by dagen");
$dagen = mysql_num_rows($dagen_res);

$graph_res = mysql_query("select count(tweets.id) as tweet_count, hour(tweets.created_at) as the_uur from tweets  group by the_uur ");

$high = 0;
$hour_label = '';
$hour_tweet_data = '';
$uur_nu = date('H');
$minuut_nu = date('i');

while ($row = mysql_fetch_array($graph_res))
{
	$hour_label .= $row['the_uur'].',';
	$deler = (int)$row['the_uur'] > (int)$uur_nu ? $dagen - 1 : $dagen;
	$tot = ceil($row['tweet_count'] / $deler);
	$hour_tweet_data .= $tot.',';
	$high = max($high, $tot + 10);
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
		$i++;
	}
	$high = max($high, $row['per_hour'] + 10);
	$hour_today_data .= $row['per_hour'].',';
	$i++;
	if( (int)$row['the_hour'] == (int)$uur_nu)
	{ // make projection; 12 times per hour, which time are we?

		$hour_part = floor($minuut_nu / 5) + 1;

		$projection = floor((12 / $hour_part) * (int)$row['per_hour']);

		$j = 0;
		while($j < (int)$uur_nu) // naar de juiste plek brengen ...
		{
			$projection_data .= '0,';
			$j++;
		}
		$projection_data .= $projection;
	}
}

$hour_today_data = substr($hour_today_data, 0, strlen($hour_today_data) - 1);
$scaleWidth2 = ceil($high / 10);


//
// Grafiek 3,
// Tweets per 5 minuten, vandaag
// vergelijken met gisteren
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
 order by created_at
");
// vierde lijn, het gemiddelde van alle dagen op die minuut ....
$avg_res = mysql_query('select avg(tweet_count) as per_minute,
 minute(created_at) as the_minute,
 hour(created_at) as the_hour,
 created_at
from  (select count(*) as tweet_count, created_at
       from tweets
       group by year(created_at), month(created_at), day(created_at), hour(created_at), minute(created_at)
) temp_table
group by hour(created_at), minute(created_at)
order by hour(created_at), minute(created_at)');

// labels klaarzetten
$labels = array();
$values = array();
$comp_values = array();
$avg_values = array();
// draaien om 24 uur vol te krijgen
$hour = 0;
while($hour < 24)
{
	$str_hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
	$minute = 0;
	while($minute < 60)
	{
		$str_minute = str_pad($minute,2, '0', STR_PAD_LEFT);
		if($minute == 0)
			$label = $str_hour.'.'.$str_minute;
		elseif($minute % 30 == 0)
			$label = $minute;
		else
			$label = '';

		$labels[$str_hour.':'.$str_minute] = $label;
		$values[$str_hour.':'.$str_minute] = 0;
		$comp_values[$str_hour.':'.$str_minute] = 0;
		$avg_values[$str_hour.':'.$str_minute] = 0;
		$minute = $minute + 5;
	}
	$hour++;
}
$tweets_pm_high = 0;
while($row = mysql_fetch_array($minute_res))
{
	$str_hour   = str_pad($row['the_hour'], 2, '0', STR_PAD_LEFT);
	$str_minute = str_pad($row['the_minute'], 2, '0', STR_PAD_LEFT);
	$values[$str_hour.':'.$str_minute] = $row['per_minute'];
	$tweets_pm_high = max($tweets_pm_high, $row['per_minute'] + 5);
}
while($comp_row = mysql_fetch_array($comp_minute_res))
{
	$str_hour   = str_pad($comp_row['the_hour'], 2, '0', STR_PAD_LEFT);
	$str_minute = str_pad($comp_row['the_minute'], 2, '0', STR_PAD_LEFT);
	$comp_values[$str_hour.':'.$str_minute] = $comp_row['per_minute'];
	$tweets_pm_high = max($tweets_pm_high, $comp_row['per_minute'] + 5);
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
	$tweets_per_minute_label .= '"'.$label.'",';
	$tweets_per_minute_value .= $values[$time].',';
	$comp_tweets_per_minute_value .= $comp_values[$time].',';
	$avg_tweets_per_minute_value .= $avg_values[$time].',';
	$i++;
}
$tweets_per_minute_value = substr($tweets_per_minute_value, 0, strlen($tweets_per_minute_value) - 1);
$tweets_per_minute_label = substr($tweets_per_minute_label, 0, strlen($tweets_per_minute_label) - 1);
$comp_tweets_per_minute_value = substr($comp_tweets_per_minute_value, 0, strlen($comp_tweets_per_minute_value) - 1);
$avg_tweets_per_minute_value = substr($avg_tweets_per_minute_value, 0, strlen($avg_tweets_per_minute_value) - 1);
$scalewidth3 = ceil($tweets_pm_high / 10);


//
// Grafiek; de artikelen van vandaag, totaal tweets en een 'benchmark'
// beperk tot de 30 beste artikelen van de dag
//
$art_res = mysql_query('
select count(tweets.id) as tweets_today, artikelen.*
	from artikelen
		left join tweets on tweets.art_id = artikelen.id
	join (
    select artikelen.id
			from artikelen
			left join tweets on tweets.art_id = artikelen.id
		where year(artikelen.created_at) = year(now() ) and month(artikelen.created_at) = month(now()) and day(artikelen.created_at) = day(now() )
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
if($num_arts ==0)
{
	$art_res = mysql_query('
	select count(tweets.id) as tweets_today, artikelen.*
		from artikelen
			left join tweets on tweets.art_id = artikelen.id
		join (
  	  select artikelen.id
				from artikelen
				left join tweets on tweets.art_id = artikelen.id
			where year(artikelen.created_at) = year(now() ) and month(artikelen.created_at) = month(now()) and 	day(artikelen.created_at) = day(now() ) - 1
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
$art_today_count = '';
$max_art_today = get_tweet_benchmark() + 4;
$i = 1;
$today_table_row = array();
while ($row = mysql_fetch_array($art_res))
{
	$og = unserialize($row['og']);
	//$art_today_label .= '"'.$i.'",';
	$art_today_label .= '"'.$og['title'].'",';

	$today_table_row[] = '<tr><td>'.$i.'</td><td>'.$og['title'].' ('.$row['tweets_today'].')</td></tr>';
	$i++;
	$art_today_count .= $row['tweets_today'].',';
	$max_art_today = max($max_art_today, $row['tweets_today'] + 5);
	$art_today_fenton .= floor(get_tweet_benchmark()).',';
}
$today_table_row[] = '<tr><td>*</td><td>Gemiddeld aantal tweets per artikel (alle artikelen): <strong>'.get_tweet_benchmark().'</strong></td></tr>';
$art_today_label  = substr($art_today_label,  0, strlen($art_today_label)  - 1);
$art_today_count  = substr($art_today_count,  0, strlen($art_today_count)  - 1);
$art_today_fenton = substr($art_today_fenton, 0, strlen($art_today_fenton) - 1);
$scalewidth4 = ceil($max_art_today / 10);
?>

		<h1>nrc.nl tweets in grafieken </h1>
<?php include ('menu.php'); ?>
		<div class="center full">

			<h2>Tweets per dag</h2>
			<div id="tot_tweets" style="z-index: -11; position:relative; margin: 0 10px" ></div>
			<script>
				$(function () {
        	$('#tot_tweets').highcharts({
            chart: { type: 'column' },
            title: { text: 'Totaal aantal tweets per dag' },
            xAxis: { categories: [<?php echo $bar_label;  ?>] },
            yAxis: {
                min: 0,
                title: {
                    text: 'Tweets per dag'
                }
            },
            tooltip: {
                headerFormat: '<table>',
                pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                    '<td style="padding:0"> <b>{point.y}</b> </td></tr>',
                footerFormat: '</table>',
                shared: true,
                useHTML: true
            },
            plotOptions: {
                column: {
                    pointPadding: 0.2,
                    borderWidth: 0
                }
            },
            series: [{
            		name: 'Tweets',
                data: [<?php echo $bar_tweet_data;?>]

            }]
        });
      });
			</script>
			<p>De laatste 30 dagen</p>

			<h2>Tweets per uur</h2>
			<div id="hour_tweets" style="z-index: -11; position:relative; margin: 0 10px"></div>
			<script>
				$(function () {
					$('#hour_tweets').highcharts({
						chart: { type: 'area' },
            title: { text: 'Tweets per uur' },
            xAxis: { categories: [<?php echo $hour_label;  ?>] },
            yAxis: { title: { text: 'Tweets per uur' },
                plotLines: [{ value: 0, width: 1, color: '#808080' }],
                min: 0
            },
            tooltip: {
                valueSuffix: ' tweets '
            },
            legend: {
                layout: 'vertical',
                align: 'right',
                verticalAlign: 'middle',
                borderWidth: 0
            },
            series: [{
                name: 'Gemiddeld',
                data: [<?php echo $hour_tweet_data;?>]
            }, {
                name: 'Vandaag',
                data: [<?php echo $hour_today_data;?>]
            }, {
            		name: 'Voorspelling',
            		data: [<?php echo $projection_data;?>]
            } ]
        	});
        });
       </script>

			<h2>Tweets vandaag</h2>
			<div id="tweets_pm" height="450" width="90%"></div>
			<script>
				$(function () {
					$('#tweets_pm').highcharts({
						chart: { type: 'area' },
            title: { text: 'Tweets per 5 minuten -vandaag, vorige week, gemiddeld-' },
            xAxis: { categories: [<?php echo $tweets_per_minute_label;  ?>] },
            yAxis: { title: { text: 'Tweets per 5 minuten' },
                plotLines: [{ value: 0, width: 1, color: '#808080' }],
                min: 0
            },
            tooltip: {
                valueSuffix: ' tweets '
            },
            legend: {
                layout: 'vertical',
                align: 'right',
                verticalAlign: 'middle',
                borderWidth: 0
            },
            series: [{
                name: 'Gemiddeld',
                data: [<?php echo $avg_tweets_per_minute_value;?>]
            }, {
            		name: 'Vorige week',
            		data: [<?php echo $comp_tweets_per_minute_value;?>]
            }, {
                name: 'Vandaag',
                data: [<?php echo $tweets_per_minute_value;?>]
            } ]
        	});
        });
       </script>

			<h2><?php echo $today_tweets_title;?></h2>
			<div id="today_tweets" style="height: 800px"></div>
			<script>
				$(function () {
					$('#today_tweets').highcharts({
						chart: { type: 'line' },
            title: { text: 'Meest getweete <?php echo $today_tweets_title;?>' },
            xAxis: { categories: [<?php echo $art_today_label;  ?>],
            				 labels: { rotation: -60, style: {fontSize: 11} }
            			 },
            yAxis: { title: { text: 'Tweets per 5 minuten' },
                plotLines: [{ value: 0, width: 1, color: '#808080' }],
                min: 0
            },
            tooltip: {
                valueSuffix: ' tweets '
            },
            legend: {
                layout: 'vertical',
                align: 'right',
                verticalAlign: 'middle',
                borderWidth: 0
            },
            series: [{
                name: 'Gemiddeld',
                data: [<?php echo $art_today_fenton;?>]
            }, {
                name: 'Vandaag',
                data: [<?php echo $art_today_count;?>]
            } ]
        	});
        });
       </script>

			<p>De hoogst scorende artikelen van vandaag, maximaal 25</p>

		</div>
<?php include('footer.php') ?>
	</body>
</html>