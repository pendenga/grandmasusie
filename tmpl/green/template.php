<?php

class GreenTemplate extends Template {
	protected $time_start;
	protected $template_path = "../tmpl/green/";
	protected $template_uri = "/tmpl/green/";
	public $cssIncludes = array('/tmpl/green/input.css', '/tmpl/green/green.css', '/js/modalbox.css');

	function pageHeader() {
		$this->time_start = microtime(true);
		parent::pageHeader();
		$userSignin = $this->getUserSignin();
		$mainLinks = $this->getMainLinks();
		$searchBox = $this->getMainSearchBox();
		$ajaxProgress = '<div id="progress" class="hiddenDiv"><img src="/tmpl/green/images/progress.gif"/></div>';
		print <<<EOD
<body>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td class="superheader">{$mainLinks}{$userSignin}{$ajaxProgress} </td>
  </tr>
  <tr>
    <td class="header">
		<div id="searchBoxDiv">{$searchBox}</div>
		<img src="/tmpl/green/images/header_logo.png" width="375" height="95" border="0" />
	</td>
  </tr>
  <tr>
    <td>
EOD;
	}


	function pageFooter() {
		$runTime = $ajaxProgress = ($this->ajax) ? sprintf("Page: %0.5f sec. Module: <span id=\"timer2\">0</span> sec. Ajax: <span id=\"timer3\">0</span> sec.", microtime(true) - $this->time_start) : sprintf("Page: %0.5f sec.", microtime(true) - $this->time_start);
		$loadTime = date('g:ia');
		print <<<EOD
</td>
  </tr>
  <tr>
    <td class="body"></td>
  </tr>
  <tr>
    <td class="footer" valign="top">
			Copyright &copy;2007 Pendenga Software.<br/>{$loadTime}.  {$runTime}<br/>Best used with <a href="http://www.spreadfirefox.com/?q=affiliates&amp;id=0&amp;t=218"><img border="0" alt="Firefox 2" title="Firefox 2" src="http://sfx-images.mozilla.org/affiliates/Buttons/firefox2/ff2b80x15.gif"/></a><br/>
			<a href="/feedback_server/" title="Feedback Form" class="modalbox">Feedback Form</a>
	</td>
	</tr>
</table>
</body>
EOD;
		parent::pageFooter();
	}

	function getMainSearchBox() {
		return <<<EOD
	<form method="GET" action="/search/">
		<input type="text" name="searchString" maxlength="55"/>
		<input type="submit" value="Search" class="buttons"/>
	</form>
EOD;
	}

	function basicPage($content, $width=800) {
		return <<<EOD
		<div style="width:{$width}px; margin: 0px auto">
		{$content}
		</div>
EOD;
	}

	function titledPage($title, $content, $width=800) {
		return <<<EOD
		<div style="width:{$width}px; margin: 0px auto">
		<h3>{$title}</h3>
		{$content}
		</div>
EOD;
	}

	function formWithInstructions($title, $form, $instructions, $width=800) {
		return <<<EOD
		<table width="{$width}" border="0" style="margin: 0px auto">
		<tr><td colspan="2">
			<h3>{$title}</h3></td></tr>
		<tr><td valign="top">{$form}</td>
			<td valign="top" class="instructions">{$instructions}</td></tr>
		</table>
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
