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

try {
	$email=$_REQUEST['email'];
	$username=$_REQUEST['username'];
	$password=$_REQUEST['password']; 
	$password2=$_REQUEST['password2'];

	// no user input, maybe first load of page
	if (!isset($_REQUEST['username']) && !isset($_REQUEST['password']) && !isset($_REQUEST['password2']) && !isset($_REQUEST['email'])) {
		$ajaxResponse .= $reg->signupForm();

	// check for user input
	} elseif ($username=='' || $password=='' || $password2=='' || $email=='') {
		$ajaxResponse .= $reg->signupFailed("Please fill out all the fields on this form.");
		$ajaxResponse .= $reg->signupForm();

	// invalid email
	} elseif(!GTools::isValidEmailAddress($email)) {
		$ajaxResponse .= $reg->signupFailed(sprintf("Come now, is '%s' really an email address?", $email));
		$ajaxResponse .= $reg->signupForm();

	} elseif($password != $password2) {
		$ajaxResponse .= $reg->signupFailed("Your passwords don't match.  Please type your password in the 'Password' box, then confirm you spelled it correctly by typing it again in the 'Confirm' box.");
		$ajaxResponse .= $reg->signupForm();

	// authenticate
	} else {
		// check for existing username
		$exUser = $reg->checkUsers($username);
		if ($exUser !== false) {

			// all info matches existing user, log in
			if ($exUser['status']=='user' && $password==$exUser['password'] && $email==$exUser['email']) {
				$ajaxResponse .= $tmpl->getBoxAlert("Existing user", sprintf("This username '%s' belongs to an existing user.  That user is <strong>YOU!</strong>.  Please go back to the <a href=\"/signin\">main signin screen</a> and just sign in.  Use the same username and password you just put in.", $username));

			// all info matches signup user, resend code
			} elseif ($exUser['status']=='signup' && $password==$exUser['password'] && $email==$exUser['email']) {
				list($subject, $body) = $reg->signupConfirmationEmail($username, $password, $email, $exUser['signup_code']);
				$sent = $reg->sendEmail($email, $subject, $body);
				$ajaxResponse .= $tmpl->getBoxNotify("Confirmation re-sent", sprintf("You already signed up with this username, password, and email.  The next step is to check your email at: '%s'.  Click on the confirmation link in that email to proceed.", $email));

			// email matches existing user, resend password
			} elseif ($exUser['status']=='user' && $email==$exUser['email']) {
				list($subject, $body) = $reg->passwordEmail($username, $password);
				$sent = $reg->sendEmail($email, $subject, $body);
				$ajaxResponse .= $tmpl->getBoxAlert("Password sent", sprintf("You have already registered with this username and email combination.  Your password was sent to '%s'.  Please check that then return here to log in", $email));

			// username taken, try again
			} else {
				$ajaxResponse .= $reg->signupFailed(sprintf("The username '%s' is in use by another family member.  Please select another one.", $username));
				$ajaxResponse .= $reg->signupForm();
			}

		// create new user
		} else {
			if ($reg->newSignupUser($username, $password, $email)) {
				$ajaxResponse .= $tmpl->getBoxNotify("Confirmation sent", sprintf("A confirmation code has been sent to '%s'.  Please follow the instructions in that email to complete the signup procedure.", $email));

			// sending confirmation code failed
			} else {
				$ajaxResponse .= $reg->signupFailed(sprintf("A confirmation could not be sent to: '%s'.  Please  try again with different email address.", $email));
				$ajaxResponse .= $reg->signupForm();
			}
		}
	}
} catch (Database_QueryException $e) {
	mail('pendenga@gmail.com', "Database Error ({$_SERVER['SCRIPT_URI']}): {$reg->result[0]['site_name']}", $e->getMessage());
	$ajaxResponse .= $reg->signupFailed(sprintf("We have detected a problem with the database.  Your request has been sent to our really smart guys for resolution.  Please be patient while we resolve this.", $code));
} catch (Exception $e) {
	mail('pendenga@gmail.com', "Unknown Error ({$_SERVER['SCRIPT_URI']}): {$reg->result[0]['site_name']}", $e->getMessage());
	$ajaxResponse .= $tmpl->getBoxError("Unknown Error", sprintf("We have detected a problem with the database.  Your request has been sent to our really smart guys for resolution.  Please be patient while we resolve this.", $code));
}

// print_r($_POST);
// print_r($_REQUEST);

header('X-JSON: ('.$json->encode(array('timer2'=>sprintf("%0.3f",microtime(true)-$microtimer_start))).')');
print $ajaxResponse;

?>