<?php

$microtimer_start = microtime(true);
session_start();
include_once '../lib/dbobject.php';
include_once '../lib/template.php';
include_once '../lib/familize.php';

header('content-type: text/xml');
print "<response><!-- GrandmaSusie.com - ";
print date("Y/m/d h:i:s");

list($username, $password, $old) = $_REQUEST['url'];

$db = new DBObject();
$famu = new Familize($db);
try {
	$famu->authorize($username, $password, false);
	$blogs = $famu->getUnseenBlogCount();
	$photo = $famu->getUnseenPhotoCount();
	if ($photo > 0) {
		$pho = $famu->getUnseenPhoto(1);
	} elseif ($old=='F') {
		$pho = $famu->getFavoritePhoto(1);
	} elseif ($old=='N') {
		$pho = $famu->getRecentPhoto(1);
	} else { // old=R
		$pho = $famu->getRandomPhoto(1);
	}
	$caption = htmlspecialchars($pho['caption']);
	$output = <<<EOD
	<unseen id="unseen">
		<blogs>{$blogs}</blogs>
		<photos>{$photo}</photos>
	</unseen>
	<photos>
		<photo id="newest" uid="{$pho['photo_uid']}" owner="{$pho['user_id']}" server="{$pho['server_id']}" ext="{$pho['ext']}" title="{$caption}" comments="{$pho['comments']}" comment_dt="{$pho['newest']}" />
	</photos>
EOD;

} catch (User_InvalidException $e) {
	$error[] = '<error code="101">'.htmlspecialchars($e->getMessage()).'</error>';
} catch (Exception $e) {
	$error[] = '<error code="100">'.htmlspecialchars($e->getMessage()).'</error>';
}

// format error output
$errCnt = count($error);
if ($errCnt >0) {
	$errStr = implode("\n\t", $error);
}

print <<<EOD
-->
<error count="{$errCnt}">{$errStr}</error>
{$output}
</response>
EOD;

?>