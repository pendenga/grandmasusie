<?php
/**
 * Familize Class
 *
 * Functions to support logged in users directly.  Inherited functions support
 * users in general.
 */

include_once 'gtools.php';
include_once 'dbobject.php';
include_once 'template.php';
include_once 'userobject.php';

class User_InvalidException extends Exception{};
class User_InactiveException extends Exception{};

class Familize extends UserObject implements SiteUser {
    const CONFIG_FILE = '/../conf/config.xml';

	protected $db;
	protected $config;
	protected $site_info;
	public $moderator = false;
	public $signedIn = false;
	public $active_id = false;
	public $signin_id = false;
	public $signin_name;
	public $user_list = false;
	//public $template = "GreenTemplate";
	public $template = "GomezTemplate";
	public $site_cache;

    // default seconds for cookie to expire (7776000 = 90 days)
	const USER_COOKIE_EXPIRE = 7776000;

	function __construct(DBObject &$db, $log=true) {
		$this->db = $db;

        $xml = simplexml_load_file(realpath(dirname(__FILE__) . self::CONFIG_FILE));
        $this->site_cache = trim($xml->cache);

		//$this->discoverSiteKey();
		$this->discoverUser($log);
		//include_once '../tmpl/green/template.php';
		include_once '../tmpl/gomez/template.php';
	}

	function allUserOptions($active='') {
		$all = $this->db->do_sql("SELECT user_id, first_name, last_name FROM users ORDER BY last_name, first_name");
		$selected[$active] = 'selected="selected"';
		$options = '<option value="-1">-- None Selected --</option>';
		foreach ($all as $user) {
			$options .= "<option value=\"{$user['user_id']}\" {$selected[$user['user_id']]}>{$user['first_name']} {$user['last_name']}</option>\n";
		}
		return $options;
	}

	function authorize($username, $password, $persist=false, $md5ed=false) {
		GTools::logOutput("authorizing: $username, $password, $persist");
		$user = $this->db->do_sql("SELECT * FROM users WHERE username='{$username}'");

		if (count($user) > 1) {
			throw new User_InvalidException("More than one username selected: '{$username}':'{$password}'");
		} elseif (count($user) < 1) {
			throw new User_InvalidException("Invalid username: '{$username}':'{$password}'");
		} elseif ($user[0]['active']==0) {
			throw new User_InactiveException("Inactive user tried to log in: '{$username}':'{$password}'");
		} elseif ($md5ed && md5($user[0]['password'])==$password) {
			$this->setSignedIn($user[0]['user_id'], "{$user[0]['first_name']} {$user[0]['last_name']}", $user[0], $persist);
			return $user[0];
		} elseif ($user[0]['password'] == $password) {
			$this->setSignedIn($user[0]['user_id'], "{$user[0]['first_name']} {$user[0]['last_name']}", $user[0], $persist);
			return $user[0];
		} else {
			$new_attempts = $user[0]['num_attempts']+1;
			$this->db->do_sql("UPDATE users SET num_attempts={$new_attempts} WHERE username='{$username}'");
			throw new User_InvalidException("Invalid password: '{$username}':'{$password}'");
		}
	}

	/**
	 * Authorize the user solely on the token.  The source must match up with the source stored in the
	 * database.
	 */
	function authByToken($token, $source) {
		GTools::logOutput("authorizing: $token, $source");
		$user = $this->db->do_sql("SELECT * FROM users u INNER JOIN user_token t ON u.user_id=t.user_id WHERE t.token='{$token}' AND t.active=1 AND (t.expires IS NULL OR t.expires>NOW())");
		if (count($user) > 1) {
			throw new User_InvalidException("More than one username selected: '{$token}'");
		} elseif (count($user) < 1) {
			throw new User_InvalidException("Invalid token: '{$token}'");
		} elseif ($user[0]['source']!=$source) {
			throw new User_InactiveException("Invalid source: {$source} (This token: {$user[0]['source']})");
		} elseif ($user[0]['active']==0) {
			throw new User_InactiveException("Inactive user tried to log in: '{$token}'");
		} elseif ($user[0]['token']==$token) {
			$this->setSignedIn($user[0]['user_id'], "{$user[0]['first_name']} {$user[0]['last_name']}", $user[0], false);
			GTools::logOutput(" authorized {$user[0]['user_id']}, token {$user[0]['token_id']}, from {$_SERVER['REMOTE_ADDR']}");
			return $user[0];
		} else {
			$new_attempts = $user[0]['num_attempts']+1;
			$this->db->do_sql("UPDATE users SET num_attempts={$new_attempts} WHERE username='{$username}'");
			throw new User_InvalidException("Invalid password: '{$username}':'{$password}'");
		}
	}


	function bloggerCloud($date1, $date2) {
		$rs = $this->db->do_sql("call bloggerTagCloud('{$date1}', '{$date2}')");
		foreach ($rs as $u) {
			$size = ($u['countPercent']*1.5)+.7;
			$comment = ($u['entryCount']==1) ? 'entry' : 'entries';
			$form .= "<span style=\"font-size: {$size}em\" class=\"help\" title=\"{$u['first_name']} {$u['last_name']} has posted {$u['entryCount']} {$comment}\">{$u['first_name']}</span> ";
		}
		return $form;
	}

	function commenterCloud($date1, $date2) {
		$rs = $this->db->do_sql("call commentTagCloud('{$date1}', '{$date2}')");
		foreach ($rs as $u) {
			$size = ($u['countPercent']*1.5)+.7;
			$comment = ($u['commentCount']==1) ? 'comment' : 'comments';
			$form .= "<span style=\"font-size: {$size}em\" class=\"help\" title=\"{$u['first_name']} {$u['last_name']} has left {$u['commentCount']} {$comment}\">{$u['first_name']}</span> ";
		}
		return $form;
	}

	function interestingCloud($date1, $date2, $score_threshold=5) {
		$rs = $this->db->do_sql("call interestingTagCloud('{$date1}', '{$date2}')");
		foreach ($rs as $u) {
			if ($u['score_avg'] > $score_threshold) {
				$size = ($u['score_pct']*1.5)+.7;
				$form .= "<span style=\"font-size: {$size}em\" class=\"help\" title=\"{$u['first_name']} {$u['last_name']} has an interest score of: {$u['score']} on {$u['counter']} posts, average ".sprintf("%0.2f", $u['score_avg'])." per post.\">{$u['first_name']}</span> ";
			}
		}
		return $form;
	}

	function featuredCloud($date1, $date2) {
		$rs = $this->db->do_sql("call photoAllFeatured('{$date1}', '{$date2}')");
		foreach ($rs as $u) {
			$size = $u['countPercent']+1;
			$form .= "<a href=\"/user/{$u['user_id']}/featured/\" style=\"font-size: {$size}em\" class=\"help\" title=\"{$u['first_name']} {$u['last_name']} is featured in {$u['photoCount']} photos\">{$u['first_name']}</a> ";
		}
		return $form;
	}

	function quantityCloud($date1, $date2, $score_threshold=20) {
		$rs = $this->db->do_sql("call quantityTagCloud('{$date1}', '{$date2}')");
		foreach ($rs as $u) {
			if ($u['score'] > $score_threshold) {
				$size = ($u['score_pct']*1.5)+.7;
				$form .= "<span style=\"font-size: {$size}em\" class=\"help\" title=\"{$u['first_name']} {$u['last_name']} has posted {$u['photoCount']} photos, {$u['blogCount']} blogs, {$u['commentCount']} comments; score: {$u['score']}.\">{$u['first_name']}</span> ";
			}
		}
		return $form;
	}

	function tagCloud($threshold=1) {
		$rs = $this->db->do_sql("call tagCloud()");
		foreach ($rs as $u) {
			if ($u['tag_count'] > $threshold) {
				//$size = ($u['countPercent']*1.5)+.7;
				$size = ($u['countPercent'])+1;
				$form .= "<a href=\"/tags/{$u['tag']}/\" style=\"font-size: {$size}em\" class=\"help\" title=\"{$u['tag']} is tagged {$u['tag_count']} times ({$u['blog_count']} blogs, {$u['photo_count']} photos)\">{$u['tag']}</a> ";
			}
		}
		return $form;
	}

	function uploaderCloud($date1, $date2) {
		$rs = $this->db->do_sql("call photoUploadTagCloud('{$date1}', '{$date2}')");
		foreach ($rs as $u) {
			$size = ($u['countPercent']*1.5)+.7;
			$comment = ($u['photoCount']==1) ? 'photo' : 'photos';
			$form .= "<span style=\"font-size: {$size}em\" class=\"help\" title=\"{$u['first_name']} {$u['last_name']} has uploaded {$u['photoCount']} {$comment}\">{$u['first_name']}</span> ";
		}
		return $form;
	}

	function getRecentVisitors() {
		return $this->db->do_sql("SELECT u.first_name, u.last_name, u.avatar_id, ua.user_id, ua.updated_dt FROM user_activity ua INNER JOIN users u ON u.user_id=ua.user_id WHERE u.passive=0 AND u.user_id != {$this->active_id} ORDER BY updated_dt DESC LIMIT 20");
	}

	function getSwitchOptions() {
		$selected[$this->active_id] = 'selected="selected"';
		foreach ($this->switch_user as $uid=>$name) {
			$options .= "<option value=\"{$uid}\" {$selected[$uid]}>{$name}</option>\n";
		}
		return $options;
	}

	function getFlaggedCount() {
		$photos = $this->db->do_sql("SELECT COUNT(*) AS flagCount FROM photo_flag WHERE user_id={$this->active_id}");
		$blogs = $this->db->do_sql("SELECT COUNT(*) AS flagCount FROM blog_entry_flag WHERE user_id={$this->active_id}");
		return $photos[0]['flagCount']+$blogs[0]['flagCount'];
	}

	function getPasswordList() {
		return $this->db->do_sql("SELECT u.username, u.password FROM users u INNER JOIN site_member m ON u.user_id=m.user_id AND m.site_id=1 WHERE u.username != '' AND u.password != '' AND u.username != u.password ORDER BY u.username");
	}

	function getUnseenPhoto($limit=1, $offset=0) {
		$rs = $this->db->do_sql("call photoUnseenComment({$this->active_id}, 30, {$limit}, {$offset})");
		return $rs[0];
	}

	function getUserOptions() {
		foreach ($this->user_list as $user) {
			$list .= "<option value=\"{$user['user_id']}\">{$user['first_name']} {$user['last_name']}</option>\n";
		}
		return $list;
	}

	/**
	 * Gets a random photo from the user's set of favorites
	 */
	function getFavoritePhoto($limit=1) {
		$rs = $this->db->do_sql("call favoritePhotoCount({$this->active_id})");
		$count = $rs[0]['photoCount'];
		$offset = rand(1, $count);
		$rs = $this->db->do_sql("call favoritePhoto({$this->active_id}, {$limit}, {$offset})");
		return $rs[0];
	}

	/**
	 * Gets a random photo from the complete set of photos
	 */
	function getRandomPhoto($limit=1) {
		$rs = $this->db->do_sql("SELECT count(*) photoCount FROM photo WHERE complete=1");
		$count = $rs[0]['photoCount'];
		$offset = rand(1, $count);
		$rs = $this->db->do_sql("call recentPhoto({$this->active_id}, {$limit}, {$offset})");
		return $rs[0];
	}

	/**
	 * Gets a random photo from recently posted photos
	 */
	function getRecentPhoto($limit=1) {
		$offset = rand(1, 49);
		$rs = $this->db->do_sql("call recentPhoto({$this->active_id}, {$limit}, {$offset})");
		return $rs[0];
	}

	/**
	 * Gets an authorization token for the current user
	 */
	function getToken($source) {
		$rs = $this->db->do_sql("SELECT * FROM user_token WHERE active=1 AND user_id={$this->signin_id} AND source='{$source}' AND (expires IS NULL OR expires>NOW())");
		if (count($rs)>0) {
			return $rs[0]['token'];
		} else {
			$tok = uniqid();
			$this->db->do_sql("INSERT INTO user_token (token, user_id, source) VALUES ('{$tok}', {$this->signin_id}, '{$source}')");
			return $tok;
		}
	}

	function getUnseenBlogCount() {
		$rs = $this->db->do_sql("call blogUnseenCount({$this->active_id}, 30)");
		return $rs[0]['blogCount'];
	}

	function getUnseenPhotoCount() {
		$rs = $this->db->do_sql("call photoUnseenCount({$this->active_id}, 30)");
		return $rs[0]['photoCount'];
	}

	function getUpcomingAnniversaries($days=30) {
		$today = date('Y-m-d');
		return $this->db->do_sql("CALL userAnniversaryList('{$today}', {$days})");
	}

	/**
	 * Get birthday list
	 */
	function getUpcomingBirthdays($days=30) {
		$today = date('Y-m-d');
		return $this->db->do_sql("CALL userBirthdayList('{$today}', {$days})");
	}

	/**
	 * Update user activity every time someone changes the page
	 */
	function logActivity() {
		$this->db->do_sql("REPLACE INTO user_activity (user_id) VALUES ({$this->active_id})");
	}

	/**
	 * Call this at the top of any given page to check that a valid user is
	 * logged in.  Also log this user's visit to the site.
	 * You can specify a page to redirect to if the user is not logged in.
	 */
	function loginCheck($redirectTo='/signin/') {
		// not signed in
		if (!$this->signedIn) {
			if ($redirectTo===false) {
				die();
			} else {
				GTools::logOutput("Login Check Failed: redirecting to {$redirectTo}");
				header("location: {$redirectTo}");
			}
		}

		// no longer active
		if ($this->user['active'] == 0) {
			header("location: /signout/");
		}
		$this->logActivity();

		// if username and password are the same, force user to change password.
		if ($this->user['user_id']==$this->signin_id && $this->user['username']==$this->user['password'] && $_SERVER['SCRIPT_URL']!='/useredit/password/matching/') {
			header('location: /useredit/password/matching/');
		}
	}

	function switchTo($uid) {
		if ($this->active_id == $uid) {
			return true;
		} elseif (in_array($uid, $this->switch_list)) {
			$this->active_id = $uid;
			$_SESSION['active_id'] = $uid;
			$this->user = $this->loadUser($this->active_id);
			$this->logActivity();
		} else {
			throw new User_InvalidException("Invalid SwitchTo request.  {$this->active_id} trying to switch to {$uid}");
		}
	}

	function restoreAvatar() {
		// restore avatar (not if it's blank)
		if ($this->signedIn && $this->user['avatar_prev']!='') {
			$this->db->do_sql("UPDATE users SET avatar_id='{$this->user['avatar_prev']}', avatar_prev=NULL WHERE user_id={$this->active_id} LIMIT 1");
			$this->user['avatar_id']=$this->user['avatar_prev'];
			$this->user['avatar_prev']='';
			return true;
		} else {
			return false;
		}
	}

	function setAvatar($avatar_id, $user_id=false) {
		// setting for another user (must be moderator)
		if ($user_id!='' && $this->moderator && $user_id!=$this->active_id) {
			$this->db->do_sql("UPDATE users SET avatar_prev=avatar_id WHERE user_id={$user_id} LIMIT 1");
			$this->db->do_sql("UPDATE users SET avatar_id='{$avatar_id}' WHERE user_id={$user_id} LIMIT 1");

		// setting own avatar (don't set prev if it's the same)
		} elseif ($avatar_id != $this->user['avatar_id']) {
			$this->db->do_sql("UPDATE users SET avatar_prev=avatar_id WHERE user_id={$this->active_id} LIMIT 1");
			$this->db->do_sql("UPDATE users SET avatar_id='{$avatar_id}' WHERE user_id={$this->active_id} LIMIT 1");
			$this->user['avatar_prev']=$this->user['avatar_id'];
			$this->user['avatar_id']=$avatar_id;
		}
	}

	function setPasswordList() {
        $xml = simplexml_load_file(realpath(dirname(__FILE__) . self::CONFIG_FILE));
        $fh = fopen(trim($xml->htpasswd), 'w');
		$pwd = $this->getPasswordList();
		foreach ($pwd as $user) {
			$pass = crypt($user['password'],base64_encode($user['password']));
			fwrite($fh, "{$user['username']}:{$pass}\n");
		}
		fclose($fh);
	}

	function setSignedOut() {
		unset($_SESSION['active_id']);
		unset($_SESSION['signin_id']);
		unset($_SESSION['user_list']);
		unset($_SESSION['signin_name']);
		unset($_SESSION['switch_list']);
		unset($_SESSION['switch_user']);
		setcookie('authorize', false, 0, '/');

		$this->user = false;
		$this->user_id = false;
		$this->signedIn = false;
		$this->signin_id = false;
		$this->signin_name = false;
		$this->active_id = false;
		$this->switch_list = false;
		$this->switch_user = false;
	}

	/* PRIVATE FUNCTIONS */
	/* PRIVATE FUNCTIONS */
	/* PRIVATE FUNCTIONS */

	private function discoverSiteKey() {
		// discover site key
		$url = (is_array($_REQUEST['url'])) ? $_REQUEST['url'] : array();
		if ($_SERVER['HTTP_HOST']=='sites.familize.com' && count($_REQUEST['url'])>0) {
			$this->site_key = array_shift($_REQUEST['url']);

		// get site id from url.  (can do a database lookup too)
		} elseif (preg_match('/www.(.+).com/', $_SERVER['HTTP_HOST'], $matches)) {
			$this->site_key = $matches[1];

		//site id is not present
		} else {
			$this->site_key = 'familize';
		}
	}

	private function discoverUser($log=true) {
		// load user from session with active user
		if (isset($_SESSION['active_id']) && isset($_SESSION['signin_id'])) {
			$user = $this->loadUser($_SESSION['active_id']);
			if ($user) {
				if ($log) {
					GTools::logOutput(" session user {$_SESSION['signin_id']}-{$_SESSION['signin_name']}, {$_SESSION['active_id']}-{$user['first_name']} ({$_SERVER['PHP_SELF']})");
				}
				$this->setSignedIn($_SESSION['signin_id'], $_SESSION['signin_name'], $user);
				return $user;
			}

		// just load signin user
		} elseif (isset($_SESSION['signin_id'])) {
			$user = $this->loadUser($_SESSION['signin_id']);
			if ($user) {
				GTools::logOutput(" found session user {$_SESSION['signin_id']}, {$_SESSION['signin_name']}");
				$this->setSignedIn($_SESSION['signin_id'], $_SESSION['signin_name'], $user);
				return $user;
			}

		// load user from cookie
		} elseif (isset($_COOKIE['authorize'])) {
			$auth = explode('|', GTools::decrypt($_COOKIE['authorize']));
			$user = $this->loadUser($auth[0]);
			if ($user) {
				GTools::logOutput(" found cookie user {$auth[0]}, {$auth[1]}");
				$this->setSignedIn($auth[0], $auth[1], $user);
				return $user;
			}
		}
		return false;
	}

	private function setSignedIn($signin_id, $signin_name, array $user, $persist=false) {
		$this->signedIn = true;
		$this->user = $user;
		$this->active_id = $user['user_id'];
		$this->moderator = ($user['moderator']==1) ? true : false;
		$_SESSION['active_id'] = $user['user_id'];

		// set signin id in session
		$this->signin_id = $signin_id;
		$this->switch_list[] = $signin_id;
		$_SESSION['signin_id'] = $signin_id;
		$this->signin_name = $signin_name;
		$_SESSION['signin_name'] = $signin_name;

		// set override users
		if ($_SESSION['switch_list']=='') {
			$list = $this->db->do_sql("SELECT user_id, first_name, last_name, avatar_id FROM users WHERE user_id IN (SELECT overrides FROM user_override WHERE user_id={$signin_id}) OR (passive=1 AND owner={$signin_id} OR owner IN (SELECT overrides FROM user_override WHERE user_id={$signin_id})) GROUP BY user_id, first_name, last_name, avatar_id ORDER BY last_name, first_name");
			foreach ($list as $user) {
				$this->switch_list[] = $user['user_id'];
				$this->switch_user[$user['user_id']] = "{$user['first_name']} {$user['last_name']}";
			}
			$_SESSION['switch_user']=$this->switch_user;
			$_SESSION['switch_list']=$this->switch_list;
		} else {
			$this->switch_list=$_SESSION['switch_list'];
			$this->switch_user=$_SESSION['switch_user'];
		}

		// set user list
		if ($_SESSION['user_list']=='') {
			$_SESSION['user_list']=$this->db->do_sql("SELECT u.user_id, u.first_name, u.last_name, u.avatar_id FROM users u INNER JOIN site_member s ON u.user_id=s.user_id AND s.site_id={$this->site_id} ORDER BY u.last_name, u.first_name");
		}
		$this->user_list=$_SESSION['user_list'];

		// set cookie for sign in next time
		if ($persist) {
			$auth = GTools::encrypt("$signin_id|$signin_name");
			$expire = time()+self::USER_COOKIE_EXPIRE;
			$set = setcookie('authorize', $auth, $expire,'/');
		}
		return true;
	}
}

?>
