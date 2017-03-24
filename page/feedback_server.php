<?php
session_start();

if ($_POST['comment']) {
	$post = $_POST['comment'];
	ob_start();
	print_r($_SESSION);
	print_r($_REQUEST);
	print_r($_SERVER);
	$vars = ob_get_clean();
	$body = <<<EOD
Comment From: {$post['name']}
Comment Type: {$post['type']}

Comment: {$post['content']}

Session: {$vars}
--------------------------------------------
This is an automated message.  Do not reply.
EOD;
	mail('pendenga@gmail.com', "Comment from: {$post['name']}",  $body);
	print 'Your message has been sent.  Thank you for your comments.';

} else {

	include_once '../lib/dbobject.php';
	include_once '../lib/familize.php';
	$db = new DBObject();
	$famu = new Familize($db);
	print <<<EOD
<form method="post" id="feedback_form">
<table>
<tr><td align="right">Name:</td>
	<td><input type="text" name="comment[name]" value="{$famu->user['first_name']} {$famu->user['last_name']}"/></td>
	<td rowspan="2" class="detail">I appreciate getting feedback.  Anything you think could work better than it does? Is there something that's giving you trouble?  Please let me know.  - Grant</td></tr>
<tr><td align="right">Type:</td>
	<td><select name="comment[type]">
		<option>Bug Report</option>
		<option>Feature Request</option>
		<option>General Comment</option>
		</select></td></tr>
<tr><td colspan="3"><textarea name="comment[content]" rows="3" cols="50"></textarea></td></tr>
<tr><td colspan="3"><input type="submit" value="Send Comments" class="buttons" onclick="Modalbox.show('Thank You', '/feedback_server/', {method: 'post', width: 500, height: 100, params:Form.serialize('feedback_form'), afterLoad: function() { $('progress').className = 'hiddenDiv'; }}); return false;"/></td></tr>
</table>
</form>
EOD;
}

?>