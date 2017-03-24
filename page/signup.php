<?php

//header('location: /signin/');
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

// load signin page
$tmpl->ajax = true;
$tmpl->title = "New User Registration";
$tmpl->pageHeader();

$instructions = 'You are required to create a user account to upload photos. Previous GrandmaSusie.com users should follow this procedure to verify their email addresses.';
$form = $reg->signupForm();
print $tmpl->formWithInstructions("Sign Up", $form, $instructions);

$tmpl->pageFooter();
?>