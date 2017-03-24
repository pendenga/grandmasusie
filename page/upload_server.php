<?php

/**
 * This AJAX server should just be passed the complete $_REQUEST object every 
 *  time and should return a full page for the middle of the registration page.
 */
$microtimer_start = microtime(true);
session_start();

include_once '../lib/dbobject.php';
include_once '../lib/template.php';
include_once '../lib/familize.php';
include_once '../lib/photo.php';
include_once '../lib/extlib/Services_JSON.php';

$db = new DBObject();
$famu = new Familize($db);
$tmpl = new $famu->template($famu);
$json = new Services_JSON();

// get shared upload settings with perl
$settingsfile = $_SERVER["DOCUMENT_ROOT"]."/../upload_settings.inc";
eval(file_get_contents($settingsfile));

// get query string variables
list($action, $photo_id) = $_REQUEST['url'];


// create new photo record
// create new photo record
// create new photo record
if ($action == 'upload') {
	// create blank photo object with new photo id ($dest_server comes from upload_settings.inc)
	$phto = new Photo($db, $famu);
	$new_photo_id = $phto->newPhoto($photo_id, $dest_server);

	// respond with 'waiting' form
	$ajaxResponse = <<<EOD
			<table width="100%" border="0" cellpadding="3" cellspacing="0" style="border: 1px solid #6c821a; background-color: #dae0c6; padding: 3px">
			<tr><td width="210" id="progress_{$photo_id}">
					Uploading... <span id="progress_pct_{$photo_id}">0</span>%<br/>
					<div id="progress_bar_{$photo_id}" class="progresscontainer"><div class="progressbar" style="width: 1%"></div></div></td>
				<td width="390">
					<span class="detail" id="message_{$photo_id}"><p>Please wait for your photo to upload.</p><p>A smaller upload file would take less time, but would also be lower quality.</p><p>You should leave this window up as it is, but you could <a href="/home/" target="_new">open another window</a> and continue to browse the site.</p></span>
				</td></tr>
			</table>
EOD;
	//<br/>Title: <input type="text" name="caption"> <input type="button" class="buttons" value="save"/>

	// prepare json header and response
	$data = array(
		'timer2'=>sprintf("%0.3f",microtime(true)-$timer),
		'new_id'=>$new_photo_id);
}


// receive photo and save details
// receive photo and save details
// receive photo and save details
if ($action == 'receive') {

	// $tmp_dir comes from upload_settings.php
	$qstring = "{$tmp_dir}/{$photo_id}_qstring";
	if (file_exists($qstring)) {
		// parse out info from upload.cgi
		$filepairs = explode('&', str_replace('file[', '', str_replace('][0]', '', urldecode(file_get_contents($qstring)))));
		foreach ($filepairs as $pair) {
			list($key, $value)=explode('=', $pair);
			$fileparts[$key] = $value;
		}
		copy($fileparts['tmp_name'], "{$upload_dir}{$dest_server}/{$photo_id}_o.jpg");

		// prepare info for database
		$photo = array(
			'photo_uid'=>$photo_id, 
			'orig_size'=>$fileparts['size'], 
			'complete'=>0, 
			'filename'=>$fileparts['name']);
		$phto = new Photo($db, $famu, $photo);
		$phto->save();

		// print thumbnail for HTML response
		$thumbURL = "http://www.grandmasusie.com/static/{$dest_server}/{$photo_id}_t.jpg";
		$ajaxResponse = "<a href=\"http://www.grandmasusie.com/gallery/\"><img src=\"{$thumbURL}\" title=\"Click here to go to the main gallery.\" border=\"0\"/></a>";
	} else {
		$ajaxResponse = "<h3>ERROR</h3> (qstring)";
	}

	// prepare json header and response
	$data = array(
		'timer2'=>sprintf("%0.3f",microtime(true)-$timer),
		'message'=>"Your photo has been successfully uploaded.");
}

header('X-JSON: ('.$json->encode($data).')');
print $ajaxResponse;

?>
