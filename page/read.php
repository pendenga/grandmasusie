<?php
$microtimer_start = microtime(true);
session_start();

include_once '../lib/dbobject.php';
include_once '../lib/template.php';
include_once '../lib/familize.php';
include_once '../lib/blogForm.php';
include_once '../lib/commentForm.php';

$db = new DBObject();
$famu = new Familize($db);
$famu->loginCheck();
$tmpl = new $famu->template($famu);
$tmpl->cssIncludes[] = '/js/control.markdown.css';
$tmpl->jsIncludes[] = '/js/control.textarea.js';
$tmpl->jsIncludes[] = '/js/control.textarea.markdown.js';
$tmpl->addJs("<!--[if IE]><style>	#markdown_toolbar li a { width:26px; height:18px; } </style><![endif]-->");
$blog = new BlogForm($db, $famu, $tmpl);
$author=false;
$instructions = '';

// load different types of blog entry
list($type_id, $blog_id, $entry_id) = $_REQUEST['url'];

// process posted comments (normal comments are posted to comment_server.php
if (isset($_POST['new']['comment'])) {
	include_once '../lib/commentForm.php';
	$como = new CommentForm($db, $famu, $tmpl);
	$como->newBlogComment($entry_id, $_POST['new']['comment'], $_POST['new']['user_id']);
}

// load page
switch ($type_id) {
case 'blog':
	if ($blog_id!='' && $entry_id!='') {
		$output = $blog->individualBlogEntry($entry_id, 'blog');
		$instructions = $blog->unreadCommentsSide(6);
		$author = true;
	} elseif ($blog_id!='') {
		$output = $blog->recentByBlog($blog_id);
	} else {
		$output = $blog->latestByBlog();
	}
	break;
case 'fam':
	if ($blog_id!='' && $entry_id!='') {
		$output = $blog->individualBlogEntry($entry_id, 'fam');
		$instructions = $blog->unreadCommentsSide(6);
	} elseif ($blog_id!='') {
		$output = $blog->recentByFamily($blog_id);
	} else {
		$output = $blog->latestByFamily();
	}
	break;
case 'comments':
	$output = $blog->unreadComments();
	break;
case 'flagged':
	$output = $blog->flaggedList();
	break;
default:
	$output = $blog->latestByFamily();
}


if ($type_id=='comments' && $blog_id=='nopage') {
	print $output;
} else {
	$tmpl->title = "Read";
	$tmpl->pageHeader();
	if ($author) {
		print $tmpl->authorForm($output, $instructions);
	} else {
		print $tmpl->formWithInstructions('', $output, $instructions);
	}
	$tmpl->pageFooter();
} 
?>
