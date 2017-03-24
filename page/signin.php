<?php
$microtimer_start = microtime(true);
session_start();

include_once '../lib/dbobject.php';
include_once '../lib/template.php';
include_once '../lib/familize.php';
include_once '../lib/registrationForm.php';

$db = new DBObject();
$famu = new Familize($db);
$tmpl = new $famu->template($famu);
$reg = new RegistrationForm($db, $famu, $tmpl);


// process signin
$errorMsg = '';
if (isset($_POST['username']) && isset($_POST['password'])) {
	if ($_POST['username']=='' || $_POST['password']=='') {
		$errorMsg = $reg->signinFailed("Please fill out all the fields on this form.");
	} else {
		try {
			GTools::logOutput("try to authorize {$_POST['username']}, {$_POST['password']}, {$_POST['persistent']}");
			$famu->authorize($_POST['username'], $_POST['password'], ($_POST['persistent']!=''));
			header("location: {$_POST['directTo']}");
		} catch (User_InvalidException $e) {
			$errorMsg = $reg->signinFailed(sprintf("The username '%s' and password '%s' could not be validated.  Please check them and try again.", $_POST['username'], $_POST['password']));
		} catch (User_InactiveException $e) {
			$errorMsg = $reg->inactiveUser(sprintf("The user account for '%s' is suspended.  Please contact your site administrator to re-activate the account.", $_POST['username']));
		} catch (Exception $e) {
			$errorMsg = $reg->signinFailed(sprintf("The username '%s' and password '%s' could not be validated.  Please check them and try again.", $_POST['username'], $_POST['password']));
		}
	}
} elseif ($_REQUEST['url'] != '') {
	list($username, $password) = $_REQUEST['url'];
	if ($username=='' || $password=='') {
		$errorMsg = $reg->signinFailed("Please fill out all the fields on this form.");
	} else {
		try {
			GTools::logOutput("try to authorize {$username}, {$password}, {$_POST['persistent']}");
			$famu->authorize($username, $password, true);
			header("location: /home/");
		} catch (User_InvalidException $e) {
			$errorMsg = $reg->signinFailed(sprintf("The username '%s' and password '%s' could not be validated.  Please check them and try again.", $username, $password));
		} catch (User_InactiveException $e) {
			$errorMsg = $reg->inactiveUser(sprintf("The user account for '%s' is suspended.  Please contact your site administrator to re-activate the account.", $username));
		} catch (Exception $e) {
			$errorMsg = $reg->signinFailed(sprintf("The username '%s' and password '%s' could not be validated.  Please check them and try again.", $username, $password));
		}
	}
}

// load signin page
$tmpl->title = "Sign In";
$tmpl->pageHeader();
print $errorMsg;
print $reg->signinForm("/home/");
$tmpl->pageFooter();
?>
