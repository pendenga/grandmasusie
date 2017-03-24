<?php
$microtimer_start = microtime(true);
session_start();

include_once '../lib/dbobject.php';
include_once '../lib/template.php';
include_once '../lib/familize.php';
include_once '../lib/registrationForm.php';

$db = new DBObject();
$famu = new Familize($db);
$tmpl = new $famu->template($famu);
$reg = new RegistrationForm($db, $famu, $tmpl);

// load signin page
$tmpl->title = "Comics";
$tmpl->pageHeader();

$date = (strtotime($_GET['date'])) ? strtotime($_GET['date']) : time();
$today = date('m/d/Y', $date);
$tdate = date('Ymd', $date);
$udate = date('ymd', $date);

$day = 60*60*24;
for ($i=(time()-(30*$day)); $i<=time(); $i+=$day) {
	$linkDate = date('Ymd', $i);
	$readDate = date('l n/d', $i);
	$dateLinks .= ($linkDate == $tdate) ? "<li>$readDate</li>" : "<li><a href=\"?date={$linkDate}\">{$readDate}</a></li>";
}

$dilbert = (date('w', $date)==0) ? "dilbert/{$tdate}.jpg" : "dilbert/{$tdate}.gif";
$fminus = (date('w', $date)==0) ? "fminus/{$tdate}.jpg" : "fminus/{$tdate}.gif";
$form = <<<EOD
	<div class="comicFrame">
		<h3>Dilbert - {$today}</h3>
		<div class="detail">by Scott Adams</div>
		<img src="http://{$_SERVER['HTTP_HOST']}/static/comics/{$dilbert}"/>

		<h3>F-Minus - {$today}</h3>
		<div class="detail">by Tony Carillo</div>
		<img src="http://{$_SERVER['HTTP_HOST']}/static/comics/{$fminus}"/>

		<h3>Calvin & Hobbes - {$today}</h3>
		<div class="detail">by Bill Watterson</div>
		<img src="http://{$_SERVER['HTTP_HOST']}/static/comics/calvinhobbes/calvinhobbes{$udate}.gif"/>

		<h3>Wizard of Id - {$today}</h3>
		<div class="detail">by Parker & Hart</div>
		<img src="http://{$_SERVER['HTTP_HOST']}/static/comics/wizardofid/wizardofid{$udate}.gif"/>

		<h3>Frank & Ernest - {$today}</h3>
		<div class="detail">by Bob Thaves</div>
		<img src="http://{$_SERVER['HTTP_HOST']}/static/comics/frankernest/frankernest{$udate}.gif"/>

		<h3>FoxTrot - {$today}</h3>
		<div class="detail">by Bill Amend</div>
		<img src="http://{$_SERVER['HTTP_HOST']}/static/comics/foxtrot/foxtrot{$udate}.gif"/>

		<h3>B.C. - {$today}</h3>
		<div class="detail">by Johnny Hart</div>
		<img src="http://{$_SERVER['HTTP_HOST']}/static/comics/BC/BC{$udate}.gif"/>

		<h3>Boondocks - {$today}</h3>
		<div class="detail">by Aaron McGruder</div>
		<img src="http://{$_SERVER['HTTP_HOST']}/static/comics/boondocks/boondocks{$udate}.gif"/>
	</div>
EOD;

print $tmpl->threeColumnLayout('', $form, "<ul>{$dateLinks}</ul>", (date('l', $date) == 'Sunday') ? 'sunday' : 'comic');
$tmpl->pageFooter();

?>
