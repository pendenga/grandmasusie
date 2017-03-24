<?php
$microtimer_start = microtime(true);
session_start();

include_once '../lib/dbobject.php';
include_once '../lib/template.php';
include_once '../lib/familize.php';
include_once '../lib/profileForm.php';
include_once '../lib/adminForm.php';
include_once '../lib/blogForm.php';

$db = new DBObject();
$famu = new Familize($db);
$famu->loginCheck();
$tmpl = new $famu->template($famu);
$prof = new ProfileForm($db, $famu, $tmpl);

// get url variables
list($page, $action, $id, $id2) = $_REQUEST['url'];
$funcList = $prof->getHeader();

// load admin page
$tmpl->title = "Admin";
$tmpl->pageHeader();

// processing form
if ($_POST['avatar']) {
	print $prof->saveAvatar($_POST['avatar']);
	$_POST['user_id'] = $_POST['avatar']['user_id'];
} elseif ($_POST['blog']) {
	$blog = new BlogForm($db, $famu, $tmpl);
	print $blog->blogSave($_POST['blog']['name']);
} elseif ($_POST['confirm']) {
	print $prof->confirmContact($_POST['confirm']);
} elseif ($_POST['contact']) {
	print $prof->saveContact($_POST['contact']);
} elseif ($_POST['join']) {
	print $prof->joinHousehold($_POST['join']);
} elseif ($_POST['password']) {
	print $prof->savePassword($_POST['password']);
} elseif ($_POST['profile']) {
	print $prof->saveProfile($_POST['profile']);
}

// load form
switch ($page) {
case 'avatar':
	// restore avatar (ignore if anything posted)
	if ($id=='restore' && count($_POST)==0) {
		if ($famu->restoreAvatar()) {
			print $tmpl->getBoxNotify("Confirmed", "Your avatar was restored.");
		}
		print $prof->avatarForm($action);

	// special avatar (ignore if anything posted)
	} elseif ($id!='' && count($_POST)==0) {
		$famu->setAvatar($id);
		print $tmpl->getBoxNotify("Confirmed", "Your avatar was changed.");
		print $prof->avatarForm($action);

	} else {
		print $prof->avatarForm($action, $_POST['user_id']);
	}
	break;

case 'preferences':
	$blog = new BlogForm($db, $famu, $tmpl);
	$form = $prof->preferencesForm($blog);
	$instructions = "<p>Edit some user specific settings.  First of all what do you want your blog to be called.</p>";
	print $tmpl->threeColumnLayout($funcList, $form, $instructions, 'page');
	break;

case 'confirm':
	print $prof->confirmForm();
	break;

case 'contact':
	$form = $prof->contactInfoForm();
	$instructions = "We would like to notify you from time to time about new content or features on GrandmaSusie.com.  Please indicate here whether you would prefer to be notified via an email message or via a text message (SMS) to your phone.";
	print $tmpl->threeColumnLayout($funcList, $form, $instructions, 'page');
	break;

case 'create':
	$form = $prof->createForm();
	$instructions = "Make up a new user... go ahead... just make someone up like that";
	print $tmpl->threeColumnLayout($funcList, $form, $instructions, 'page');
	break;

case 'household':
	print $prof->householdForm();
	break;

case 'password':
	if ($action=='reset' && $id!='') {
		$form = $prof->passwordChangeForm($id);
		$instructions = "<p>You can only change the password for the user you logged in as.  To change the password for a user you are currently overriding, you need to log out and log back in as that user.</p><p>To change your password, you must re-enter your current password and confirm your new password by entering it twice.  If you've forgotten you current password, <a href=\"#\">Click here</a> to get a password reset code sent to your email address.</p>";	
	} elseif ($action=='matching') {
		$form = $prof->passwordChangeForm(false);
		$instructions = "Please take a moment to change your password.  Formerly, you were able to maintain a username and password that were the same.  We've decided that is insecure enough that you should change your password to be different than your username.  Please do that now.  Thank you.";
	} else {
		$form = $prof->passwordChangeForm();
		$instructions = "<p>You can only change the password for the user you logged in as.  To change the password for a user you are currently overriding, you need to log out and log back in as that user.</p><p>To change your password, you must re-enter your current password and confirm your new password by entering it twice.  If you've forgotten you current password, <a href=\"#\">Click here</a> to get a password reset code sent to your email address.</p>";
	}
	print $tmpl->threeColumnLayout($funcList, $form, $instructions, 'page');
	break;

case 'profile':
	$form = $prof->userProfileForm();
	$instructions = "<p>Edit user info for this user. Fill out all fields as completely as possible.</p><p>If you don't want to use a nickname, leave it blank.  If your nickname replaces your full name (like 'Grandma Susie' or 'Gramps') check the 'nickname is full name' box.  If your nickname replaces only your full name (like 'Dani' or 'Boomer') leave the 'nickname is full name' box unchecked.</p>";

	print $tmpl->threeColumnLayout($funcList, $form, $instructions, 'page');
	break;

default:
	// basic options
	$form = $funcList;
	print $tmpl->formWithInstructions("Edit User Account", $form, "These options pertain to your individual login account.  If you want to change options for a user that you override, you must login in as that user.");
}

$tmpl->pageFooter();

/*
print "<pre>";
print_r($famu);
print "</pre>";
*/
?>