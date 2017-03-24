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
include_once '../lib/registrationForm.php';
include_once '../lib/extlib/Services_JSON.php';

$db = new DBObject();
$famu = new Familize($db);
$tmpl = new $famu->template($famu);
$reg = new RegistrationForm($db, $famu, $tmpl);
$json = new Services_JSON();

GTools::logOutput(" signup_code: {$_REQUEST['signup_code']}");
try {
	// initialize form
	if (!isset($_REQUEST['signup_code'])) {
		$ajaxResponse .= $reg->confirmForm();

	// no signup code
	} elseif ($_REQUEST['signup_code'] == '') {
		$ajaxResponse .= $reg->signinFailed("Please fill out all the fields on this form.");
		$ajaxResponse .= $reg->confirmForm();

	} else {
		// check signup code, insert new user
		list($userCreated, $userAssigned, $siteName) = $reg->insertSignupUser($_REQUEST['signup_code']);

		// invalid signup code
		if ($userCreated===false) {
			$ajaxResponse .= $reg->signupFailed(sprintf("This signup code: '%s' is no longer valid.  Please enter a different code or <a href=\"/signup\">signup for a new username</a>.", $signup_code));
			$ajaxResponse .= $reg->confirmForm();

		// created new user, may have been assigned to site
		} else {
			$ajaxResponse .= $reg->signupSuccess(sprintf("Your username '%s' was created", $userCreated));
			$ajaxResponse .= ($userAssigned) ? $reg->signupNotes(sprintf("The %s site does not have an open enrollment system; so you will need to be approved by the site's administrator before you can join the site.", $siteName)) : $reg->signupSuccess(sprintf("You have been added to the %s site", $siteName));
		}
	}
} catch (Database_QueryException $e) {
	mail('pendenga@gmail.com', "Database Error ({$_SERVER['SCRIPT_URI']}): {$reg->result[0]['site_name']}", $e->getMessage());
} catch (Exception $e) {
	mail('pendenga@gmail.com', "Unknown Error ({$_SERVER['SCRIPT_URI']}): {$reg->result[0]['site_name']}", $e->getMessage());
}

header('X-JSON: ('.$json->encode(array('timer2'=>sprintf("%0.3f",microtime(true)-$microtimer_start))).')');
print $ajaxResponse;

?>