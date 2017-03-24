<?php

include_once '../lib/dbobject.php';
include_once '../lib/template.php';
include_once '../lib/userobject.php';
include_once '../tmpl/gomez/template.php';

$uobj = new UserObject($db, $user_id);
$tmpl = new GomezTemplate($uobj);

GTools::logOutput("NOT FOUND: yo");

$tmpl->title = "Page Not Found";
$tmpl->pageHeader();

print "NOT FOUND";

$tmpl->pageFooter();

//print "NOT FOUND";

?>