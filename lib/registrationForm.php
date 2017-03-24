<?php

include_once 'registration.php';

class RegistrationForm extends Registration {

	function confirmForm($instructions='') {
		if (trim($instructions)=='') {
			$instructions = "Please confirm your email address by clicking on the link we sent you.  Alternatively, you can enter here the signup code we sent to your email.";
		}
		$form = <<<EOD
		<form id="confirm_form">
		<table width="100%" border="0">
		<tr><td align="right">Code</td>
			<td><input type="text" name="signup_code" value="{$_REQUEST['signup_code']}"/></td></tr>
		<tr><td>&nbsp;</td>
			<td><input type="button" class="buttons" value="Verify Signup" onclick="submitForm()"/></td></tr>
		</table>
		</form>
EOD;
		return $this->tmpl->formWithInstructions("Confirm Registration", $form, $instructions);
	}

	function signinForm($directTo, $instructions='') {
		if (trim($instructions)=='') {
			$instructions = "Please provide your username and password to sign in.  You can use the same login and password you used to use for the previous GrandmaSusie.com.";
			// If you don't have a username and password, <a href=\"/signup/\">signup for a new username</a>.
		}
		$form = <<<EOD

		<form method="POST" action="/signin/">
			<input type="hidden" name="directTo" value="{$directTo}"/>
		<table width="100%" border="0">
		<tr><td align="right">Username</td>
			<td><input type="text" name="username" value="{$_REQUEST['username']}"/></td></tr>
		<tr><td align="right">Password</td>
			<td><input type="password" name="password" value="{$_REQUEST['password']}"/></td></tr>
		<tr><td colspan="2" align="center"><input type="checkbox" name="persistent" value="on" checked="checked"/> Keep me signed in<div class="detail">Uncheck if on a shared computer</div></td></tr>
		<tr><td>&nbsp;</td>
			<td><input type="submit" class="buttons" value="Sign In"/></td></tr>
		</table>
		</form>
EOD;
		return $this->tmpl->formWithInstructions("Please Sign In", $form, $instructions);
	}

	function confirmNewUser($signup_code) {
		$this->tmpl->title = 'Confirm Registration';
		$this->tmpl->pageHeader();
		parent::confirmNewUser($signup_code);
		$this->tmpl->pageFooter($this->time_start);
	}

	function passwordEmail($username, $password) {
		$subject = "{$this->site['site_name']} password reminder";
		$body = <<<EOD
\nDear {$username},

This automatically generated email was sent because you requested the password for your account at {$this->site['site_name']}. Please keep this information for your records!

Per your request, your account information is:
	Username - {$username}
	Password - {$password}

Thank you for using Familize.com.

Familize Support
http://www.familize.com
EOD;
		return array($subject, $body);
	}

	// generate confirmation email
	function signupConfirmationEmail($username, $password, $email, $code) {
		$subject = "{$this->site['site_name']} registration confirmation";
		$body = <<<EOD
\nDear {$username},

This automatically generated email confirms the registration information for your account at {$this->site['site_name']}. Please keep this information for your records!

Please visit the following URL to verify this email address:
	http://{$this->site['http_host']}/confirm/?signup_code={$code}

Here is the info you gave us when you signed up:
	Username - {$username}
	Email Address - {$email}
	Password - {$password}

Thank you for using Familize.com.

Familize Support
http://www.familize.com
EOD;
		return array($subject, $body);
	}

	function signupForm() {
		$smsOptions = $this->tmpl->getSmsGatewayOptions($this->famu->user['sms_carrier']);
		return <<<EOD
	<div id="page">
		<form id="signup_form">
		<table width="100%" border="0" class="reg_form">
		<tr><td colspan="3"><h4>Tell us about yourself...</h4></td></tr>
		<tr><td class="label"><img src="{$this->tmpl->icon['ok.png']}" id="name_ok" style="display: none"/>
				<img src="{$this->tmpl->icon['warning.png']}" id="name_warning" style="display: none"/>
				<label id="name_label" for="name">Full Name</label></td>
			<td class="input" id="name">
				<input type="text" name="name" id="name" value="" class="required"/></td>
			<td class="hints" rowspan="3"><ul>
				<li id="name_hint" style="display: none">Please enter your full name.  We will check that your name was not already entered by your family.</li>
				<li id="gender_hint" style="display: none">Gender is an essential characteristic of individual premortal, mortal, and eternal identity and purpose.</li>
			</ul></td>
		</tr>
		<tr><td class="label"><img src="{$this->tmpl->icon['ok.png']}" id="gender_ok" style="display: none"/>
				<img src="{$this->tmpl->icon['warning.png']}" id="gender_warning" style="display: none"/>
				<label id="gender_label" for="gender">Gender</label></td>
			<td class="input">
				<select name="gender" id="gender" class="required"><option value="-1">- Select One -</option><option value="M">Male</option><option value="F">Female</option></select></td></tr>
		<tr><td class="label">Preference</td>
			<td class="input">
				<input type="radio" name="pref" id="pref" value="0" onfocus="switchPreference(this)"/> Email 
				<input type="radio" name="pref" id="pref" value="1" onfocus="switchPreference(this)"/> SMS</td></tr>
		<tr><td class="label"></td>
			<td class="input">
				<div id="pref_sms" class="commentForm" style="display: none">
					<form id="sms_form">
					<table>
					<tr><td align="right">Mobile</td>
						<td><input type="text" class="forms" name="contact[sms_number]" value="" maxlength="10"/></td></tr>
					<tr><td align="right">Carrier</td>
						<td><select class="forms" name="contact[sms_carrier]">{$smsOptions}</select></td></tr>
					<tr><td align="right">Cost</td>
						<td><input type="checkbox" name="contact[sms_cost]" value="yes" {$checked['cost']}/><span class="detail">Free Messaging</span></td></tr>
					<tr><td></td>
						<td><input type="button" class="smallbuttons" value="check" onclick="new Ajax.Update('pref_sms','/signup_server/', {method: 'post', parameters: $('sms_form').serialize(true)});"/></td></tr>
					</table>
					</form>
				</div>
				<div id="pref_email" class="commentForm" style="display: none">
					<form id="email_form">
					<table>
					<tr><td align="right">Email</td>
						<td><input type="text" class="forms" name="contact[email_address]" value="{$this->famu->user['email_address']}" maxlength="80"/></td></tr>
					<tr><td></td>
						<td><input type="button" class="smallbuttons" value="check" onclick="new Ajax.Update('pref_email','/signup_server/', {method: 'post', parameters: $('email_form').serialize(true)});"/></td></tr>
					</table>
					</form>
				</div>
			</td></tr>			
		<tr><td colspan="3"><h4>Select a username and password</h4></td></tr>
		<tr><td class="label">Username</td>
			<td class="input"><input type="text" name="username" value="{$_REQUEST['username']}"/></td>
			<td class="hints" rowspan="4"></td></tr>
		<tr><td class="label">Password</td>
			<td class="input"><input type="password" name="password" value="{$_REQUEST['password']}"/></td></tr>
		<tr><td class="label">Confirm</td>
			<td class="input"><input type="password" name="password2" value="{$_REQUEST['password2']}"/></td></tr>
		<tr><td class="label">&nbsp;</td>
			<td class="input"><input type="button" class="buttons" value="Sign up" onclick="submitForm()"/></td></tr>
		</table>
		</form>
	</div>
	<script>
	function switchPreference(radio) {
		if (radio.value==1) {
			Element.hide($('pref_email'));
			new Effect.Appear($('pref_sms'), {queue: 'end'});
		} else {
			Element.hide($('pref_sms')); 
			new Effect.Appear($('pref_email'), {queue: 'end'});
		}
	}
	function submitForm() {
		// initialize the page module
		var start = new Date();
		time_start = start.getTime(); 
		new Ajax.Request('/signup_server/', {
			method: 'post',
			parameters: $('signup_form').serialize(true),
			onSuccess: function(transport, json) {
				$('page').innerHTML = transport.responseText;
				$('timer2').innerHTML = json.timer2;
				var ender = new Date();
				$('timer3').innerHTML = ((ender.getTime()-time_start)/1000); 
			}
		});
	}
	</script>

EOD;
	}

	/** STATUS MESSAGES **/
	/** STATUS MESSAGES **/
	/** STATUS MESSAGES **/

	function signinFailed($instructions) {
		return $this->tmpl->getBoxError("Sign In Failed", $instructions);
	}

	function inactiveUser($instructions) {
		return $this->tmpl->getBoxError("Inactive User", $instructions);
	}

	function signupFailed($instructions) {
		return $this->tmpl->getBoxError("Registration failed", $instructions);
	}

	function signupNotes($instructions) {
		return $this->tmpl->getBoxNotify("Please note", $instructions);
	}

	function signupSuccess($instructions) {
		return $this->tmpl->getBoxAlert("Success", $instructions);
	}
}

?>