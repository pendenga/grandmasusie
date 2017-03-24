<?php

include_once 'module.php';

class SearchForm extends Module {
	protected $db;

	function searchTags($phrase, $partial=false) {
		$partial = ($partial==true) ? '*' : '';
		$phrase = str_replace("'", '', $phrase);
		return $this->db->do_sql("SELECT tag_id, tag FROM tag WHERE MATCH(tag) AGAINST ('{$phrase}{$partial}' IN BOOLEAN MODE) LIMIT 6");
	}

	function searchTagsForList($phrase, $partial=false) {
		$tags = $this->searchTags($phrase, $partial);
		if (count($tags)>0) {
			foreach ($tags as $tag) {
				$output .= <<<EOD
					<li onclick="$('new_tags_id').value={$tag['tag_id']}"><div class="name">{$tag['tag']}</div></li>
EOD;
			}
			return "<ul class=\"contacts\">{$output}</ul>";
		} else {
			return "<ul class=\"contacts\"></ul>";
		}
	}

	function searchUsers($phrase, $partial=false) {
		$partial = ($partial==true) ? '*' : '';
		$phrase = str_replace("'", '', $phrase);
		return $this->db->do_sql("SELECT user_id, CASE WHEN nickname IS NOT NULL THEN CASE WHEN nickname_is_full=1 THEN nickname ELSE CONCAT(nickname, ' ', last_name) END ELSE CONCAT(first_name, ' ', last_name) END AS full_name, CASE WHEN nickname IS NOT NULL THEN nickname ELSE first_name END AS common_name, first_name, last_name, avatar_id, deceased FROM users WHERE MATCH(first_name, last_name, username, old_login, nickname) AGAINST ('{$phrase}{$partial}' IN BOOLEAN MODE) LIMIT 6");
	}

	/**
	 * Search for the given name first as a full name, then as a partial-match search.  
	 * Return the set of names and a flag if the name was unique.
	 */
	function searchUsersForFullName($name) {
		$rs = $this->db->do_sql("SELECT * FROM user_name WHERE full_name='{$name}'");
		if (count($rs)==1) {
			return array($rs, true);
		} else {
			$rs = $this->searchUsers($name, true);
			return array($rs, (count($rs)==1));
		}
	}

	function searchUsersForList($phrase, $partial=false) {
		$users = $this->searchUsers($phrase, $partial);
		if (count($users)>0) {
			foreach ($users as $user) {
				$avt_url = $this->famu->getFullAvatar($user['avatar_id']);
				$real = ($user['full_name'] != "{$user['first_name']} {$user['last_name']}") ? "{$user['first_name']} {$user['last_name']}" : '';
				$output .= <<<EOD
					<li class="contact" onclick="$('new_feat_id').value={$user['user_id']}"><div class="image"><img src="{$avt_url}" height="32"/></div><div class="name">{$user['full_name']}</div><div class="email"><span class="informal">{$real}</span></div></li>
EOD;
			}
			return "<ul class=\"contacts\">{$output}</ul>";
		} else {
			return "<ul class=\"contacts\"></ul>";
		}
	}

	function searchUsersForAvatar($phrase, $partial=false) {
		$users = $this->searchUsers($phrase, $partial);
		if (count($users)>0) {
			foreach ($users as $user) {
				$avt_url = $this->famu->getFullAvatar($user['avatar_id']);
				$output .= <<<EOD
					<a href="/user/{$user['user_id']}"><img src="{$avt_url}" title="{$user['first_name']} {$user['last_name']}" border="0"/></a>
EOD;
			}
			return $output;
		} else {
			return false;
		}
	}

	function countBlogEntries($phrase) {
		$phrase = str_replace("'", '', $phrase);
		$rs = $this->db->do_sql("SELECT COUNT(be.entry_id) AS entryCount FROM blog_entry be WHERE MATCH(be.title, be.content) AGAINST ('{$phrase}' IN BOOLEAN MODE)");
		return ($rs[0]['entryCount']);
	}

	function searchBlogEntries($phrase, $limit, $offset) {
		$phrase = str_replace("'", '', $phrase);
		$entries = $this->db->do_sql("SELECT be.entry_id, be.user_id, u.first_name, u.last_name, u.avatar_id, b.blog_name, b.blog_id, be.title, be.content, be.updated_dt, be.active, COUNT(bc.comment_id) AS comments FROM blog_entry be INNER JOIN blog b ON b.blog_id=be.blog_id INNER JOIN users u ON be.user_id=u.user_id LEFT OUTER JOIN blog_comment bc ON bc.entry_id=be.entry_id WHERE MATCH(be.title, be.content) AGAINST ('{$phrase}' IN BOOLEAN MODE) GROUP BY be.user_id, u.first_name, u.last_name, u.avatar_id, b.blog_name, be.title, be.content, be.updated_dt LIMIT {$limit} OFFSET {$offset}");
		foreach ($entries as $entry) {
			$output .= $this->formatBlogResult($entry);
		}
		return $output;
	}

	function countBlogComments($phrase) {
		$phrase = str_replace("'", '', $phrase);
		$rs = $this->db->do_sql("SELECT COUNT(be.entry_id) AS entryCount FROM blog_entry be INNER JOIN blog_comment bc ON be.entry_id=bc.entry_id WHERE MATCH(bc.comment) AGAINST ('{$phrase}' IN BOOLEAN MODE)");
		return ($rs[0]['entryCount']);
	}

	function searchBlogComments($phrase, $limit, $offset) {
		$phrase = str_replace("'", '', $phrase);
		$entries = $this->db->do_sql("SELECT be.entry_id, be.user_id, u.first_name, u.last_name, u.avatar_id, b.blog_name, b.blog_id, be.title, be.content, be.updated_dt, be.active, COUNT(bc.comment_id) AS comments FROM blog_entry be INNER JOIN blog b ON b.blog_id=be.blog_id INNER JOIN users u ON be.user_id=u.user_id INNER JOIN blog_comment bc ON bc.entry_id=be.entry_id WHERE MATCH(bc.comment) AGAINST ('{$phrase}' IN BOOLEAN MODE) GROUP BY be.user_id, u.first_name, u.last_name, u.avatar_id, b.blog_name, be.title, be.content, be.updated_dt LIMIT {$limit} OFFSET {$offset}");
		foreach ($entries as $entry) {
			$output .= $this->formatBlogResult($entry);
		}
		return $output;
	}

	protected function formatBlogResult($entry) {
		$avt_url = $this->famu->getFullAvatar($entry['avatar_id']);
		$posted = GTools::postTime($entry['updated_dt']);
		return <<<EOD
		<a href="/user/{$entry['user_id']}"><img src="{$avt_url}" title="{$entry['first_name']} {$entry['last_name']}" border="0" style="float: left"/></a> <strong class="title"><a href="/read/blog/{$entry['blog_id']}/{$entry['entry_id']}/">{$entry['title']}</a></strong><div class="detail">By {$entry['first_name']} {$entry['last_name']} - posted {$posted}</div><br style="clear: both; margin: 0; padding: 0; line-spacing: 0"/>
EOD;
	}
}
?>