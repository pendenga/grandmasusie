<?php
$microtimer_start = microtime(true);
session_start();

include_once '../lib/dbobject.php';
include_once '../lib/template.php';
include_once '../lib/familize.php';

$db = new DBObject();
$famu = new Familize($db);
$famu->loginCheck();
$tmpl = new $famu->template($famu);

// load home page
$tmpl->title = "Upload Photo - GrandmaSusie.com";
$tmpl->ajax = true;
$tmpl->pageHeader();

$new_photo_id = uniqid();

/*
<div id="mainbody" style="width: 800px; margin: 0px auto">
	<h3>Upload Photos</h3>

	<div id="formPane">
		<form enctype="multipart/form-data" action="/cgi-bin/upload.cgi?sid={$new_photo_id}" method="post" target="iframe_{$new_photo_id}" id="form_{$new_photo_id}"/>
			<input class="input" type="file" class="buttons" name="file_1" onchange="submitForm('{$new_photo_id}');" />
		</form>
	</div>
	<iframe name="iframe_{$new_photo_id}" style="border: 0; width: 0px; height: 0px;"></iframe>
	<div id="photoPane"></div>

</div>
<script>
*/


$form = <<<EOD
	<div id="formPane">
		<form enctype="multipart/form-data" action="/cgi-bin/upload.cgi?sid={$new_photo_id}" method="post" target="iframe_{$new_photo_id}" id="form_{$new_photo_id}"/>
			<input class="input" type="file" class="buttons" name="file_1" onchange="submitForm('{$new_photo_id}');" />
		</form>
	</div>
	<iframe name="iframe_{$new_photo_id}" style="border: 0; width: 0px; height: 0px;"></iframe>
	<div id="photoPane"></div>

<script>

/**
 * Initialize Page
 */
var photo_index=1;
function submitForm(pid) {
	// initialize the page module
	var start = new Date();
	time_start = start.getTime(); 
	new Ajax.Request('/upload_server/upload/'+pid, {
		method: 'post',
		//parameters: $('upload_form').serialize(true),
		onSuccess: function(transport, json) {
			// create new progress area from ajax response
			var photoPane = $('photoPane');
			photoPane.innerHTML+=transport.responseText;
			var formPane = $('formPane');
			formPane.className = 'hiddenDiv';

			// move input box into form in progress area
			var photoForm = $('form_'+pid);
			photoForm.submit();

			// start checking upload progress and updating monitor
			var pu = new Ajax.PeriodicalUpdater('progress_pct_'+pid, '/upload_progress/'+pid, { 
				frequency:1, 
				decay:2, 
				onSuccess: function(transport, json) { 
					var pct = transport.responseText;
					if (pct<100) {
						$('progress_bar_'+pid).innerHTML = '<div class="progressbar" style="width: '+pct+'%"></div>';
					} else {
						pu.stop();
					}
				},
				onComplete: function(transport, json) {
					time_start = start.getTime();
					$('progress_'+pid).innerHTML = '<img src="/tmpl/green/images/progress.gif"/>';
					new Ajax.Updater('progress_'+pid, '/upload_server/receive/'+pid, { 
						onSuccess: function(transport, json) {
							var ender = new Date();
							$('timer2').innerHTML = json.timer2;
							$('timer3').innerHTML = ((ender.getTime()-time_start)/1000); 
							$('message_'+pid).innerHTML = json.message;
						}
					});
				}
			});

			var ender = new Date();
			$('timer2').innerHTML = json.timer2;
			$('timer3').innerHTML = ((ender.getTime()-time_start)/1000); 
		}
	});
}

</script>
EOD;

print $tmpl->formWithInstructions("Upload Photos", $form, "<p>Browse your computer for a file to upload.  The upload will start automatically as soon as you select the file.</p><p class=\"detail\">There is a ton of space for photos so upload as many as you want and as large as you want.<br/>- Grant");
$tmpl->pageFooter();

?>