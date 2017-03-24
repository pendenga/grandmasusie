<?php

header('content-type: text/plain');
include_once 'dbobject.php';

$db = new DBObject(array('db_errors'=>'on'));
$result = $db->do_sql("SELECT * FROM users");
print_r($result);

$result = $db->do_sql("SELECT * FROM users u INNER JOIN site_member sm ON u.user_id=sm.user_id INNER JOIN site s ON s.site_id=sm.site_id");
print_r($result);

?>