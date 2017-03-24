<?php

/**
 * This AJAX server should just be passed the complete $_REQUEST object every 
 *  time and should return a full page for the middle of the registration page.
 */
$microtimer_start = microtime(true);
session_start();

include_once '../lib/dbobject.php';
include_once '../lib/template.php';
include_once '../lib/familize.php';
include_once '../lib/extlib/Services_JSON.php';
include_once '../lib/photoForm.php';
include_once '../lib/commentForm.php';

$db = new DBObject();
$famu = new Familize($db);
$famu->loginCheck(false);
$tmpl = new $famu->template($famu);
$json = new Services_JSON();
$json_values = array();

list($action, $pid) = $_REQUEST['url'];

switch ($action) {
case 'blog':
	$time = date('m/d/Y h:i:s a');
	$como = new CommentForm($db, $famu, $tmpl);
	if ($_REQUEST['new']['comment']) {
		$como->newBlogComment($pid, $_REQUEST['new']['comment'], $_REQUEST['new']['user_id']);
	}
	if ($_REQUEST['delete']['comment']) {
		$como->deleteBlogComment($pid, $_REQUEST['delete']['comment']);
	}
	$como->setBlogComments($pid, $time);
	$ajaxResponse = $como->comments;
	break;

case 'photo':
	$time = date('m/d/Y h:i:s a');
	$como = new CommentForm($db, $famu, $tmpl);
	if ($_REQUEST['new']['comment']) {
		$como->newPhotoComment($pid, $_REQUEST['new']['comment'], $_REQUEST['new']['user_id']);
	}
	if ($_REQUEST['delete']['comment']) {
		$como->deletePhotoComment($pid, $_REQUEST['delete']['comment']);
	}
	$como->setPhotoComments($pid, $time);
	$ajaxResponse = $como->comments;
	break;

default:
	$json_values['alert'] = "Unknown Action";
}

header('X-JSON: ('.$json->encode($json_values).')');
print $ajaxResponse;

?>