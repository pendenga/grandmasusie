<?php
$microtimer_start = microtime(true);
session_start();

include_once '../lib/dbobject.php';
include_once '../lib/template.php';
include_once '../lib/familize.php';
include_once '../lib/blogForm.php';
include_once '../lib/photoForm.php';

GTools::logOutput("--- Loading Home Page ---");

$db = new DBObject();
$famu = new Familize($db);
$famu->loginCheck();
$tmpl = new $famu->template($famu);
$blog = new BlogForm($db, $famu, $tmpl);
$phto = new PhotoForm($db, $famu, $tmpl);


// load signin page
$tmpl->title = "Home Page";
$tmpl->pageHeader();
print $errorMsg;

// special days messages
switch (date('m/d/y')) {
case '02/05/08':
case '11/04/08':
	print <<<EOD
	<div style="border: 1px solid #000; background-color: #ccf; float: right; width: 250px; margin: 2px; margin-right: 250px; padding: 2px"><div><a href="/useredit/avatar/6629/ivotedtoday"><img src="/static/avatar/ivotedtoday.jpg" border="0" align="left" style="margin: 3px"/></a> <a href="/useredit/avatar/6629/ivotedearly"><img src="/static/avatar/ivotedearly.jpg" border="0" align="left" style="margin: 3px"/></a> Change your avatar for Election Day!  Click here if you voted!</div></div>
EOD;
	break;
default:
	// normal days do nothing
}


$phto->setRecent(50);
$gallery = $phto->getThumbGallery();
$famblog = $blog->latestByFamily(false);

$birthdays = $tmpl->upcomingBirthdays();
$anniversaries = $tmpl->upcomingAnniversaries();
$recent = $tmpl->recentVisitors();

/*
$oneDay = (60*60*24);
$twoWeeks = (60*60*24*14);
$interestingCloud = $famu->interestingCloud(date('Y-m-d', time()-$twoWeeks), date('Y-m-d', time()+$oneDay));
$quantityCloud = $famu->quantityCloud(date('Y-m-d', time()-$twoWeeks), date('Y-m-d', time()+$oneDay));
*/

print $tmpl->threeColumnLayout($gallery, $famblog, $birthdays.$anniversaries.$recent);
$tmpl->pageFooter();

?>
