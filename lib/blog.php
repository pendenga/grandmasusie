<?php

/**
 * Defines operations to take place on a single blog entry.
 */
class Blog {
	public $info = false;
	public $last;
	protected $db;
	protected $famu;

	function __construct(DBObject &$db, SiteUser &$famu, $initialize=true) {
		$this->db = $db;
		$this->famu = $famu;

		if ($initialize) {
			$info = $this->db->do_sql("SELECT * FROM blog WHERE user_id={$famu->active_id}");
			if (count($info)==0) {
				$this->last = false;
				$this->info = false;
			} else {
				$this->last = false;
				$this->info = $info[0];
			}
		}
	}

	function getBlogEntry($entry_id) {
		$valid_ids = implode(',',$this->famu->switch_list);
		$blog = $this->db->do_sql("CALL blogEntry({$this->famu->site_id},{$this->famu->active_id},'{$valid_ids}',{$entry_id})");

		if (count($blog)>0) {
			$last = $this->db->do_sql("CALL log_blog_view ({$this->famu->active_id}, {$entry_id}, 1)");
			$this->last = $last[0]['last_seen'];
			$this->info = $blog[0];
		} else {
			$this->last = false;
			$this->info = array();
		}
	}

	function assignTag($tag_text, $suggested=false) {
		GTools::logOutput("assigning tag: $tag_text");
		$suggestion = ($suggested) ? 1 : 0;
		if ($tag_text != '') {
			$rs = $this->db->do_sql("SELECT * FROM tag WHERE tag='{$tag_text}'");
			if (count($rs) < 1) {
				$this->db->do_sql("INSERT INTO tag (tag) VALUES ('{$tag_text}')");
				$rs = $this->db->do_sql("SELECT LAST_INSERT_ID() AS tag_id");
			}
			$tag_id = $rs[0]['tag_id'];
			$this->db->do_sql("REPLACE INTO blog_tag (entry_id, tag_id, user_id, suggested) VALUES ({$this->info['entry_id']}, {$tag_id}, {$this->famu->active_id}, {$suggestion})");
			return true;
		}
		return false;
	}

	function unassignTag($tag_id) {
		if ($tag_id != '') {
			$this->db->do_sql("DELETE FROM blog_tag WHERE entry_id={$this->info['entry_id']} AND tag_id={$tag_id} LIMIT 1");
			return true;
		}
		return false;
	}

	function getTagsAssigned() {
		return $this->db->do_sql("CALL blogGetTags({$this->famu->site_id}, {$this->info['entry_id']})");
	}

	function getCategories($entry_id) {
		return $this->db->do_sql("CALL blogGetCategories({$this->famu->site_id},{$entry_id})");
	}

	function getUnreadComments($limit=20) {
		return $this->db->do_sql("call blogUnseenComment({$this->famu->active_id}, 30, {$limit}, 0)");
	}

	function getFlaggedEntries() {
		return $this->db->do_sql("SELECT be.entry_id, be.user_id, u.first_name, u.last_name, u.avatar_id, b.blog_name, b.blog_id, be.title, be.content, be.updated_dt, be.active, COUNT(bc.comment_id) AS comments FROM blog_entry_flag bef INNER JOIN blog_entry be ON be.entry_id=bef.entry_id INNER JOIN blog b ON b.blog_id=be.blog_id INNER JOIN users u ON be.user_id=u.user_id LEFT OUTER JOIN blog_comment bc ON bc.entry_id=bef.entry_id WHERE bef.user_id={$this->famu->active_id} GROUP BY be.user_id, u.first_name, u.last_name, u.avatar_id, b.blog_name, be.title, be.content, be.updated_dt");
	}

	function getTaggedEntries($tag_id, $limit=20, $offset=0) {
		return $this->db->do_sql("CALL blogSetTags({$this->famu->site_id}, {$tag_id}, {$limit}, {$offset})");
	}

	/**
	 * Insert new blog by name, return the blog record.
	 */
	function newBlog($name) {
		$name = addslashes($name);
		$this->db->do_sql("INSERT INTO blog (user_id, blog_name) VALUES ({$this->famu->active_id}, '{$name}')");
		$info = $this->db->do_sql("SELECT * FROM blog WHERE blog_id=LAST_INSERT_ID()");
		$this->info = $info[0];
	}

	/**
	 * Insert new blog by name, return the blog record.
	 */
	function saveBlog($name) {
		$name = addslashes($name);
		$this->db->do_sql("UPDATE blog SET blog_name='{$name}' WHERE user_id={$this->famu->active_id}");
	}

	/**
	 * Insert new blog by name, return the blog record.
	 */
	function newEntry($title, $content, $active) {
		$title = addslashes($title);
		$content = addslashes($content);
		$active = 1; //($active) ? 1 : 0;
		$this->db->do_sql("INSERT INTO blog_entry (blog_id, user_id, title, content, active) VALUES ({$this->info['blog_id']}, {$this->famu->active_id}, '{$title}', '{$content}', {$active})");
		$info = $this->db->do_sql("SELECT LAST_INSERT_ID() AS entry_id");
		return $info[0]['entry_id'];
	}

	function saveActive($entry_id, $active) {	
		$active = ($active) ? 1 : 0;
		$valid_ids = implode(',',$this->famu->switch_list);
		$this->db->do_sql("UPDATE blog_entry SET active={$active} WHERE entry_id={$entry_id} AND user_id IN ({$valid_ids})");
		$this->info['active'] = $active;
	}

	function saveFlagged($entry_id, $flagged) {
		$flagged = ($flagged) ? 1 : 0;
		if ($flagged) {
			$this->db->do_sql("REPLACE INTO blog_entry_flag (entry_id, user_id) VALUES ({$entry_id}, {$this->famu->active_id})");
			$this->info['flagged'] = 1;
		} else {
			$this->db->do_sql("DELETE FROM blog_entry_flag WHERE entry_id={$entry_id} AND user_id={$this->famu->active_id} LIMIT 1");
			$this->info['flagged'] = 0;
		}
	}

	function saveEntry($entry_id, $title, $content, $active) {
		$valid_ids = implode(',',$this->famu->switch_list);
		$title = addslashes($title);
		$content = addslashes($content);
		$active = 1; //($active) ? 1 : 0;
		$this->db->do_sql("UPDATE blog_entry SET title='{$title}', content='{$content}', active={$active} WHERE entry_id={$entry_id} AND user_id IN ({$valid_ids})");
	}

	function saveTitle($entry_id, $title) {
		$valid_ids = implode(',',$this->famu->switch_list);
		$this->info['title'] = $title;
		$title = addslashes($title);
		$this->db->do_sql("UPDATE blog_entry SET title='{$title}' WHERE entry_id={$entry_id} AND user_id IN ({$valid_ids})");
	}

	function saveContent($entry_id, $content) {
		$valid_ids = implode(',',$this->famu->switch_list);
		$this->info['content'] = $content;
		$content = addslashes($content);
		$this->db->do_sql("UPDATE blog_entry SET content='{$content}' WHERE entry_id={$entry_id} AND user_id IN ({$valid_ids})");		
	}
}