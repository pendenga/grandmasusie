<?php

//session_start();
include_once 'dbobject.php';
include_once 'familize.php';
include_once 'photo.php';
$famu = new Familize();
$db = new DBObject();
$rs = $db->do_sql("SELECT * FROM photo ORDER BY uploaded_dt DESC");
$phto = new Photo($db, $famu, $rs[0]);

print "<pre>";
print_r($_SESSION);
print_r($rs[0]);
print_r($phto);
print "</pre>";

?>