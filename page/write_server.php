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
include_once '../lib/extlib/Services_JSON.php';
include_once '../lib/blogForm.php';
include_once '../helpers/formatlongtext.php';

$db = new DBObject();
$famu = new Familize($db);
$famu->loginCheck(false);
$tmpl = new $famu->template($famu);
$blog = new BlogForm($db, $famu, $tmpl);
$json = new Services_JSON();
$json_values = array();

list($action, $entry_id, $parm_1, $parm_2) = $_REQUEST['url'];
$blog->__singleton();
$blog->blog->getBlogEntry($entry_id);

switch ($action) {
case 'draft':
	$blog->blog->saveActive($entry_id, !$blog->blog->info['active']);
	$ajaxResponse .=  $blog->draftIcon();
	break;
case 'flagged':
	$blog->blog->saveFlagged($entry_id, !$blog->blog->info['flagged']);
	$ajaxResponse .=  $blog->flaggedIcon();
	break;
case 'addtags':
	$addMessage = ($blog->addTags($parm_1)) ? "{$parm_1} added" : "Action Failed!";
	$json_values['tags_message'] = $addMessage;
	$ajaxResponse .= $blog->getTags();
	break;
case 'remtags':
	$blog->removeTags($parm_1);
	$json_values['tags_message'] = "Tag Removed";
	$ajaxResponse = "do not show this response anywhere";
	break;
case 'title':
	$value = $_REQUEST['title'];
	if ($value=='') {
		$ajaxResponse .=  $blog->titleEdit("title_editable_{$entry_id}");
		$json_values['edit_id'] = "title_editable_{$entry_id}";
	} else {
		$blog->blog->saveTitle($entry_id, $value);
		$ajaxResponse .=  ($value) ? $value : '<em>click here to add a title</em>';
	}
	break;
case 'content':
	$value = $_REQUEST['content'];
	if ($value=='') {
		$ajaxResponse .=  $blog->contentEdit("content_editable_{$entry_id}");
		$json_values['edit_id'] = "content_editable_{$entry_id}";
	} else {
		$blog->blog->saveContent($entry_id, $value);
		$content = FormatLongText::for_print($value);
		$ajaxResponse .=  "<div class=\"blogContent\" onclick=\"editContent(this)\">{$content}</div>";
	}
	break;
case 'resetcontent':
	$content = FormatLongText::for_print($blog->blog->info['content']);
	$ajaxResponse .=  "<div class=\"blogContent\" onclick=\"editContent(this)\">{$content}</div>";
	break;
}

header('X-JSON: ('.$json->encode($json_values).')');
print $ajaxResponse;

?>