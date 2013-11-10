<!DOCTYPE html>
<?php
require_once('settings.local.php');
require_once('functions.php');
include('db.php');

$meta_id = (int)$_GET['id'];
$meta_res = mysql_query('select * from meta where ID = '.$meta_id);
$meta_row = mysql_fetch_array($meta_res);
// determine in what mode we are running; Author or Section?
$mode = explode(':', $meta_row['type']);
$mode = isset($mode[1]) ? $mode[1] : $meta_row['type'];
$title_by_in = $mode == 'author' ? 'door' : 'in de sectie';
$th_extra = $mode == 'author' ? '<th>sectie</th>' : '<th>auteur</th>';
$th_related = $mode == 'author' ? 'auteurs' : 'secties';
$extra_query_var = $mode == 'author' ? 'article:section' : 'article:author';

// paging dr. beat:
$count_res = mysql_query('select count(artikelen.id) as amount from artikelen join meta_artikel on artikelen.ID = meta_artikel.art_id where meta_artikel.meta_id = '.$meta_id);
$count_arr = mysql_fetch_array($count_res);
$tot_row = $count_arr['amount'];
$start = 0;
if(isset($_GET['page']))
{
	$page = (int)$_GET['page'];
	$start = ($page - 1) * ITEMS_PER_PAGE;
	if($start < 0) $start = 0;
}

$order_by = ' order by created_at desc ';
$qsa = '&amp;id='.$meta_id;
$th_pubdate = '<th>Gepubliceerd</th>';
$sep = strstr($_SERVER['REQUEST_URI'], '?') ? '&amp;' : '?';
$th_tweets = '<th class="sortable"><a href="'.$_SERVER['REQUEST_URI'].$sep.'order=tweets" title="Sorteer op aantal maal gedeeld" >tweets</a>&#9660;</th>';

if(isset($_GET['order']) && $_GET['order'] == 'tweets')
{
	$order_by = ' order by tweet_count desc ';
	$qsa .= '&amp;order=tweets'; // voor de pager
	$th_pubdate = '<th class="sortable"><a href="./meta_art.php?id='.$meta_id.'&amp;page='.$page.'" title="Sorteer op publicatiedatum">Gepubliceerd</a>&#9660;</th>';
	$th_tweets = '<th>tweets</th>';
}



$th = $th_pubdate.'<th>Title / Article</th>'.$th_extra.$th_tweets.'<th>FB</th>';

?>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="author" content="xiffy">

		<title>nrc.nl, artikelen <?php echo $title_by_in; ?>: <?php echo $meta_row['waarde'].' ('.$mode.')';?></title>
		<link rel="stylesheet" href="./style2.css" />
		<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
		<script src="highcharts.js"></script>

	</head>
	<body id="meta_art">
		<h1>Artikelen geschreven <?php echo $title_by_in; ?>: <?php echo $meta_row['waarde']?></h1>
<?php include('menu.php')?>
		<div class="clear"></div>

<?php
$res = mysql_query ('select artikelen.*, count(tweets.id) as tweet_count, facebook.total_count as fb_total, facebook.share_count as fb_share, facebook.like_count as fb_like, facebook.comment_count as fb_comment, twitter.twitter_count as twitter_alltime  from artikelen join meta_artikel on artikelen.ID = meta_artikel.art_id left outer join tweets on tweets.art_id = artikelen.ID left outer join facebook on facebook.art_id = artikelen.id left join twitter on twitter.art_id = artikelen.id where meta_artikel.meta_id = '.$meta_id.' group by artikelen.ID '.$order_by.' limit '.$start.','.ITEMS_PER_PAGE);

if($mode == 'author')
	$fields = array('pubdate', 'title', 'section', 'tweets', 'fb_count');
else
	$fields = array('pubdate', 'title', 'author', 'tweets', 'fb_count');

$extra_class = ' class="meta-table"';
show_table($res, $th, false, $fields, $extra_class);

pager($tot_row, $qsa);

// grafiekje tweets per dag over deze meta (auteur of sectie)
$graph_res = mysql_query("select count(tweets.id) as tweet_count, day(tweets.created_at) as dag, month(tweets.created_at) as maand from artikelen join meta_artikel on artikelen.ID = meta_artikel.art_id left outer join tweets on tweets.art_id = artikelen.ID where meta_artikel.meta_id = {$meta_id} and not day(tweets.created_at) is null group by year(tweets.created_at), maand, dag order by year(tweets.created_at) desc, month(tweets.created_at) desc, day(tweets.created_at) desc limit 0,30");

$rows = array();
while ($row = mysql_fetch_array($graph_res))
{
	$rows[] = $row;
}
$rows = array_reverse($rows);


$high = 0;
$bar_label = '';
$bar_tweet_data = '';
foreach($rows as $row)
{
	$bar_label .= $row['dag'].',';
	$bar_tweet_data .= $row['tweet_count'].',';
	$high = max($high, $row['tweet_count'] + 10);
}
$scaleWidth = ceil($high / 10);
$bar_label = substr($bar_label, 0, strlen($bar_label) - 1);
$bar_tweet_data = substr($bar_tweet_data, 0, strlen($bar_tweet_data) - 1);
?>

			<div class="meta_graph" style="float:left;">

			<div id="tot_tweets" style="height:450px; width:650px"></div>
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
            plotOptions: {
            	column: {
            		pointPadding: 0,
            		borderWidth: 0
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
            series: [{
            		name: 'Tweets',
                data: [<?php echo $bar_tweet_data;?>]

            }]
        });
      });
			</script>
		</div>
		</div>

		<table class="related">
			<tr><th>Alle <?php echo $th_related;?></th></tr>
<?php
			$i = 0;
			$metatype_res = mysql_query('select * from meta where meta.type = "'.$meta_row['type'].'" order by waarde');
			while($rel_row = mysql_fetch_array($metatype_res))
			{
				?>
				<tr <?php if($i % 2 == 1) echo 'class="odd"'?>>
					<td><a href="./meta_art.php?id=<?php echo $rel_row['ID']?>"><?php echo $rel_row['waarde'] ?></a></td>
				</tr>
				<?php
				$i++;
			}
?>
		</table>
		<div class="center">
<?php include('search_box.php'); ?>
		</div>
	</div>
<?php include('footer.php') ?>
	</body>
</html>