<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="author" content="xiffy">
		<title>nrc.nl, de artikelen volgens twitter</title>
		<link rel="stylesheet" href="./style2.css" />
	</head>
	<body>


<?php
require_once('settings.local.php');
require_once('functions.php');
include('db.php');
$count_res = mysql_query('select count(*) as amount from artikelen');
$count_arr = mysql_fetch_array($count_res);
$tot_row = $count_arr['amount'];
$start = 0;
if(isset($_GET['page']))
{
	$page = (int)$_GET['page'];
	$start = ($page - 1) * ITEMS_PER_PAGE;
	if($start < 0) $start = 0;
}
// sorteren kan op 'vinddatum' of aantal tweets
$order_by = ' order by created_at desc ';
$qsa = '';
$th_pubdate = '<th>Gepubliceerd</th>';
$sep = strstr($_SERVER['REQUEST_URI'], '?') ? '&amp;' : '?';
$th_tweets = '<th class="sortable"><a href="'.$_SERVER['REQUEST_URI'].$sep.'order=tweets" title="Sorteer op aantal maal gedeeld" >tweets</a>&#9660;</th>';
$th_facebook = '<th class="sortable"><a href="'.$_SERVER['REQUEST_URI'].$sep.'order=fb" title="Sorteer op aantal Facbook shares">FB</a>&#9660;</th>';

if(isset($_GET['order']) && $_GET['order'] == 'tweets')
{
	$order_by = ' order by tweet_count desc ';
	$qsa = '&amp;order=tweets'; // voor de pager
	$th_pubdate = '<th class="sortable"><a href="./?page='.$page.'" title="Sorteer op publicatiedatum">Gepubliceerd</a>&#9660;</th>';
	$th_tweets = '<th>tweets</th>';
	$th_facebook = '<th class="sortable"><a href="'.$_SERVER['REQUEST_URI'].$sep.'order=fb" title="Sorteer op aantal Facbook shares">FB</a>&#9660;</th>';
}
elseif(isset($_GET['order']) && $_GET['order'] == 'fb')
{
	$order_by = ' order by facebook.total_count desc ';
	$qsa = '&amp;order=fb'; // voor de pager
	$th_pubdate = '<th class="sortable"><a href="./?page='.$page.'" title="Sorteer op publicatiedatum">Gepubliceerd</a>&#9660;</th>';
	$th_tweets = '<th class="sortable"><a href="'.$_SERVER['REQUEST_URI'].$sep.'order=tweets" title="Sorteer op aantal maal gedeeld" >tweets</a>&#9660;</th>';
	$th_facebook = '<th>FB</th>';
}

$i = 0;
$res = mysql_query('select artikelen.*, count(tweets.id) as tweet_count, facebook.total_count as fb_total, facebook.share_count as fb_share, facebook.like_count as fb_like, facebook.comment_count as fb_comment from artikelen left outer join tweets on tweets.art_id = artikelen.id left outer join facebook on facebook.art_id = artikelen.id group by artikelen.id '.$order_by.' limit '.$start.','.ITEMS_PER_PAGE);
?>
		<h1>Artikelen van <a href="http://www.nrc.nl/">nrc.nl</a> gevonden op Twitter</h1>
<?php include ('menu.php'); ?>
		<div class="center">
<?php
$th = $th_pubdate.'<th>Titel / Artikel</th><th>Auteur</th><th>Sectie</th>'.$th_tweets.$th_facebook;

show_table($res, $th);

	pager($tot_row, $qsa);
?>

<?php include('search_box.php') ?>
	</div>
<?php include('footer.php') ?>
</body>
<?php @include('ga.inc.php') ?>

</html>
