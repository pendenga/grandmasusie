<?php
$microtimer_start = microtime(true);
session_start();

include_once '../lib/dbobject.php';
include_once '../lib/template.php';
include_once '../lib/familize.php';
include_once '../lib/blogForm.php';
include_once '../lib/photoForm.php';
include_once '../helpers/formattime.php';

$db = new DBObject();
$famu = new Familize($db);
$famu->loginCheck();
$tmpl = new $famu->template($famu);
$phto = new PhotoForm($db, $famu, $tmpl);

// get url variables
list($age_days, $offset, $header) = $_REQUEST['url'];
// convert to zero-indexed offset
$offset = ($offset=='')  ? 0 : max(0, $offset-1);
$limit = 49;

$offs = $phto->setSameAge($age_days, false, $limit, $offset*$limit);
$phto->setPhotoSet('in_sameage', $age_days, $offs);
if ($phto->photo_count > 0) {
	$form = $phto->getThumbGallery(6);
	$tmpl->setPageList($phto->getPageList($phto->photo_count, $limit, $offs, "/sameage/{$age_days}/"));
} else {
	$form = "<h3>There are no people this age.</h3>";
}

// print output
$fmt_age = FormatTime::ageInDays($age_days);
$tmpl->title = $fmt_age;
$tmpl->pageHeader();
$instructions = "This is a set of photos where at least one person in the photo set is {$fmt_age}.  Look for ages of people in the photo next to their name on the side.  A person will only be identified by age in a picture if the following three conditions are met:  <ol><li>They must be identified in the picture.</li><li>The picture must have a <em>date taken</em> attribute.</li><li>The person must have their age recorded in the system including the year they were born.</li></ol>";
print $tmpl->formWithInstructions("Photos featuring people who were {$fmt_age}", $form, $instructions);
$tmpl->pageFooter();
?>