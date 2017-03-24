<?php
$microtimer_start = microtime(true);
session_start();

include_once '../lib/dbobject.php';
include_once '../lib/template.php';
include_once '../lib/familize.php';
include_once '../lib/registrationForm.php';

$db = new DBObject();
$famu = new Familize($db);
$famu->setSignedOut();
$tmpl = new $famu->template($famu);
$reg = new RegistrationForm($db, $famu, $tmpl);

// load signin page
$tmpl->title = "Sign Out";
$tmpl->pageHeader();
print $errorMsg;
print $reg->signinForm("/home", "You have successfully signed out.  Please come back again soon.");
$tmpl->pageFooter();

?>