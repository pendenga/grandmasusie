<?php
$microtimer_start = microtime(true);
session_start();

include_once '../lib/dbobject.php';
include_once '../lib/template.php';
include_once '../lib/familize.php';

$photo_id = $_REQUEST['url'][0];

$db = new DBObject();
$famu = new Familize($db);
$famu->loginCheck();
$tmpl = new $famu->template($famu);

$tmpl->title = "Featured Person Cloud - dev";
$tmpl->pageHeader();

/* 2007 */
$form .= '<h3>2007 thru 2008 Present</h3>';
$rs = $db->do_sql("call photoAllFeatured('2007-1-1', '2008-1-1')");
foreach ($rs as $u) {
	$size = $u['countPercent']+1;
	$form .= "<span style=\"font-size: {$size}em\">{$u['first_name']}</span> ";
}

/* 2006 */
$form .= '<hr/><h3>2006 thru 2007 Present</h3>';
$rs = $db->do_sql("call photoAllFeatured('2006-1-1', '2007-1-1')");
foreach ($rs as $u) {
	$size = $u['countPercent']+1;
	$form .= "<span style=\"font-size: {$size}em\">{$u['first_name']}</span> ";
}

/* 2005 */
$form .= '<hr/><h3>2005 thru 2006</h3>';
$rs = $db->do_sql("call photoAllFeatured('2005-1-1', '2006-1-1')");
foreach ($rs as $u) {
	$size = $u['countPercent']+1;
	$form .= "<span style=\"font-size: {$size}em\">{$u['first_name']}</span> ";
}

/* 2004 */
$form .= '<hr/><h3>2004 thru 2005</h3>';
$rs = $db->do_sql("call photoAllFeatured('2004-1-1', '2005-1-1')");
foreach ($rs as $u) {
	$size = $u['countPercent']+1;
	$form .= "<span style=\"font-size: {$size}em\">{$u['first_name']}</span> ";
}

/* 2003 */
$form .= '<hr/><h3>2003 thru 2004</h3>';
$rs = $db->do_sql("call photoAllFeatured('2003-1-1', '2004-1-1')");
foreach ($rs as $u) {
	$size = $u['countPercent']+1;
	$form .= "<span style=\"font-size: {$size}em\">{$u['first_name']}</span> ";
}

/* 2002 */
$form .= '<hr/><h3>2002 thru 2003</h3>';
$rs = $db->do_sql("call photoAllFeatured('2002-1-1', '2003-1-1')");
foreach ($rs as $u) {
	$size = $u['countPercent']+1;
	$form .= "<span style=\"font-size: {$size}em\">{$u['first_name']}</span> ";
}

/* 2001 */
$form .= '<hr/><h3>2001 thru 2002</h3>';
$rs = $db->do_sql("call photoAllFeatured('2001-1-1', '2002-1-1')");
foreach ($rs as $u) {
	$size = $u['countPercent']+1;
	$form .= "<span style=\"font-size: {$size}em\">{$u['first_name']}</span> ";
}

print $tmpl->formWithInstructions("Featured Person Cloud - Since Jan 2003", $form, "This cloud shows the frequency by which names are identified in photos.  The larger the name appears on the screen, the more times that name has been identified in photos.");

$tmpl->pageFooter();


?>