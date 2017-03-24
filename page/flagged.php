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

$tmpl->title = "Flagged";
$tmpl->pageHeader();

// set flagged photos
$limit = 100;
$offset = 0;
$offs = $phto->setFlagged($limit, $offset*$limit);

// output photos and blogs
$output .= $phto->getThumbGallery(6);
$output .= $blog->flaggedList();

$instructions = "<p>These are the images and photos you have flagged for further follow-up.</p><p>Items will remain flagged until you click the 'is Flagged' button to remove the flagged status from the item.</p>";
print $tmpl->formWithInstructions('Flagged Photos and Blogs', $output, $instructions);

$tmpl->pageFooter();

?>