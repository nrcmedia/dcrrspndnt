<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="author" content="xiffy">
		<title>nrc.nl, de artikelen - populair op twitter</title>
		<link rel="stylesheet" href="./style2.css" />
	</head>
	<body>


<?php
require_once('settings.local.php');
require_once('functions.php');
include('db.php');

$start = 0;
$qsa = '';
$th_pubdate = '<th>Gepubliceerd</th>';
$sep = strstr($_SERVER['REQUEST_URI'], '?') ? '&amp;' : '?';
$th_tweets = '<th>tweets</th>';
$th_facebook = '<th>FB</th>';
$order_by = ' order by tweet_count desc ';

$mode = '';
$title = 'Populaire artikelen volgens twitter (all time)';

if(isset($_GET['mode']))
{
	$mode = $_GET['mode'];
	switch($mode)
	{
		case 'hour':
			$mode = ' where tweets.created_at > date_add(now(), interval -60 minute) ';
			$title = 'Populaire artikelen volgens twitter (laatste uur)';
			if (isset($_GET['disposition']))
			{
				$disp = (int) $_GET['disposition'];
				$low = ($disp + 1) * 60;
				$high = $disp * 60;
				$mode = 'where tweets.created_at > date_add(now(), interval -'.$low.' minute) and tweets.created_at < date_add(now(), interval -'.$high.' minute) ';
				$title = 'Populaire artikelen volgens twitter ('.$disp.' uur geleden)';
			}
			break;
		case 'day':
			$mode = ' where tweets.created_at > date_add(now(), interval -24 hour) ';
			$title = 'Populaire artikelen op \'nrc.nl\' volgens twitter (afgelopen 24 uur)';
			if (isset($_GET['disposition']))
			{
				$disp = (int) $_GET['disposition'];
				$low = ($disp + 1) * 24;
				$high = $disp * 24;
				$mode = 'where tweets.created_at > date_add(now(), interval -'.$low.' hour) and tweets.created_at < date_add(now(), interval -'.$high.' hour) ';
				$title = 'Populaire artikelen volgens twitter ('.$disp.' dag geleden)';
			}

			break;
		case 'week':
			$mode = ' where tweets.created_at > date_add(now(), interval -7 day) ';
			$title = 'Populaire artikelen volgens twitter ('.$disp.' week)';
			break;
		default:
			$mode = '';
	}
}


$res = mysql_query('select artikelen.*, count(tweets.id) as tweet_count, facebook.total_count as fb_total, facebook.share_count as fb_share, facebook.like_count as fb_like, facebook.comment_count as fb_comment, twitter.twitter_count as twitter_alltime from artikelen left outer join tweets on tweets.art_id = artikelen.id left outer join facebook on facebook.art_id = artikelen.id  left join twitter on twitter.art_id = artikelen.id '.$mode.' group by artikelen.id having tweet_count > 0 '.$order_by.' limit '.$start.',50');

$th = $th_pubdate.'<th>Titel / Artikel</th><th>Auteur</th><th>Sectie</th>'.$th_tweets.$th_facebook;
?>


		<h1><?php echo $title;?></h1>
<?php include ('menu.php'); ?>
		<div class="center">

<?php
show_table($res, $th, true);

pager($tot_row, $qsa);

include('search_box.php')
?>
	</div>
<?php include('footer.php') ?>
	</body>
</html>