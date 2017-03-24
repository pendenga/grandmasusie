<?php
$microtimer_start = microtime(true);
session_start();

include_once '../lib/dbobject.php';
include_once '../lib/template.php';
include_once '../lib/familize.php';
include_once '../lib/blogForm.php';
include_once '../lib/photoForm.php';

$db = new DBObject();
$famu = new Familize($db);
$famu->loginCheck();
$tmpl = new $famu->template($famu);
$blog = new BlogForm($db, $famu, $tmpl);
$phto = new PhotoForm($db, $famu, $tmpl);

// get url variables
list($tag_text, $offset, $header) = $_REQUEST['url'];
// convert to zero-indexed offset
$offset = ($offset=='')  ? 0 : max(0, $offset-1);
$limit = 49;

// get tag id
$rs = $db->do_sql("SELECT * FROM tag WHERE tag='{$tag_text}'");
$tag_id = (count($rs) > 0) ? $rs[0]['tag_id'] : false;

if ($tag_id) {
	$offs = $phto->setTaggedPhotos($tag_id, $limit, $offset*$limit);
	$phto->setPhotoSet('tagged', $tag_text, $offs);
	$form = $phto->getThumbGallery(6);
	$tmpl->setPageList($phto->getPageList($phto->photo_count, $limit, $offs, "/tags/{$tag_text}/"));
} else {
	$form = "<h3>There are no tagged photos.</h3>";
}
$form .= $blog->taggedList($tag_id);

// print output
$tmpl->title = $tag_text;
$tmpl->pageHeader();
print $tmpl->formWithInstructions("Photos and Blog Entries Tagged with '{$tag_text}'", $form, $instructions);
$tmpl->pageFooter();
?>