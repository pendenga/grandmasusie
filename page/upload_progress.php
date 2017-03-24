<?php
# PHP File Uploader with progress bar Version 2.0
# Based on progress.php, a contrib to Megaupload, by Mike Hodgson.
# Changed for use with AJAX by Tomas Larsson
# http://tomas.epineer.se/

# Licence:
# The contents of this file are subject to the Mozilla Public
# License Version 1.1 (the "License"); you may not use this file
# except in compliance with the License. You may obtain a copy of
# the License at http://www.mozilla.org/MPL/
# 
# Software distributed under this License is distributed on an "AS
# IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or
# implied. See the License for the specific language governing
# rights and limitations under the License.

// get shared upload settings with perl
$settingsfile = $_SERVER["DOCUMENT_ROOT"]."/../upload_settings.inc";
eval(file_get_contents($settingsfile));

// get query string variables
list($photo_id) = $_REQUEST['url'];

// read upload files (managed by perl script)
$info_file = "$tmp_dir/$photo_id"."_flength";
$data_file = "$tmp_dir/$photo_id"."_postdata";
$error_file = "$tmp_dir/$photo_id"."_err";

# Send error code if error file exists
if(file_exists($error_file)) {
	header("HTTP/1.1 500 Internal Server Error");
	echo file_get_contents($error_file);
	exit;
}

$percent_done = 0;
$started = TRUE;
if ($fp = @fopen($info_file,"r")) {
		$fd = fread($fp,1000);
		fclose($fp);
		$total_size = $fd;
} else {
	$started = FALSE;
}
if ($started == TRUE) {
	$current_size = @filesize($data_file);
	$percent_done = intval(($current_size / $total_size) * 100);
}

print $percent_done;
?>