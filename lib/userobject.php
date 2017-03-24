<?php

include_once 'dbobject.php';

class UserObject implements SiteUser {
    const CONFIG_FILE = '/../conf/config.xml';

	protected $db;
	protected $site_key = 'familize';
	public $switch_list = array();
	public $switch_user = array();
	//public $site_id = 5195;
	public $site_id = 1;
	public $user = array();

	function __construct(DBObject &$db, $user_id) {
		$this->db = $db;
		$this->active_id=$user_id;
		$this->refreshUser();
	}

	function contactInfo() {

	}

	function getBlogId() {
		$rs = $this->db->do_sql("SELECT blog_id FROM blog WHERE user_id={$this->active_id}");
		return (count($rs)>0) ? $rs[0]['blog_id'] : false;
	}

	function getUserID() {
		return $this->user['user_id'];
	}

	function getUserName() {
		return $this->user['full_name'];
	}

	function getFullAvatar($avatar_id=false) {
        $xml = simplexml_load_file(realpath(dirname(__FILE__) . self::CONFIG_FILE));
        $avatar_id = ($avatar_id===false) ? $this->user['avatar_id'] : $avatar_id;
		if (is_file(trim($xml->photo) . "avatar/{$avatar_id}.jpg")) {
			return "http://{$_SERVER['HTTP_HOST']}/static/avatar/{$avatar_id}.jpg";
		} else {
			return "http://{$_SERVER['HTTP_HOST']}/static/avatar/default.jpg";
		}
	}

	function getFullAvatarLink($avatar_id=false, $user_id='', $user_name='', $link_text='', $width=48) {
		$avtr = $this->getFullAvatar($avatar_id);
		return "<a href=\"/user/{$user_id}/\" class=\"help\" title=\"See {$user_name}'s profile, photos, and blog entries.\"><img src=\"{$avtr}\" class=\"avatar\" width=\"{$width}\"/>{$link_text}</a>";
	}

	function getCustomAvatarLink($avatar_id=false, $link_dest, $link_title, $link_text, $width=48) {
		$avtr = $this->getFullAvatar($avatar_id);
		return "<a href=\"{$link_dest}\" class=\"help\" title=\"{$link_title}\"><img src=\"{$avtr}\" class=\"avatar\" width=\"{$width}\"/>{$link_text}</a>";
	}


	/**
	 * Reload user data from the database
	 */
	function refreshUser() {
		$this->user = $this->loadUser($this->active_id);
	}

	/* PROTECTED FUNCTIONS */
	/* PROTECTED FUNCTIONS */
	/* PROTECTED FUNCTIONS */

	/**
	 * Load user data from database
	 */
	protected function loadUser($user_id) {
		$user = $this->db->do_sql("call userLookup({$this->site_id}, {$user_id})");
		return (count($user)>0) ? $user[0] : false;
	}

	protected function loadPrefs($user_id) {
		$preferences = array();
		$pref = $this->db->do_sql("SELECT pref_key, pref_value FROM user_preferences WHERE user_id={$user_id}");
		foreach ($pref as $p) {
			$preferences[$p['pref_key']] = $p['pref_value'];
		}
		return $preferences;
	}
}

?>
