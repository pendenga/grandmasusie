<?php

/**
 * This AJAX server should just be passed the complete $_REQUEST object every 
 *  time and should return a full page for the middle of the registration page.
 */
$microtimer_start = microtime(true);
session_start();
//$microtimer_session = microtime(true);

include_once '../lib/dbobject.php';
include_once '../lib/familize.php';
include_once '../lib/extlib/Services_JSON.php';
include_once '../lib/template.php';
include_once '../lib/photoForm.php';
include_once '../lib/searchForm.php';
include_once '../helpers/formatlongtext.php';
//$microtimer_include = microtime(true);

$db = new DBObject();
$famu = new Familize($db);
$famu->loginCheck(false);
$tmpl = new $famu->template($famu);
$json = new Services_JSON();
$json_values = array();
//$microtimer_load = microtime(true);

list($action, $pid, $parm_1, $parm_2) = $_REQUEST['url'];
$phto = new PhotoForm($db, $famu, $tmpl);
$phto->setPhoto($pid);
//$microtimer_init = microtime(true);

GTools::logOutput(" action:$action, pid:$pid, name:$parm_1, id:$parm_2");

// set photo caption
switch ($action) {
case 'caption':
	if (!isset($_REQUEST['caption'])) {
		$ajaxResponse = $phto->captionEdit("caption_editable_{$pid}");
		$json_values['edit_id'] = "caption_editable_{$pid}";
	} else {
		$phto->captionSave($_REQUEST['caption']);
		$ajaxResponse = ($phto->photo->img['caption']=='') ? '<em>click here to add a caption</em>' : $phto->photo->img['caption'];
	}
	break;

case 'addfeatured':
	// add user by id
	if ($parm_2 != '') {
		GTools::logOutput(" adding feat by id: $parm_2");
		$addMessage = ($phto->addFeatured($parm_2)) ? "{$parm_1} added" : "Action Failed!";

	// add user by name
	} else {
		GTools::logOutput(" adding feat by query: $parm_1");
		$srch = new SearchForm($db, $famu, $tmpl);
		list($users, $unique) = $srch->searchUsersForFullName($parm_1);
		if ($unique) {
			$addMessage = ($phto->addFeatured($users[0]['user_id'])) ? "{$parm_1} added" : "Action Failed!";
		} elseif (count($users) == 0) {
			$addMessage = "No Users Found";
		} else {
			$addMessage = "Ambiguous Name";
		}
	}
	$json_values['feat_message'] = $addMessage;
	$ajaxResponse .= $phto->getFeatured();
	break;

case 'addtags':
	$addMessage = ($phto->addTags($parm_1)) ? "{$parm_1} added" : "Action Failed!";
	$json_values['tags_message'] = $addMessage;
	$ajaxResponse .= $phto->getTags();
	break;

case 'remfeatured':
	$phto->removeFeatured($parm_1);
	$json_values['feat_message'] = "User Removed";
	$ajaxResponse = "do not show this response anywhere";
	//$ajaxResponse .= $phto->getFeatured();
	break;

case 'remtags':
	$phto->removeTags($parm_1);
	$json_values['tags_message'] = "Tag Removed";
	$ajaxResponse = "do not show this response anywhere";
	//$ajaxResponse .= $phto->getFeatured();
	break;

// set photo date
case 'taken':
	if (!isset($_REQUEST['taken'])) {
		$ajaxResponse = $phto->takenEdit("taken_editable_{$pid}");
		$json_values['edit_id'] = "taken_editable_{$pid}";
	} else {
		if (trim($_REQUEST['taken'])=='') {
			$json_values['error_msg'] = "Please type 'NULL' if you want to clear this date";
		} elseif (trim($_REQUEST['taken'])!='NULL' && strtotime($_REQUEST['taken'])==false) {
			$json_values['error_msg'] = "'{$_REQUEST['taken']}' is not a valid date";
		} else {
			$phto->takenSave($_REQUEST['taken']);
		}
		$ajaxResponse = ($phto->photo->img['take_dt']=='') ? '<em>When was this photo taken?</em>' : sprintf("Taken: <em>%s</em>", GTools::takeTime($phto->photo->img['take_dt'], $phto->photo->img['take_exif']));
	}
	break;

// set photo description
case 'description':
	$value = $_REQUEST['description'];
	if ($value=='') {
		$ajaxResponse = $phto->descriptionEdit("description_editable_{$pid}");
		$json_values['edit_id'] = "description_editable_{$pid}";
	} else {
		$phto->descriptionSave($value);
		$desc = ($value) ? $value : '<em>click here to add a description</em>';
		$ajaxResponse = FormatLongText::for_print($desc);
	}
	break;

// toggle favorite icon
case 'favorite':
	$phto->favoriteToggle();
	$ajaxResponse = $phto->favoriteIcon();
	break;

// toggle flagged icon
case 'flagged':
	$phto->flaggedToggle();
	$ajaxResponse = $phto->flaggedIcon();
	break;

case 'prevnext':
}

//GTools::logTimer(sprintf(" photo_server (ses: %0.4f, inc: %0.4f, load: %0.4f, init: %0.4f, run: %0.4f, tot: %0.4f)", $microtimer_session-$microtimer_start, $microtimer_include-$microtimer_session, $microtimer_load-$microtimer_include, $microtimer_init-$microtimer_load, microtime(true)-$microtimer_init, microtime(true)-$microtimer_start));

header('X-JSON: ('.$json->encode($json_values).')');
print $ajaxResponse;

?>