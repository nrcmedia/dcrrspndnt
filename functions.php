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
									  $fields = array('pubdate', 'title', 'author', 'section', 'tweets', 'fb_count'),
									  $extra_class = '')
{
	?>
		<table<?php echo $extra_class;?>>
			<tr>
				<?php echo $table_header;?>
			</tr>
<?php
while($row = mysql_fetch_array($res) )
{
	$og = unserialize(stripslashes($row['og']));
	$titel = isset($og['title']) ? $og['title'] : substr($row['clean_url'],18,50);
	$description = isset($og['description']) ? $og['description'] : 'Een mysterieus artikel';
	$auth_res = mysql_query('select * from meta where meta.waarde = "'.$og['article:author'].'" and meta.type="article:author"');
	$author = mysql_fetch_array($auth_res);
	$section_res = mysql_query('select * from meta where meta.waarde = "'.$og['article:section'].'" and meta.type="article:section"');
	$section = mysql_fetch_array($section_res);
	$display_time = isset($og['article:published_time']) ? strftime('%e %b %H:%M', $og['article:published_time']) : substr($row['created_at'],8,2).'-'.substr($row['created_at'],5,2).' '.substr($row['created_at'],11,5);
	if(isset($og['article:published_time']) && $og['article:published_time'] < time() - 360 * 24 * 60 * 60)
	$display_time = strftime('%e %b %Y', $og['article:published_time']);
	$found_at = substr($row['created_at'],8,2).'-'.substr($row['created_at'],5,2).' '.substr($row['created_at'],11,5);
	$fb_abbr = 'Facebook, likes: '.$row['fb_like'].' shares: '.$row['fb_share'].' comments: '.$row['fb_comment'];
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
				<td align="right"><?php echo $row['tweet_count']?></td>
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
?>
		</table>
<?php

}