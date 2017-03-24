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
include_once '../lib/userForm.php';
include_once '../lib/extlib/Services_JSON.php';

$db = new DBObject();
$famu = new Familize($db);
$famu->loginCheck(false);
$tmpl = new $famu->template($famu);
$json = new Services_JSON();

// get url variables
list($user_id, $action, $id) = $_REQUEST['url'];

// set user object
if ($user_id!='' && $user_id!=$famu->active_id) {
	$uobj = new UserObject($db, $user_id);
	$user = new UserForm($db, $uobj, $tmpl);
} else {
	$user = new UserForm($db, $famu, $tmpl);
}

try {
	switch ($action) {
	case 'send_sms':
		$ajaxResponse .= $user->sendSms($_POST['sms']);
		break;
	case 'addfamily':
		$ajaxResponse .= $user->addFamilyRelationship($_POST['searchString'], $_POST['relationship']);
		$ajaxResponse .= $user->getImmediateFamily();
		break;
	case 'removefamily':
		$ajaxResponse .= $user->removeFamilyRelationship($id);
		$ajaxResponse .= $user->getImmediateFamily();
	default:
		$ajaxResponse .= "Error: Unknown Action";
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