<?php
$microtimer_start = microtime(true);
session_start();

include_once '../lib/dbobject.php';
include_once '../lib/template.php';
include_once '../lib/familize.php';
include_once '../lib/userobject.php';
include_once '../lib/userForm.php';
include_once '../lib/photoForm.php';

$db = new DBObject();
$famu = new Familize($db);
$famu->loginCheck();
$tmpl = new $famu->template($famu);
$tmpl->title = "User";
$tmpl->pageHeader();

// get url variables
list($user_id, $page, $offset) = $_REQUEST['url'];
$offset = ($offset=='')  ? 0 : max(0, $offset-1);

// set user object
$uobj = new UserObject($db, $user_id);
$user = new UserForm($db, $uobj, $tmpl);
$name = $uobj->getUserName();

// load form
switch ($page) {
case 'favorites':
	$limit = 48;
	$phto = new PhotoForm($db, $uobj, $tmpl);
	$offs = $phto->setFavorites($limit, $offset*$limit);
	$phto->setPhotoSet('in_faves', $uobj->active_id, $offs);
	$form .= $phto->getThumbGallery(8);
	$tmpl->setPageList($phto->getPageList($phto->photo_count, $limit, $offs, "/user/{$uobj->active_id}/favorites/"));
	$output .= $tmpl->titledPage("{$name}'s {$page}", $form);
	break;

case 'featured':
	$limit = 48;
	$phto = new PhotoForm($db, $uobj, $tmpl);
	$offs = $phto->setFeatured($limit, $offset*$limit);
	$phto->setPhotoSet('in_feat', $uobj->active_id, $offs);
	$form .= $phto->getThumbGallery(8);
	$tmpl->setPageList($phto->getPageList($phto->photo_count, $limit, $offs, "/user/{$uobj->active_id}/featured/"));
	$output .= $tmpl->titledPage("{$name} featured in photos", $form);
	break;

case 'photos':
	$limit = 48;
	$phto = new PhotoForm($db, $uobj, $tmpl);
	$offs = $phto->setUserPhotos($limit, $offset*$limit);
	$phto->setPhotoSet('in_user', $uobj->active_id, $offs);
	$form .= $phto->getThumbGallery(8);
	$tmpl->setPageList($phto->getPageList($phto->photo_count, $limit, $offs, "/user/{$uobj->active_id}/photos/"));
	$title = (substr($name, -1)=='s') ? "{$name}' {$page}" : "{$name}'s {$page}";
	$output .= $tmpl->titledPage($title, $form);
	break;

case 'editfamily':
	// if the user is not a moderator, skip down to the next section
	if ($famu->moderator) {
		$form .= $user->editFamily();
		$title = (substr($name, -1)=='s') ? "Edit {$name}' family" : "Edit {$name}'s family";
		$output .= $tmpl->titledPage($title, $form);
		break;
	}
case 'profile':
default:
	$page = 'profile';
	$form .= $user->getProfile($famu->moderator);
	$title = (substr($name, -1)=='s') ? "{$name}' {$page}" : "{$name}'s {$page}";
	$output .= $tmpl->titledPage($title, $form);
}

$head = $user->getHeader($page);
print $tmpl->threeColumnLayout($head, $output, '', 'page');

//print $tmpl->basicPage($head.$output);
$tmpl->pageFooter();

?>