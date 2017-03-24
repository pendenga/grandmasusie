<?php

class GomezTemplate extends Template {
	protected $time_start;
	protected $template_path = "../tmpl/gomez/";
	protected $template_uri = "/tmpl/gomez/";
	public $title_icon;
	public $cssIncludes = array('/tmpl/gomez/style.css?v2', '/js/modalbox.css');
	
	function getMainLinks() {
		return <<<EOD
		<ul>
			<li><a href="/upload/" title="Upload photos to the main gallery." class="help">Upload</a></li>
			<li><a href="/gallery/" title="View the main site gallery." class="help">Gallery</a></li>
			<li><a href="/user/{$this->famu->active_id}/favorites/" title="View a gallery of photos you have marked as your favorites." class="help">Favorites</a></li>
			<li><a href="/write/" title="Write a blog entry." class="help">Write</a></li>
			<li><a href="/read/" title="A list of the latest blog entries from each family." class="help">Read</a></li>
		</ul>
EOD;
		//	<li><a href="/home/" class="help">Forum</a></li>
	}

	function getUserSignin() {
		if ($this->famu->signedIn) {
			// display user name
			$name = $this->famu->getUserName();
			$modr = ($this->famu->user['moderator']==1) ? '*' : '';

			// add unseen links and login switcher
			$checker = $this->getUnseenChecker();
			$switcher = $this->getLoginSwitcher("Active:");

			return <<<EOD
			<div class="header-links">{$switcher}</div>
			<div class="header-links">
				[<a href="http://www.grandmasusie.com/bb/index.php?c=3" target="_new" title="View the Familize Wiki for help on various topics." class="help">Help</a>, 
				 <a href="/signout/" title="Sign out if you are using a shared computer or need to log in as a different user." class="help">Sign Out</a>, 
				 <a href="/feedback_server/" title="Click here to open a feedback form, where you can leave comments or suggestions." class="help modalbox">Feedback</a>]
			</div>
			<div class="header-links">
				<span id="unseen" class="{$hide}">{$checker}</span> 
				<input id="check_unseen" type="button" class="smallbuttons {$hide}" value="check"/>
			</div>
EOD;

		// not signed in
		} else {
			return "You aren't signed in &nbsp; <a href=\"/signin\">Sign In</a> &nbsp; <a href=\"http://www.grandmasusie.com/bb/index.php?c=3\" target=\"_new\">Help</a> &nbsp; ";
		}
	}

	function pageHeader() {
		$this->time_start = microtime(true);
		parent::pageHeader();
		$userSignin = $this->getUserSignin();
		$mainLinks = $this->getMainLinks();
		$searchBox = $this->getMainSearchBox();
		print <<<EOD
<body>


<div id="progress" style="display: none"><img src="/tmpl/gomez/images/loading.gif"/></div>
<div id="containerwrap" class="clearfix">
<div id="container" class="clearfix">

<div id="header">
	<h1><a href="/home/" class="help" title="Home Page">GrandmaSusie.com</a></h1>
	<div id="header-nav">{$mainLinks}</div>
	<div id="header-login">  
        {$userSignin}
		<div class="header-links" id="headerSearch">{$searchBox}</div>
	</div>
</div>

EOD;
	}

	/**
	 * Read page footer from cache so it only gets generated twice a day.
	 */
	function pageFooter() {
		/*
		$filename = "{$this->famu->site_cache}/footer.htm";
		if (!is_dir($this->famu->site_cache))
			mkdir($this->famu->site_cache, 0755, true);
		if (!is_file($filename) || date('YmdA', filemtime($filename))!=date('YmdA')) {
			$fh = fopen($filename, 'w');
			fwrite($fh, $this->generatePageFooter());
			fclose($fh);
		}
		readfile($filename);
		*/
		print $this->generatePageFooter();

		$runTime = ($this->ajax) ? sprintf("Page: %0.5f sec. Module: <span id=\"timer2\">0</span> sec. Ajax: <span id=\"timer3\">0</span> sec.", microtime(true) - $this->time_start) : sprintf("Page: %0.5f sec.", microtime(true) - $this->time_start);
		$loadTime = date('g:ia');
		print <<<EOD
	<div id="copyright">Copyright &copy;2007 <a href="http://www.pendenga.com" class="externalLink">Pendenga Software</a>.  {$loadTime}.  {$runTime}</div>

</div></div>
</body>
</html>
EOD;
	}

	/**
	 * Generate formatted page footer.
	 */
	function generatePageFooter() {
		$oneDay = (60*60*24);
		$twoWeeks = (60*60*24*30);
		$tagCloud = $this->famu->tagCloud();
		//$interestingCloud = $this->famu->interestingCloud(date('Y-m-d', time()-$twoWeeks), date('Y-m-d', time()+$oneDay));
		//$quantityCloud = $this->famu->quantityCloud(date('Y-m-d', time()-$twoWeeks), date('Y-m-d', time()+$oneDay));
		$featuredCloud = $this->famu->featuredCloud(date('Y-m-d', time()-$twoWeeks), date('Y-m-d', time()+$oneDay));
		//$featuredAllCloud = $this->famu->featuredCloud('2000-1-1', date('Y-m-d', time()+$oneDay));
		$moreUserLinks = ($this->famu->user['moderator']) ? '<li><a href="/useredit/create/">Add New User</a></li>' : '';

		return <<<EOD
</div>
</div>
<!-- END containerwrap -->

<div id="footerwrap"><div id="footer">
	<div class="panel">
		<h4>Site Links</h4>
		<ul>
			<li><a href="/report/">Site Usage History</a></li>
			<li><a href="/comics/">Comics (Dilbert, etc.)</a></li>
			<li><a href="/secure/rss/" class="rssFeed">RSS Feed</a></li>
		</ul>

		<h4>Photo Galleries</h4>	
		<ul>
			<li><a href="/gallery/thumb/">All Photos</a></li>
			<li><a href="/gallery/favorite/">Favorite Photos</a></li>
			<li><a href="/gallery/group/">Group Photos</a></li>
			<li><a href="/gallery/topcomment/">Most Comments</a></li>
		</ul>
	</div>
	<div class="panel">
	</div>
	<div class="panel">
		<h4>User Links</h4>
		<ul>
			<li><a href="/useredit/password/">Change Password</a></li>
			<li><a href="/useredit/contact/">Edit Contact Info</a></li>
			<li><a href="/useredit/profile/">Edit User Profile</a></li>
			<li><a href="/useredit/preferences/">Preferences</a></li>
			{$moreUserLinks}
		</ul>

		<h4>Software</h4>
		<ul>
			<a href="http://www.spreadfirefox.com/?q=affiliates&amp;id=0&amp;t=218"><img border="0" alt="Firefox 2" class="help" title="Best viewed with Firefox 2. Download at www.spreadfirefox.com." src="http://sfx-images.mozilla.org/affiliates/Buttons/firefox2/ff2b80x15.gif"/></a><br/>
		</ul>
	</div>
	<div class="panel">
		<h4 title="Tags popular in more content in the last 30 days have larger names" class="help">Tag Cloud</h4>
		<div class="tagCloud">{$tagCloud}</div>
	</div>
	<div class="panel">
		<h4 title="Users featured in more photos in the last 30 days have larger names" class="help"><a href="/cloud/">Featured Cloud</a></h4>
		<div class="tagCloud">{$featuredCloud}</div>
	</div>
EOD;
/*
		<h4 title="Users posting more total content in the last 30 days have larger names." class="help">Activity Cloud</h4>
		<div class="tagCloud">{$quantityCloud}</div>
		<h4 title="Users posting content in the last 30 days that gets the most comments and favorites have larger names" class="help">Interest Cloud</h4>
		<div class="tagCloud">{$interestingCloud}</div>
*/
	}

	function getMainSearchBox() {
		return <<<EOD
	<form method="GET" action="/search/">
		<input type="text" name="searchString"/>
	</form>
EOD;
	}

	function basicPage($content) {
		if (trim($this->pageList)!='' || trim($this->pageListDesc)!='') {
			$pagelist = "<div class=\"head-detail\">Showing {$this->pageListDesc}</div>{$this->pageList}";
		}

		return <<<EOD
		<div class="photoFrame">
		{$pagelist}
		{$content}
		</div>
EOD;
	}

	function titledPage($title, $content) {
		if (trim($this->pageList)!='' || trim($this->pageListDesc)!='') {
			$pagelist = "<div class=\"head-detail\">Showing {$this->pageListDesc}</div>{$this->pageList}";
		}

		return <<<EOD
		<div class="photoFrame">
		<h3>{$title}</h3>
		{$pagelist}
		{$content}
		</div>
EOD;
	}

	function threeColumnLayout($colOne, $colTwo, $colThree, $class="home") {
		return <<<EOD

<div id="content" class="{$class}">
	<div id="leftwrap"><div id="left">
		{$colOne}
	</div></div>

	<div id="centerwrap"><div id="center">
		{$colTwo}
	</div></div>

	<div id="rightwrap"><div id="right">
		{$colThree}
	</div></div>
</div>

EOD;
	}

	function formWithInstructions($title, $form, $instructions, $width=800) {
		if (trim($this->pageList)!='' || trim($this->pageListDesc)!='') {
			$pagelist = "<div class=\"head-detail\">Showing {$this->pageListDesc}</div>{$this->pageList}";
		}

		return <<<EOD

<div id="content" class="comic">
	<div id="leftwrap"><div id="left">
	</div></div>

	<div id="centerwrap"><div id="center">
		<div class="photoFrame">
			<h3>{$title}</h3>
			{$pagelist}
		</div>
		<div class="photoFrame">
			{$form}
		</div>
	</div></div>

	<div id="rightwrap"><div id="right">
		<div class="panel">
		{$instructions}
		</div>
	</div></div>
</div>
EOD;
	}

	function authorForm($form, $instructions) {
		return <<<EOD
<div id="content" class="page">
	<div id="leftwrap"><div id="left">
		{$this->authorBlock}
	</div></div>
	<div id="centerwrap"><div id="center">
		{$form}	
	</div></div>
	<div id="rightwrap">
		{$instructions}<div id="right">
	</div></div>
</div>
EOD;
	}

	function getBoxAlert($title, $instructions) {
		return <<<EOD
		<div style="border: 2px solid #f90; background: #fc6; width: 600px; margin: 10px auto; padding: 5px"><h3>{$title}</h3>{$instructions}</div>
EOD;
	}

	function getBoxNotify($title, $instructions) {
		return <<<EOD
		<div style="border: 2px solid #69c; background: #9cf; width: 600px; margin: 10px auto; padding: 5px"><h3>{$title}</h3>{$instructions}</div>
EOD;
	}

	function getBoxError($title, $instructions) {
		return <<<EOD
		<div style="border: 2px solid #c33; background: #f99; width: 600px; margin: 10px auto; padding: 5px"><h3>{$title}</h3>{$instructions}</div>
EOD;
	}



}

?>
