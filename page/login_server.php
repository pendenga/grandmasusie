<?php

/**
 * This AJAX server should just be passed the complete $_REQUEST object every 
 *  time and should return a full page for the middle of the registration page.
 */
$microtimer_start = microtime(true);
session_start();
//$microtimer_session = microtime(true);

include_once '../lib/dbobject.php';
include_once '../lib/familize.php';
include_once '../lib/template.php';
include_once '../lib/extlib/Services_JSON.php';
//$microtimer_include = microtime(true);

$db = new DBObject();
$famu = new Familize($db);
$tmpl = new $famu->template($famu);
$json = new Services_JSON();
$jarr = array();
//$microtimer_load = microtime(true);

list($action, $user_id) = $_REQUEST['url'];
//$microtimer_init = microtime(true);

try {
	switch ($action) {
	case 'unseen':
		print $tmpl->getUnseenChecker($user_id);
		break;
	case 'switchto':
		$famu->switchTo($user_id);
		$jarr['passive'] = $famu->user['passive'];
	default:
		$name = $famu->getUserName();
		print "<strong><a href=\"/useredit/\">{$name}</a></strong>";
	}
	//print_r($famu);

} catch (Database_QueryException $e) {
	mail('pendenga@gmail.com', "Database Error ({$_SERVER['SCRIPT_URI']}): ", $e->getMessage());
} catch (Exception $e) {
	mail('pendenga@gmail.com', "Unknown Error ({$_SERVER['SCRIPT_URI']}): ", $e->getMessage());
}

//GTools::logTimer(sprintf(" login_server (ses: %0.4f, inc: %0.4f, load: %0.4f, init: %0.4f, run: %0.4f, tot: %0.4f)", $microtimer_session-$microtimer_start, $microtimer_include-$microtimer_session, $microtimer_load-$microtimer_include, $microtimer_init-$microtimer_load, microtime(true)-$microtimer_init, microtime(true)-$microtimer_start));

$jarr['timer2'] = sprintf("%0.3f",microtime(true)-$microtimer_start);
header('X-JSON: ('.$json->encode($jarr).')');
print $ajaxResponse;

?>