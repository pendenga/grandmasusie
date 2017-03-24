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
list($page, $offset, $header) = $_REQUEST['url'];
// convert to zero-indexed offset
$offset = ($offset=='')  ? 0 : max(0, $offset-1);
$limit = 49;

// load signin page
if ($header == '') {
	$tmpl->title = "Photos";
	$tmpl->pageHeader();

	$instructions = <<<EOD
		<ul>
		<li><a href="/gallery/thumb/">All Photos</a> <span class="detail">All photos ordered with the most recently uploaded photos first.</span></li>
		<li><a href="/gallery/detail/">All (Detailed)</a> <span class="detail">All photos in a list view with more information about each photo ...but less photos per page.</span></li>
		<li><a href="/gallery/favorite/">Favorite Photos</a> <span class="detail">All photos marked by anyone as a favorite.  Ordered by the number of people who call it a favorite.</span></li>
		<li><a href="/gallery/group/">Group Photos</a> <span class="detail">All photos with four or more people identified in them. Ordered with the photos featuring the most people first.</span></li>
		<li><a href="/gallery/topcomment/">Most Comments</a> <span class="detail">All photos on which have at least ten comments.  Ordered by the number of comments left on each photo.</span></li>
		<li><a href="/gallery/newcomment/">Unseen Comments</a> <span class="detail">All recent photos on which you haven't read the comments</span></li>
		</ul>
EOD;
	//<li><a href="/gallery/hotfave/">Hot Favorites</a> <span class="detail">Favorite photos marked in the last month</span></li>
}

// load form
switch ($page) {
case 'favorite':
case 'favorites':
	$offs = $phto->setAllFavorites($limit, $offset*$limit);
	$phto->setPhotoSet('in_gallery', 'favorite', $offs);
	$form = $phto->getThumbGallery();
	$tmpl->setPageList($phto->getPageList($phto->photo_count, $limit, $offs, '/gallery/favorite/'));
	print $tmpl->formWithInstructions('Favorite Photos', $form, $instructions);
	break;	
case 'faves':
	$offs = $phto->setFavorites($limit, $offset*$limit);
	$form = $phto->getThumbGallery();
	$tmpl->setPageList($phto->getPageList($phto->photo_count, $limit, $offs, '/gallery/faves/'));
	print $tmpl->formWithInstructions('Your Favorites', $form, $instructions);
	break;
case 'flagged':
	$offs = $phto->setFlagged($limit, $offset*$limit);
	$form = $phto->getThumbGallery();
	$tmpl->setPageList($phto->getPageList($phto->photo_count, $limit, $offs, '/gallery/flagged/'));
	print $tmpl->formWithInstructions('Flagged Photos', $form, $instructions);
	break;
case 'group':
	$offs = $phto->setGroupPhotos(4, $limit, $offset*$limit);
	$phto->setPhotoSet('in_gallery', 'group', $offs);
	$form = $phto->getThumbGallery();
	$tmpl->setPageList($phto->getPageList($phto->photo_count, $limit, $offs, '/gallery/group/'));
	print $tmpl->formWithInstructions('Group Photos', $form, $instructions);
	break;
case 'tags':
	$offs = $phto->setGroupPhotos(4, $limit, $offset*$limit);
	$phto->setPhotoSet('in_gallery', 'group', $offs);
	$form = $phto->getThumbGallery();
	$tmpl->setPageList($phto->getPageList($phto->photo_count, $limit, $offs, '/gallery/group/'));
	print $tmpl->formWithInstructions('Group Photos', $form, $instructions);
	break;
case 'user':
	$offs = $phto->setUserPhotos($limit, $offset*$limit);
	$form = $phto->getThumbGallery();
	$tmpl->setPageList($phto->getPageList($phto->photo_count, $limit, $offs, '/gallery/user/'));
	print $tmpl->formWithInstructions('Your Photos', $form, $instructions);
	break;
case 'newcomment':
	$offs = $phto->setCommentsUnseen($limit, $offset*$limit);
	$phto->setPhotoSet('in_gallery', 'unseen', $offs);
	$form = $phto->getThumbGallery();
	//$form = $phto->getGallery();
	$tmpl->setPageList($phto->getPageList($phto->photo_count, $limit, $offs, '/gallery/newcomment/'));
	print $tmpl->formWithInstructions('Unseen Comments', $form, $instructions);
	break;
case 'topcomment':
	$offs = $phto->setTopComments(10, $limit, $offset*$limit);
	$form = $phto->getThumbGallery();
	$tmpl->setPageList($phto->getPageList($phto->photo_count, $limit, $offs, '/gallery/topcomment/'));
	print $tmpl->formWithInstructions('Most Comments', $form, $instructions);
	break;	
case 'detail':
	$limit = 16;
	$offs = $phto->setRecent($limit, $offset*$limit);
	$form = $phto->getGallery();
	$tmpl->setPageList($phto->getPageList($phto->photo_count, $limit, $offs, '/gallery/detail/'));
	print $tmpl->formWithInstructions('All (Detailed)', $form, $instructions);
	break;
case 'thumb':
default:
	$offs = $phto->setRecent($limit, $offset*$limit);
	$phto->setPhotoSet('gallery', 'recent', $offs);
	$form = $phto->getThumbGallery();
	$tmpl->setPageList($phto->getPageList($phto->photo_count, $limit, $offs, '/gallery/thumb/'));
	print $tmpl->formWithInstructions('All Photos', $form, $instructions);
}

if ($header == '') {
	$tmpl->pageFooter();
}
?>
