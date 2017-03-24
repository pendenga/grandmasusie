<?php

header('content-type: text/plain');
include_once 'gtools.php';

// Encrypt and Decrypt
$orig_string = "George W Bush";
$encr_string = GTools::encrypt($orig_string);
$decr_string = GTools::decrypt($encr_string);
print "Test: {$orig_string} = {$decr_string}\n";

// Default Value
$value1 = 'original value';
$value2 = '';
unset($value3);
GTools::defaultValue($value1, 'new value');
GTools::defaultValue($value2, 'new value');
GTools::defaultValue($value3, 'new value');
print "Test: '{$value1}' should be 'original value'\n";
print "Test: '{$value2}' should be 'new value'\n";
print "Test: '{$value3}' should be 'new value'\n";

?>
