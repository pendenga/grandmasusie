<?php
include_once '../helpers/formatlongtext.php';
include_once '../helpers/formattime.php';

/**
 * Defines operations to take place on a single blog entry.
 */
class CommentForm extends Module {
	public $info;
	public $comments;
	public $waitingPeriod = 20; // minutes to delete comment
	protected $commentCount = 0;
	protected $db;
	protected $famu;
	protected $type='blog';

	function alertPhotoComment($photo_id, $user_id) {
		$users = $this->db->do_sql("SELECT u.first_name, u.email_address, u.sms_number, u.sms_carrier, u.pref_sms, p.caption, p.description, p.filename FROM photo_alert pa INNER JOIN users u ON u.user_id=pa.user_id INNER JOIN photo p ON p.photo_id=pa.photo_id WHERE pa.photo_id={$photo_id} AND pa.user_id != {$user_id} AND ((u.pref_sms=1 AND sms_confirmed=1) OR (u.pref_sms=0 AND email_confirmed=1))");
		$p = $users[0];
		if ($p['caption']) {
			$photo = GTools::truncateParagraph($p['caption'], 25);
		} elseif ($p['description']) {
			$photo = GTools::truncateParagraph($p['description'], 25);
		} elseif ($p['filename']) {
			$photo = GTools::truncateParagraph($p['filename'], 25);
		} else {
			$photo = "A photo you've been watching";
		}
		foreach ($users as $u) {
			$alert = "{$u['first_name']}, {$photo} has recieved a comment.  Thought you might like to know.";
			if ($u['pref_sms']==1) {
				GTools::logOutput(" SMS Alert to {$u['sms_number']}");
				mail("{$u['sms_number']}@{$u['sms_carrier']}", "ALERT", $alert);
			} else {
				GTools::logOutput(" Email Alert to {$u['email_address']}");
				mail($u['email_address'], "Photo Comment Alert", $alert);
			}
		}
	}

	function deleteBlogComment($entry_id, $comment_id) {
		$rdata = $this->db->do_sql("SELECT * FROM blog_comment WHERE entry_id={$entry_id} AND comment_id={$comment_id} AND user_id IN (".implode(',', $this->famu->switch_list).")");

		if (count($rdata)==1) {
			$wait = time() - strtotime($rdata[0]['updated_dt']);
			$peri = $this->waitingPeriod * 60;
			if ($peri-$wait > 0) {
				$rdata = $this->db->do_sql("UPDATE blog_comment SET active=0 WHERE entry_id={$entry_id} AND comment_id={$comment_id}");		
			}
		}
	}

	function deletePhotoComment($photo_id, $comment_id) {
		$rdata = $this->db->do_sql("SELECT * FROM photo_comment WHERE photo_id={$photo_id} AND comment_id={$comment_id} AND user_id IN (".implode(',', $this->famu->switch_list).")");

		if (count($rdata)==1) {
			$wait = time() - strtotime($rdata[0]['updated_dt']);
			$peri = $this->waitingPeriod * 60;
			if ($peri-$wait > 0) {
				$rdata = $this->db->do_sql("UPDATE photo_comment SET active=0 WHERE photo_id={$photo_id} AND comment_id={$comment_id}");		
			}
		}
	}

	function newBlogComment($entry_id, $comment, $user_id) {
		$comment = addslashes($comment);
		$this->db->do_sql("INSERT INTO blog_comment (entry_id, user_id, site_id, comment) VALUES ({$entry_id}, {$user_id}, 1, '{$comment}')");
	}

	function newPhotoComment($photo_id, $comment, $user_id) {
		$comment = addslashes($comment);
		$this->db->do_sql("INSERT INTO photo_comment (photo_id, user_id, site_id, comment) VALUES ({$photo_id}, {$user_id}, 1, '{$comment}')");
		$this->alertPhotoComment($photo_id, $user_id);
	}

	protected function formatComment($comment, $record_id) {
		$date = FormatTime::postTime($comment['updated_dt']);
		$body = FormatLongText::for_print($comment['comment']);
		$avtr = $this->famu->getFullAvatarLink($comment['avatar_id'], $comment['user_id'], $comment['full_name']);
		$unrd = ($comment['unread']==1 && $comment['user_id']!=$this->famu->active_id) ? ' class="unread"' : '';

		// figure waiting period for deleting comments
		$wait = time() - strtotime($comment['updated_dt']);
		$peri = $this->waitingPeriod * 60;
		$wfmt = GTools::formatDuration($peri-$wait);

		if ($wait < $peri && in_array($comment['user_id'], $this->famu->switch_list)) {
			$remo = <<<EOD
			<input type="button" class="smallbuttons" value="oops, delete this!" title="{$wfmt} left to delete this comment" onclick="new Ajax.Updater('comments_{$record_id}', '/comment_server/{$this->type}/{$record_id}/', { method: 'post', parameters: 'delete[comment]={$comment['comment_id']}', onComplete: function() { new Effect.Highlight('comments_{$record_id}'); }});"/>
EOD;
		}
		return <<<EOD

			<li{$unrd}>{$avtr}
				<div class="author">{$comment['full_name']} says: $remo</div>
				<div class="comment">{$body}</div>
				<div class="footnote">Posted {$date}</div></li>
EOD;
	}

	function getPage($record_id) {
		$switch = $this->famu->getSwitchOptions();
		$title = ($this->commentCount > 0) ? "<h3>Comments</h3>" : '';
		return <<<EOD
		<div class="commentFrame">
			{$title}
			<ul id="comments_{$record_id}">{$this->comments}</ul>
			<form id="new_comment_form" class="commentForm" method="POST">
				<h3>Leave a Comment</h3>
				<textarea cols="30" rows="3" name="new[comment]" id="new_comment"></textarea>
				<div class="tools">
					<div class="instructions">Use <a href="http://daringfireball.net/projects/markdown/dingus">Markdown</a> for text formatting</div>
					<div class="detail">Sign comment as:</div>
					<select name="new[user_id]" class="smallselect">
					<option value="{$this->famu->signin_id}">{$this->famu->signin_name}</option>
					{$switch}</select>
					<div class="submit"><input type="submit" class="buttons" value="post comment" onclick="new Ajax.Updater( 'comments_{$record_id}', '/comment_server/{$this->type}/{$record_id}/', { method: 'post', parameters: $('new_comment_form').serialize(true), onComplete: function(){ $('new_comment_form').reset(); }, onFailure: function() { alert('Something went wrong... please describe what happened in as much detail as you can on the feedback form.'); }}); return false;"/> <input type="button" class="smallbuttons" value="check for new comments" onclick="new Ajax.Updater('comments_{$record_id}', '/comment_server/{$this->type}/{$record_id}/', { onComplete: function() { new Effect.Highlight('comments_{$record_id}'); }});"/>
					</div>
				</div>
			</form>
		</div>
EOD;
	}
	
	function setBlogComments($entry_id, $last_seen) {
		$this->type = 'blog';
		$last_seen = date('Y-m-d H:i:s', strtotime($last_seen));
		$comments = $this->db->do_sql("call blogGetComments (1, {$entry_id}, '{$last_seen}')");
		$this->commentCount = count($comments);
		foreach ($comments as $comment) {
			$this->comments .= $this->formatComment($comment, $entry_id);
		}
	}

	function setPhotoComments($photo_id, $last_seen) {
		$this->type = 'photo';
		$last_seen = date('Y-m-d H:i:s', strtotime($last_seen));
		$comments = $this->db->do_sql("call photoGetComments (1, {$photo_id}, '{$last_seen}')");
		$this->commentCount = count($comments);
		foreach ($comments as $comment) {
			$this->comments .= $this->formatComment($comment, $photo_id);
		}
	}
}

?>
