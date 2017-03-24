<?php
$microtimer_start = microtime(true);
session_start();

include_once '../lib/dbobject.php';
include_once '../lib/template.php';
include_once '../lib/familize.php';
include_once '../lib/photoForm.php';

$db = new DBObject();
$famu = new Familize($db);
$famu->loginCheck();
$tmpl = new $famu->template($famu);
$phto = new PhotoForm($db, $famu, $tmpl);

// get url variables
list($photo_id, $offset) = $_REQUEST['url'];
// convert to zero-indexed offset
$offset = ($offset=='')  ? 0 : max(0, $offset-1);

// load signin page
$tmpl->title = "Favorites";
$tmpl->pageHeader();


// set title
$rs = $db->do_sql("SELECT f.photo_id, f.updated_dt, f.user_id, u.full_name, u.common_name, u.first_name, u.last_name, u.avatar_id FROM photo_favorite f INNER JOIN user_name u ON f.user_id=u.user_id WHERE photo_id={$photo_id} ORDER BY f.updated_dt DESC");
$favCount = count($rs);
$title = "{$favCount} people count this photo as a favorite";

// set instructions
foreach ($rs as $user) {
	$avtr = $famu->getFullAvatarLink($user['avatar_id'], $user['user_id'], $user['full_name']);
	$time = GTools::postTime($user['updated_dt']);
	$form .= <<<EOD
	{$avtr}
	<div><strong>{$user['full_name']}</strong> added this as a favorite {$time}.</div>
	<div class="userLinks">>> See <a href="/user/{$user['user_id']}/favorites/">{$user['common_name']}'s other favorites</a></div>
	<br class="clearfix"/>	
EOD;
}

// set photo
$phto->setPhoto($photo_id);
$purl = $phto->photo->getUrl_small();
$instructions = <<<EOD
	<div class="photoFrame">
		<a href="/viewphoto/{$photo_id}/"><img src="{$purl}" border="0"/></a>
	</div>
EOD;

$output = $tmpl->titledPage($title, $form);
print $tmpl->threeColumnLayout($output, $instructions,'');
$tmpl->pageFooter();

?>