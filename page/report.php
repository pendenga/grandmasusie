<?php
$microtimer_start = microtime(true);
session_start();

include_once '../lib/dbobject.php';
include_once '../lib/template.php';
include_once '../lib/familize.php';
include_once '../lib/statForm.php';

$db = new DBObject();
$famu = new Familize($db);
$famu->loginCheck();
$tmpl = new $famu->template($famu);
$stat = new StatForm($db, $famu, $tmpl);

// get url variables
list($type, $parm1, $parm2) = $_REQUEST['url'];

// load admin page
$tmpl->title = "Admin";
$tmpl->pageHeader();


switch ($type) {
case 'uploads':
	$form = <<<EOD
	<OBJECT classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
	 codebase="https://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0"
	 WIDTH="800" HEIGHT="400" id="pieF" ALIGN=""><PARAM NAME=movie VALUE="/js/pieF.swf?filename=/report_server/photouser/{$parm1}/{$parm2}/"><PARAM NAME=bgcolor VALUE=#FFFFFF><PARAM NAME=wmode VALUE=transparent><PARAM NAME=quality VALUE=high><EMBED src="/js/pieF.swf?filename=/report_server/photouser/{$parm1}/{$parm2}/" wmode="transparent" quality=high bgcolor=#FFFFFF  WIDTH="800" HEIGHT="400" NAME="pieF" ALIGN=""
	 TYPE="application/x-shockwave-flash" PLUGINSPAGE="https://www.macromedia.com/go/getflashplayer"></EMBED></OBJECT>
EOD;
 	print $tmpl->titledPage("Uploads By User", $form);
	break;
case 'stats':
default:
	$userOps = $stat->posterOptions($parm1);
	$form = <<<EOD
	<div>Report For: <select onchange="window.location.href='/report/stats/'+this[this.selectedIndex].value;"><option value="">-- Entire Site --</option>{$userOps}</select></div>
	 <br/>


	<OBJECT classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
	 codebase="https://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0"
	 WIDTH="800" HEIGHT="400" id="vBarF" ALIGN=""><PARAM NAME=movie VALUE="/js/vBarF.swf?filename=/report_server/photoyear/{$parm1}"><PARAM NAME=bgcolor VALUE=#FFFFFF><PARAM NAME=wmode VALUE=transparent><PARAM NAME=quality VALUE=high><EMBED src="/js/vBarF.swf?filename=/report_server/photoyear/{$parm1}" wmode="transparent" quality=high bgcolor=#FFFFFF  WIDTH="800" HEIGHT="400" NAME="vBarF" ALIGN=""
	 TYPE="application/x-shockwave-flash" PLUGINSPAGE="https://www.macromedia.com/go/getflashplayer">
	 </EMBED>
	</OBJECT>

	 <br/>
	 <br/>

	<OBJECT classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
	 codebase="https://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0"
	 WIDTH="800" HEIGHT="400" id="vBarF" ALIGN=""><PARAM NAME=movie VALUE="/js/vBarF.swf?filename=/report_server/commentyear/{$parm1}"><PARAM NAME=bgcolor VALUE=#FFFFFF><PARAM NAME=wmode VALUE=transparent><PARAM NAME=quality VALUE=high><EMBED src="/js/vBarF.swf?filename=/report_server/commentyear/{$parm1}" wmode="transparent" quality=high bgcolor=#FFFFFF  WIDTH="800" HEIGHT="400" NAME="vBarF" ALIGN=""
	 TYPE="application/x-shockwave-flash" PLUGINSPAGE="https://www.macromedia.com/go/getflashplayer">
	 </EMBED>
	</OBJECT>

	 <br/>
	 <br/>

	<OBJECT classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
	 codebase="https://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0"
	 WIDTH="800" HEIGHT="400" id="vBarF" ALIGN=""><PARAM NAME=movie VALUE="/js/vBarF.swf?filename=/report_server/commentsperphoto/{$parm1}"><PARAM NAME=bgcolor VALUE=#FFFFFF><PARAM NAME=wmode VALUE=transparent><PARAM NAME=quality VALUE=high><EMBED src="/js/vBarF.swf?filename=/report_server/commentsperphoto/{$parm1}" wmode="transparent" quality=high bgcolor=#FFFFFF  WIDTH="800" HEIGHT="400" NAME="vBarF" ALIGN=""
	 TYPE="application/x-shockwave-flash" PLUGINSPAGE="https://www.macromedia.com/go/getflashplayer">
	 </EMBED>
	</OBJECT>

EOD;
	print $tmpl->titledPage("Site Statistics", $form);

}

$tmpl->pageFooter();

/*
print "<pre>";
print_r($famu);
print "</pre>";
*/
?>