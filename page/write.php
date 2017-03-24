<?php
$microtimer_start = microtime(true);
session_start();

include_once '../lib/dbobject.php';
include_once '../lib/template.php';
include_once '../lib/familize.php';
include_once '../lib/blogForm.php';

$db = new DBObject();
$famu = new Familize($db);
$famu->loginCheck();
$tmpl = new $famu->template($famu);
$tmpl->cssIncludes[] = '/js/control.markdown.css';
$tmpl->jsIncludes[] = '/js/control.textarea.js';
$tmpl->jsIncludes[] = '/js/control.textarea.markdown.js';
$tmpl->addJs("<!--[if IE]><style>	#markdown_toolbar li a { width:26px; height:18px; } </style><![endif]-->");
$blog = new BlogForm($db, $famu, $tmpl);

if ($_REQUEST['blog']['name']!='') {
	$blog->blogCreate($_REQUEST['blog']['name']);
} elseif ($_REQUEST['new']['title']!='' && $_REQUEST['new']['content']!='') {
	$entry_id = $blog->entryCreate($_REQUEST['new']['title'], $_REQUEST['new']['content'], $_REQUEST['new']['publish']);
	header("location: /read/blog/{$famu->active_id}/{$entry_id}/");
}

list($blog_id, $entry_id) = $_REQUEST['url'];


// load signin page
$tmpl->title = "Write";
$tmpl->pageHeader();

print $blog->entryForm($entry_id);

$tmpl->pageFooter();
?>