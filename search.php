<?php
require_once('settings.local.php');
require_once('functions.php');
include('db.php');

if (! isset($_GET['q']))
{
	header('Location: ./ ');
}
$search_string = mysql_real_escape_string($_GET['q']);
$display_string = htmlspecialchars($_GET['q']);
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>nrc.nl, zoekresultaten</title>
		<link rel="stylesheet" href="./style2.css" />
	</head>
	<body id="meta_art">
		<h1>Artikelen die "<?php echo $display_string;?>" bevatten</h1>
		<div class="center">
<?php

include('menu.php');
$th_pubdate = '<th>Gepubliceerd</th>';
$th_tweets = '<th>tweets</th>';
$th_facebook = '<th>FB</th>';

$th = $th_pubdate.'<th>Titel / Artikel</th><th>Auteur</th><th>Sectie</th>'.$th_tweets.$th_facebook;

$res = mysql_query('select *, count(tweets.id) as tweet_count, facebook.total_count as fb_total, facebook.share_count as fb_share, facebook.like_count as fb_like, facebook.comment_count as fb_comment, twitter.twitter_count as twitter_alltime from meta left join meta_artikel on meta_artikel.meta_id = meta.id left join artikelen on artikelen.id = meta_artikel.art_id left outer join tweets on tweets.art_id = artikelen.id left outer join facebook on facebook.art_id = artikelen.id  left join twitter on twitter.art_id = artikelen.id where waarde like "%'.$search_string.'%" and NOT artikelen.id IS NULL group by artikelen.id ');

show_table($res, $th);

include('search_box.php')
?>
</div>
<?php include('footer.php') ?>
</body>
</html>