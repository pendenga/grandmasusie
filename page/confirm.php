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

$tmpl->ajax = true;
$tmpl->title = "Confirm Registration";
$tmpl->pageHeader();

print <<<EOD

<div id="page">
	<form id="confirm_form"><input type="hidden" name="signup_code" value="{$_REQUEST['signup_code']}"/></form>
	<div style="width:400px; margin: 0px auto"><h3>Loading...</h3></div>
</page>
<script>

/**
 * Initialize Page
 */
function submitForm() {
	// initialize the page module
	var start = new Date();
	time_start = start.getTime(); 
	new Ajax.Request('/confirm_server/', {
		method: 'post',
		parameters: $('confirm_form').serialize(true),
		onSuccess: function(transport, json) {
			$('page').innerHTML = transport.responseText;
			$('timer2').innerHTML = json.timer2;
			var ender = new Date();
			$('timer3').innerHTML = ((ender.getTime()-time_start)/1000); 
		}
	});
}

Behaviour.addLoadEvent(submitForm);
</script>
EOD;

$tmpl->pageFooter();
?>