<?php

$microtimer_start = microtime(true);
session_start();
include_once '../lib/dbobject.php';
include_once '../lib/template.php';
include_once '../lib/familize.php';

header('content-type: text/xml');
print "<response><!-- GrandmaSusie.com - ";
print date("Y/m/d h:i:s");

$action = array_shift($_REQUEST['url']);

$db = new DBObject();
$famu = new Familize($db);
try {
	switch($action) {

	// new token response
	case 'token':
		list($username, $password) = $_REQUEST['url'];
		$famu->authorize($username, $password, false, true);
		$token = $famu->getToken('rest');
		foreach ($famu->switch_user as $s_id=>$s_user) {
			$switcher .= "<user id=\"{$s_id}\" name=\"{$s_user}\" />\n";
		}
		$output = <<<EOD
		<auth>
			<token>{$token}</token>
			<user id="{$famu->signin_id}">{$famu->user['first_name']} {$famu->user['last_name']}</user>
			<switcher>{$switcher}</switcher>
		</auth>
EOD;
		break;

	// unseen content response
	case 'unseen':
		list($active_id, $token, $old) = $_REQUEST['url'];
		$famu->authByToken($token, 'rest');
		if ($active_id!='') {
			$famu->switchTo($active_id);
			$blogs = $famu->getUnseenBlogCount();
			$photo = $famu->getUnseenPhotoCount();
			if ($photo > 0) {
				$pho = $famu->getUnseenPhoto(1);
			} elseif ($old=='F') {
				$pho = $famu->getFavoritePhoto(1);
			} elseif ($old=='N') {
				$pho = $famu->getRecentPhoto(1);
			} else { // old=R
				$pho = $famu->getRandomPhoto(1);
			}
			$caption = htmlspecialchars($pho['caption']);
			$output = <<<EOD
			<unseen id="unseen">
				<blogs>{$blogs}</blogs>
				<photos>{$photo}</photos>
			</unseen>
			<photos>
				<photo id="newest" uid="{$pho['photo_uid']}" owner="{$pho['user_id']}" server="{$pho['server_id']}" ext="{$pho['ext']}" title="{$caption}" comments="{$pho['comments']}" comment_dt="{$pho['newest']}" />
			</photos>
EOD;
		}
		break;
	default:
		$error[] = "<error code=\"102\">Unknown Action: {$action}</error>";
	}
} catch (User_InvalidException $e) {
	$error[] = '<error code="101">'.htmlspecialchars($e->getMessage()).'</error>';
} catch (Exception $e) {
	$error[] = '<error code="100">'.htmlspecialchars($e->getMessage()).'</error>';
}

// format error output
$errCnt = count($error);
if ($errCnt >0) {
	$errStr = implode("\n\t", $error);
}

print <<<EOD
-->
<error count="{$errCnt}">{$errStr}</error>
{$output}
</response>
EOD;

?>
