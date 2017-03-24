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
include_once '../lib/statForm.php';
include_once '../lib/extlib/Services_JSON.php';

$db = new DBObject();
$famu = new Familize($db);
$tmpl = new $famu->template($famu);
$stat = new StatForm($db, $famu, $tmpl);
$json = new Services_JSON();

list($action, $parm1, $parm2) = $_REQUEST['url'];

try {
	switch ($action) {
	case 'photoyear':
		$ajaxResponse = $stat->data_photosByYear($parm1);
		break;
	case 'commentyear':
		$ajaxResponse = $stat->data_commentsByYear($parm1);
		break;
	case 'commentsperphoto':
		$ajaxResponse = $stat->data_commentsPerPhoto($parm1);
		break;
	case 'photouser':
		$ajaxResponse = $stat->data_photosByUser($parm1, $parm2);
		break;
	} 

} catch (Database_QueryException $e) {
	mail('pendenga@gmail.com', "Database Error ({$_SERVER['SCRIPT_URI']}): {$reg->result[0]['site_name']}", $e->getMessage());
} catch (Exception $e) {
	mail('pendenga@gmail.com', "Unknown Error ({$_SERVER['SCRIPT_URI']}): {$reg->result[0]['site_name']}", $e->getMessage());
}

header('X-JSON: ('.$json->encode(array('timer2'=>sprintf("%0.3f",microtime(true)-$microtimer_start))).')');
header('content-type: text/plain');
print $ajaxResponse;

?>