<?php

include_once 'module.php';

abstract class Template implements ModuleDisplay {
	public $title = "Familize.com";
	public $ajax = false;
	public $icon;
	public $jsIncludes = array('/js/prototype.js', '/js/prototype_test.js', '/js/behaviour.js', '/js/scriptaculous.js', '/js/g_ajax.js', '/js/modalbox.js', '/js/tooltip_simple.js');
	protected $famu;
	protected $authorBlock;
	protected $jsSection;
	protected $includes;
	protected $pageList = '';
	protected $pageListDesc = '';

	function __construct(SiteUser &$famu) {
		//GTools::logOutput(($famu->signedIn) ? "Initializing Template (signed in)" : "Initializing Template (signed out)");
		$this->famu = $famu;

		// load icons
		$this->referenceImages($this->icon, 'icons');
	}

	function activeId() {
		return $this->famu->active_id;
	}

	function activeName($firstOnly=false) {
		if ($firstOnly) {
			return $this->famu->user['first_name'];
		} else {
			return "{$this->famu->user['first_name']} {$this->famu->user['last_name']}";
		}
	}

	function addJS($javascript) {
		$this->jsSection .= $javascript;
	}

	function getMainLinks() {
		if ($this->famu->signedIn) {
			$name = $this->famu->getUserName();
			$uid = $this->famu->getUserID();
			$userLinks = <<<EOD
			<li class="item-user">{$name}:</li>
			<li class="item-write"><a href="/write/">Write</a></li>
			<li class="item-upload"><a href="/upload/">Upload</a></li>
			<li class="item-photos"><a href="/user/{$this->famu->active_id}/photos/">Your Photos</a></li>
			<li class="item-faves"><a href="/user/{$this->famu->active_id}/favorites/">Favorites</a></li>
EOD;
		}
		return <<<EOD
		<ul class="mainLinks">
			<li class="item-home"><a href="/home/">Home</a></li>
			<li class="item-blog"><a href="/read/">Read</a></li>
			<li class="item-gallery"><a href="/gallery/">Gallery</a></li>
			<li class="item-forum"><a href="/home/">Forum</a></li>
			<li class="item-comics"><a href="/comics/">Comics</a></li>
			{$userLinks}
		</ul>
EOD;
	}

	function getSmsGatewayOptions($selected) {
		$checked[$selected] = 'selected="selected"';
		return <<<EOD
			<option value="NULL">- None Selected -</option>
			<option value="message.alltel.com" {$checked['message.alltel.com']}>Alltel</option>
			<option value="mobile.celloneusa.com" {$checked['mobile.celloneusa.com']}>Cellular One</option>
			<option value="cingularme.com" {$checked['cingularme.com']}>Cingular</option>
			<option value="mms.mycricket.com" {$checked['mms.mycricket.com']}>Cricket</option>
			<option value="page.nextel.com" {$checked['page.nextel.com']}>Nextel/Sprint</option>
			<option value="tmomail.net" {$checked['tmomail.net']}>T-Mobile</option>
			<option value="vtext.com" {$checked['vtext.com']}>Verizon</option>
EOD;
	}

	function getMonthOptions($selected, $short=true) {
		$shorts = array('null', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
		$longs = array('null', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
		$month = ($short) ? $shorts : $longs;
		for($i=1; $i<13; $i++) {
			$options .= "<option value=\"{$i}\">{$month[$i]}</option>";
		}
		return $options;
	}

	function getTimezoneOptions($tz_offset, $tz_dst) {
		$selected["{$tz_offset}|{$tz_dst}"] = 'selected="selected"';
		return <<<EOD
					<option value=""> - None Selected - </option>
					<option value="-12|0" {$selected['-12|0']}>-12:00 IDL West</option>
					<option value="-11|1" {$selected['-11|1']}>-11:00 Samoa</option>
					<option value="-10|1" {$selected['-10|1']}>-10:00 Hawaii</option>
					<option value="-9|1" {$selected['-9|1']}>-09:00 Alaska</option>
					<option value="-8|1" {$selected['-8|1']}>-08:00 Pacific Time</option>
					<option value="-7|0" {$selected['-7|0']}>-07:00 Arizona</option>
					<option value="-7|1" {$selected['-7|1']}>-07:00 Mountain Time</option>
					<option value="-6|1" {$selected['-6|1']}>-06:00 Central Time</option>
					<option value="-6|0" {$selected['-6|0']}>-06:00 Saskatchewan</option>
					<option value="-5|0" {$selected['-5|0']}>-05:00 Bogota</option>
					<option value="-5|1" {$selected['-5|1']}>-05:00 Eastern Time</option>
					<option value="-5|0" {$selected['-5|0']}>-05:00 Indiana (East)</option>
					<option value="-4|1" {$selected['-4|1']}>-04:00 Atlantic Time</option>
					<option value="-4|0" {$selected['-4|0']}>-04:00 Caracas</option>
					<option value="-3.5|1" {$selected['5|1']}>-03:30 Newfoundland</option>
					<option value="-3|1" {$selected['-3|1']}>-03:00 Brasilia</option>
					<option value="-3|0" {$selected['-3|0']}>-03:00 Buenos Aires</option>
					<option value="-2|0" {$selected['-2|0']}>-02:00 Mid-Atlantic</option>
					<option value="-1|1" {$selected['-1|1']}>-01:00 Azores</option>
					<option value="-1|0" {$selected['-1|0']}>-01:00 Cape Verde Is.</option>
					<option value="0|0" {$selected['0|0']}>+00:00 Casablanca</option>
					<option value="0|1" {$selected['0|1']}>+00:00 GMT: London</option>
					<option value="1|1" {$selected['1|1']}>+01:00 Amsterdam</option>
					<option value="1|0" {$selected['1|0']}>+01:00 West Africa</option>
					<option value="2|1" {$selected['2|1']}>+02:00 Jerusalem</option>
					<option value="2|0" {$selected['2|0']}>+02:00 Pretoria</option>
					<option value="3|1" {$selected['3|1']}>+03:00 Moscow</option>
					<option value="3|0" {$selected['3|0']}>+03:00 Nairobi</option>
					<option value="3.5|1" {$selected['5|1']}>+03:30 Tehran</option>
					<option value="4|0" {$selected['4|0']}>+04:00 Abu Dhabi</option>
					<option value="4|1" {$selected['4|1']}>+04:00 Tbilisi</option>
					<option value="4.5|0" {$selected['5|0']}>+04:30 Kabul</option>
					<option value="5|1" {$selected['5|1']}>+05:00 Ekaterinburg</option>
					<option value="5|0" {$selected['5|0']}>+05:00 Islamabad</option>
					<option value="5.5|0" {$selected['5|0']}>+05:30 New Delhi</option>
					<option value="5.75|0" {$selected['75|0']}>+05:45 Kathmandu</option>
					<option value="6|1" {$selected['6|1']}>+06:00 Novosibirsk</option>
					<option value="6|0" {$selected['6|0']}>+06:00 Dhaka</option>
					<option value="6.5|0" {$selected['5|0']}>+06:30 Rangoon</option>
					<option value="7|0" {$selected['7|0']}>+07:00 Bangkok</option>
					<option value="7|1" {$selected['7|1']}>+07:00 Krasnoyarsk</option>
					<option value="8|0" {$selected['8|0']}>+08:00 Hong Kong</option>
					<option value="9|0" {$selected['9|0']}>+09:00 Tokyo</option>
					<option value="9|1" {$selected['9|1']}>+09:00 Yakutsk</option>
					<option value="9.5|1" {$selected['5|1']}>+09:30 Adelaide</option>
					<option value="9.5|0" {$selected['5|0']}>+09:30 Darwin</option>
					<option value="10|0" {$selected['10|0']}>+10:00 Guam</option>
					<option value="10|1" {$selected['10|1']}>+10:00 Sydney</option>
					<option value="11|1" {$selected['11|1']}>+11:00 Soloman Is.</option>
					<option value="12|1" {$selected['12|1']}>+12:00 Auckland</option>
					<option value="12|0" {$selected['12|0']}>+12:00 Kamchatka</option>
					<option value="13|0" {$selected['13|0']}>+13:00 Nuku'alofa</option>
EOD;
	}

	function getLoginSwitcher($label="Active:") {
		if (count($this->famu->switch_list)==1) {
			return <<<EOD
			{$label} {$this->famu->signin_name}
EOD;
		} elseif (count($this->famu->switch_list)>1) {
			$over = $this->famu->getSwitchOptions();
			return <<<EOD
			<span class="help" title="Switch your active user without logging out and logging back in.  Switching will refresh the number of unread comments and blogs, and flagged entries.">{$label}</span> <select id="login_switcher">
				<option value="{$this->famu->signin_id}">{$this->famu->signin_name}</option>
				<optgroup label="Switch To...">
				{$over}
				</optgroup>
			</select>
EOD;
		}
	}

	function getUnseenChecker() {
		if ($this->famu->user['passive']==1) {
			return "PASSIVE USER";

		} else {
			$unseenBlogs = $this->famu->getUnseenBlogCount();
			$unseenPhotos = $this->famu->getUnseenPhotoCount();
			$flaggedItems = $this->famu->getFlaggedCount();

			// format unseen labels
			$photoLabel = ($unseenPhotos==1) ? '<span class="label"> photo</span>' : '<span class="label"> photos</span>';
			$blogLabel = ($unseenBlogs==1) ? '<span class="label"> blog</span>' : '<span class="label"> blogs</span>';
			$flagLabel = ($flaggedItems==1) ? '<span class="label"> flag</span>' : '<span class="label"> flags</span>';

			// output links
			return <<<EOD
			<a href="/gallery/newcomment/0/" title="Unseen Photos/Comments" class="alert-photo help">{$unseenPhotos}{$photoLabel}</a>,
			<a href="/read/comments/" title="Unseen Blogs/Comments" class="alert-blog help">{$unseenBlogs}{$blogLabel}</a>,
			<a href="/flagged/" title="Flagged Items" class="alert-flag help">{$flaggedItems}{$flagLabel}</a>
EOD;
		}
	}

	function getUserSignin() {
		if ($this->famu->signedIn) {
			// display user name
			$name = $this->famu->getUserName();
			$modr = ($this->famu->user['moderator']==1) ? '*' : '';

			// add unseen links and login switcher
			$checker = $this->getUnseenChecker();
			$switcher = $this->getLoginSwitcher();

			return <<<EOD
			Signed in as <span id="active_user"><strong><a href="/useredit/">{$name}{$modr}</a></strong></span> &nbsp; 
			<a href="http://www.grandmasusie.com/bb/index.php?c=3" target="_new">Help</a> &nbsp; 
			<a href="/signout/">Sign Out</a> &nbsp; 
			<a href="/feedback_server/" title="Feedback Form" class="modalbox">Feedback</a> &nbsp; 

			$output
			<div>
				New: <span id="unseen" class="{$hide}">{$checker}</span> 
				<input id="check_unseen" type="button" class="smallbuttons {$hide}" value="check"/>
				{$switcher}
			</div>
EOD;
		// not signed in
		} else {
			return "You aren't signed in &nbsp; <a href=\"/signin\">Sign In</a> &nbsp; <a href=\"http://www.grandmasusie.com/bb/index.php?c=3\" target=\"_new\">Help</a> &nbsp; ";
		}
	}

	function pageHeader() {
		foreach ($this->cssIncludes as $filename) {
			$stylesheetIncludes .= "<link href=\"{$filename}\" rel=\"stylesheet\" type=\"text/css\" />\n\t";
		}
		foreach ($this->jsIncludes as $filename) {
			$javascriptIncludes .= "<script type=\"text/javascript\" src=\"$filename\"> </script>\n\t";
		}
		print <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="shortcut icon" type="image/ico" href="/favicon.ico">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>{$this->title}</title>
	{$stylesheetIncludes}
	{$javascriptIncludes}
	<!--[if lt IE 7.]><script defer type="text/javascript" src="/js/pngfix.js"></script><![endif]-->
<script>{$this->jsSection}</script>
</head>

EOD;
	}

	function pageFooter() {
		print generatePageFooter();
	}

	function generatePageFooter() {
		return "</html>";
	}

	function recentVisitors() {
		$users = $this->famu->getRecentVisitors();
		foreach ($users as $user) {
			$date = GTools::postTime($user['updated_dt']);
			//$avat = $this->famu->getFullAvatar($user['avatar_id']);
			$name = "{$user['first_name']} {$user['last_name']}";
			$list .= <<<EOD
			<li style="clear: both">
				<a href="/user/{$user['user_id']}/" class="help" title="See {$name}'s profile, photos, and blog entries">{$name}</a>
				<span class="detail"><em>{$date}</em></span></li>
EOD;
		}
		return <<<EOD
		<div class="panel">
			<h3>Recent Visitors</h3>
			<ul id="visitorList">{$list}</li>
			</ul>
		</div>

EOD;
	}

	/**
	 * Loads images from the given directory into the given associative array.
	 * This makes it easier to reference images in a wierd template directory.
	 */
	public function referenceImages(&$refArray, $directory) {
		// preload all icons
		$fh = opendir("{$this->template_path}{$directory}");
		while ($file = readdir($fh)) {
			if (is_file("{$this->template_path}{$directory}/{$file}")) {
				$refArray[$file] = "{$this->template_uri}{$directory}/{$file}";
			}
		}
	}

	function setAuthorBlock($author) {
		$this->authorBlock = $author;
	}

	function setPageList(array $pageList) {
		$this->pageList = $pageList[0];
		$this->pageListDesc = $pageList[1];
	}

	function upcomingAnniversaries() {
		$annivs = $this->famu->getUpcomingAnniversaries();
		foreach ($annivs as $anniv) {
			$date = date('M j', strtotime($anniv['anniversary']));
			$anoLbl = ($anniv['numYears']==1) ? 'year' : 'years';
			$output .= "<li>{$date} - <strong>{$anniv['full_name']}</strong> <span class=\"detail\">{$anniv['numYears']} {$anoLbl}</span></li>";
		}
		if ($output != '') {
			return <<<EOD
			<div class="panel">
				<h3>Upcoming Anniversaries</h3>
				<ul id="birthdayList">{$output}</ul>
			</div>

EOD;
		}
	}

	/**
	 * Read birthday list from cache so it only gets generated once a day.
	 */
	function upcomingBirthdaysCached() {
		$filename = "{$this->famu->site_cache}/birthdays.htm";
		if (!is_dir($this->famu->site_cache))
			mkdir($this->famu->site_cache, 0755, true);
		if (!is_file($filename) || date('Ymd', filemtime($filename))!=date('Ymd')) {
			$fh = fopen($filename, 'w');
			fwrite($fh, $this->upcomingBirthdays());
			fclose($fh);
		}
		return file_get_contents($filename);
	}

	/**
	 * Generate the birthday list and format for output.
	 */
	function upcomingBirthdays() {
		$bdays = $this->famu->getUpcomingBirthdays();
		foreach ($bdays as $bday) {
			$name = "{$bday['first_name']} {$bday['last_name']}";
			$avtr = $this->famu->getFullAvatarLink($bday['avatar_id'], $bday['user_id'], $name, $name, 32);
			$age = ($bday['birth_year_unknown']) ? '' : $bday['age'];
			$dead = ($bday['deceased']) ? " <span class=\"detail\"> deceased</span>" : '';
			$date = date('M j', strtotime($bday['birth_date']));
			$output .= "<li>{$avtr}<div>{$age} on {$date}{$dead}</div></li>";
		}
		return <<<EOD
		<div class="panel">
			<h3>Upcoming Birthdays</h3>
			<ul id="birthdayList">{$output}</ul>
		</div>

EOD;
	}
}

interface SiteUser {
	function getUserName();
	function getUserID();
}

?>
