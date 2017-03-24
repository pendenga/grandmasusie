<?php
header ('content-type: text/xml');
print <<<EOD
<rss version="2.0">
   <channel>
      <title>GrandmaSusie.com</title>
      <link>http://www.grandmasusie.com/home/</link>
      <description>The online presence for Susan Anderson and her descendants.</description>
      <language>en-us</language>
      <docs>http://backend.userland.com/rss</docs>
      <generator>PHP/$phpversion</generator>
EOD;

// set the file's content type and character set
// this must be called before any output
header("Content-Type: text/xml;charset=utf-8");

// display RSS 2.0 channel information

print <<<EOD
EOD;

include '../lib/dbobject.php';
$db = new DBObject();
$rs = $db->do_sql("call recentPhoto(1, 49, 0)");
$phpversion = phpversion();


// loop through the array pulling database fields for each item
foreach ($rs as $photo) {
	$caption = "{$photo['first_name']} {$photo['last_name']}: ".htmlspecialchars(stripslashes($photo['caption']));
	$comment = ($photo['comments']==1) ? '1 comment' : "{$photo['comments']} comments";
	$descrip = htmlspecialchars(stripslashes("<img align=\"left\" src=\"http://{$_SERVER['HTTP_HOST']}/static/{$photo['server_id']}/{$photo['photo_uid']}_m.{$photo['ext']}\"/><p>{$photo['description']}</p><p>{$comment}"));
	$pubDate = date("r", strtotime($photo['uploaded_dt'])-(7*3600));
	print <<<EOD
	<item>
		<title>{$caption}</title>
		<link>http://www.grandmasusie.com/viewphoto/{$photo['photo_id']}</link>
		<description>{$descrip}</description>
		<pubDate>{$pubDate}</pubDate>
		<guid isPermaLink="true">http://www.grandmasusie.com/viewphoto/{$photo['photo_id']}</guid>
	</item>
EOD;
}

?>
   </channel>
</rss>
