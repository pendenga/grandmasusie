<?php
include_once 'photo.php';
include_once 'profile.php';
include_once 'blogForm.php';

class ProfileForm extends Profile {

	function avatarForm($photo_id, $user_id=false) {
		$photo = $this->db->do_sql("SELECT * FROM photo WHERE photo_id={$photo_id}");
		$phto = new Photo($this->db, $this->famu, $photo[0]);
		if ($user_id && $this->famu->moderator && $user_id!=$this->famu->active_id) {
			$user = $this->db->do_sql("SELECT * FROM users WHERE user_id={$user_id}");
			$olda = $this->famu->getFullAvatar($user[0]['avatar_id']);
		} else {
			$olda = $this->famu->getFullAvatar();
			$user_id = $this->famu->active_id;
		}
		$path = $phto->getUrl_medium();
		$info = getimagesize($phto->getFile_medium());

		// give moderator the option to change other people's avatars
		$user_select_form = '';
		if ($this->famu->moderator) {
			$u_op = $this->famu->allUserOptions($user_id);
			$user_select_form = "<form method=\"POST\">Change user to: <select name=\"user_id\" onchange=\"this.form.submit()\"/>{$u_op}</select></form>";
		}

		$previous_avatar = '';
		if ($user_id==$this->famu->active_id && $this->famu->user['avatar_prev']!='') {
			$pvav = $this->famu->getFullAvatar($this->famu->user['avatar_prev']);
			$previous_avatar = "<br/><strong>Previous:</strong><div class=\"oldAvatar\"><a href=\"/useredit/avatar/{$photo_id}/restore\"><img src=\"{$pvav}\" border=\"0\" title=\"Click here to revert to this avatar\"/></a><span class=\"detail\">Click to Revert</span></div>";
		}		

		switch (date('m/d/y')) {
		case '02/05/08':
		case '11/04/08':
			$special_avatar_form = "<div style=\"border: 1px solid #000; background-color: #ccf; float: right; width: 250px; margin: 2px; padding: 2px\"><div><a href=\"/useredit/avatar/{$photo_id}/ivotedtoday\"><img src=\"/static/avatar/ivotedtoday.jpg\" border=\"0\" align=\"left\" style=\"margin: 3px\"/></a> <a href=\"/useredit/avatar/{$photo_id}/ivotedearly\"><img src=\"/static/avatar/ivotedearly.jpg\" border=\"0\" align=\"left\" style=\"margin: 3px\"/></a> Change your avatar for Election Day!  Click here if you voted!</div></div>";
			break;
		default:
			$special_avatar_form = '';
		}

		$form = <<<EOD
		<script src="/js/cropper.js" type="text/javascript"></script>
		<script type="text/javascript" charset="utf-8">
			function onEndCrop( coords, dimensions ) {
				$( 'x1' ).value = coords.x1;
				$( 'y1' ).value = coords.y1;
				$( 'x2' ).value = coords.x2;
				$( 'y2' ).value = coords.y2;
				$( 'width' ).value = dimensions.width;
				$( 'height' ).value = dimensions.height;
			}
			/*
			function createAvatar() { 
				alert($('avatarSize'));
				var parms = Form.serialize('avatarSize');
				alert(parms);
			}
			*/
			// example with a preview of crop results, must have minimumm dimensions
			Event.observe( window, 'load', function() { 
					new Cropper.ImgWithPreview( 
						'testImage',
						{ 
							minWidth: 48, 
							minHeight: 48,
							ratioDim: { x: 48, y: 48 },
							displayOnInit: true, 
							onEndCrop: onEndCrop,
							previewWrap: 'previewArea'
						} 
					) 
				} 
			);		
		</script>
		<style type="text/css">
			label { 
				clear: left;
				float: left;
				width: 5em;
			}
			
			#testWrap {
				width: 500px;
				float: left;
				margin: 0px;
			}
			#previewArea {
				width: 48px;
				margin: 10px auto;
			}
			.oldAvatar {
				width: 48px;
				margin: 10px auto;
			}
			#results {
				clear: both;
			}
		</style>

		{$special_avatar_form}
		{$user_select_form}
		<br style="clear: both"/>

		<table border="0">
		<tr><td id="testWrap"><img src="{$path}" alt="test image" id="testImage" {$info[3]} /></td>
			<td valign="top"><strong>Existing:</strong><br/>
				<div class="oldAvatar"><img src="{$olda}"/></div>
				<strong>Preview:</strong><br/>
				<div id="previewArea"></div>
				<form id="avatarSize" method="POST">
				<input type="hidden" name="avatar[x1]" id="x1" />
				<input type="hidden" name="avatar[y1]" id="y1" />
				<input type="hidden" name="avatar[x2]" id="x2" />
				<input type="hidden" name="avatar[y2]" id="y2" />
				<input type="hidden" name="avatar[width]" id="width" />
				<input type="hidden" name="avatar[height]" id="height" />
				<input type="hidden" name="avatar[user_id]" value="{$user_id}"/>
				<input type="hidden" name="avatar[photo_id]" value="{$photo_id}"/>
				<input type="submit" value="Change" class="buttons" onclick="createAvatar()"/>
				</form>
				{$previous_avatar}
			</td></tr>
		</table>
EOD;
		$instructions = "Set your avatar by dragging the light square around in the photo frame.  Change the size of the square by dragging the corners.  Preview your avatar and click the 'Change' button when you're done.";
		return $this->tmpl->formWithInstructions("Change Avatar", $form, $instructions);
	}

	function confirmForm() {
		$instructions = "<p>Confirm your email address or mobile number by entering the code we sent you.</p>";
		$form = <<<EOD
		<table border="0">
		<form method="POST">
			<tr><td align="right">Code</td>
				<td><input type="text" class="forms" name="confirm[code]" maxlength="4"/></td></tr>
			<tr><td>&nbsp;</td>
				<td><input name="submit" class="buttons" type="submit" value="Confirm"/></td></tr>
		</form>
		</table>
EOD;
		return $this->tmpl->formWithInstructions("Confirmation Code", $form, $instructions);	
	}

	function contactInfoForm() {
		$smsOptions = $this->tmpl->getSmsGatewayOptions($this->famu->user['sms_carrier']);
		$emailConfirmed = ($this->famu->user['email_confirmed']==1) ? 'Your email address is confirmed' : "Email Not Confirmed";
		$smsConfirmed = ($this->famu->user['sms_confirmed']==1) ? 'Your mobile number is confirmed' : "SMS Not Confirmed";;
		$checked['pref_sms'] = ($this->famu->user['pref_sms']==1) ? 'checked="checked"' : '';
		$checked['pref_email'] = ($this->famu->user['pref_sms']==0) ? 'checked="checked"' : '';
		$checked['cost'] = ($this->famu->user['sms_cost']==1) ? '' : 'checked="checked"';

		return <<<EOD
		<div class="photoFrame">
			<h3>Contact Information</h3>
			<table border="0">
			<form method="POST">
				<tr><td align="right">Email</td>
					<td><input type="text" class="forms" name="contact[email_address]" value="{$this->famu->user['email_address']}" maxlength="80"/></td></tr>
				<tr><td colspan="2" align="center"><span class="detail">{$emailConfirmed}</span></td></tr>
				<tr><td align="right">Mobile</td>
					<td><input type="text" class="forms" name="contact[sms_number]" value="{$this->famu->user['sms_number']}" maxlength="10"/></td></tr>
				<tr><td align="right">Carrier</td>
					<td><select class="forms" name="contact[sms_carrier]">{$smsOptions}</select></td></tr>
				<tr><td align="right">Cost</td>
					<td><input type="checkbox" name="contact[sms_cost]" value="yes" {$checked['cost']}/><span class="detail">Free Messaging</span></td></tr>
				<tr><td colspan="2" align="center"><span class="detail">{$smsConfirmed}</span></td></tr>
				<tr><td align="right">Preference</td>
					<td><input type="radio" class="forms" name="contact[pref_sms]" value="0" {$checked['pref_email']}/> Email <input type="radio" class="forms" name="contact[pref_sms]" value="1" {$checked['pref_sms']}/> SMS</td></tr>
				<tr><td>&nbsp;</td>
					<td><input name="submit" class="buttons" type="submit" value="Save"/></td></tr>
			</form>
			</table>
		</div>
EOD;
	}

	function getHeader() {
		$name = $this->famu->getUserName();
		$avtr = $this->famu->getFullAvatarLink($this->famu->user['avatar_id'], $this->famu->user['user_id'], $name, "<h4>{$name}</h4>");


		$moreFunc = ($this->famu->user['moderator']) ? '<li><a href="/useredit/create/">Add New User</a></li>' : '';
		return <<<EOD
		<div class="photoAuthor clearfix">
			{$avtr}
			<ul class="userLinks">
				<li><a href="/useredit/password/">Change Password</a></li>
				<li><a href="/useredit/contact/">Edit Contact Info</a></li>
				<li><a href="/useredit/profile/">Edit User Profile</a></li>
				<li><a href="/useredit/preferences/">Preferences</a></li>
				{$moreFunc}
			</ul>
		</div>

EOD;
	}

	function householdForm() {
		$instructions = "<p>Identify users that are members of your household.</p>";

		$houses = $this->getHouseholds();
		$selected[$this->famu->user['household_id']] = 'selected="selected"';
		foreach ($houses as $ho) {
			if ($ho['hoh_1_first']!='' && $ho['hoh_2_first']!='') {
				if ($ho['hoh_1_last']==$ho['hoh_2_last']) {
					$h_name = "{$ho['hoh_1_first']} & {$ho['hoh_2_first']} {$ho['hoh_2_last']}";
				} else {
					$h_name = "{$ho['hoh_1_first']} {$ho['hoh_1_last']} & {$ho['hoh_2_first']} {$ho['hoh_2_last']}";
				}
			} else {
				$h_name = trim("{$ho['hoh_1_first']} {$ho['hoh_1_last']} {$ho['hoh_2_first']} {$ho['hoh_2_last']}");
			}
			$h_options .= "<option value=\"{$ho['house_id']}\" {$selected[$ho['house_id']]}>{$h_name}</option>";
		}
		
		list($house, $h_mem) = $this->getHousehold();
		$hoh_1 = $this->famu->allUserOptions($house['hoh_1']);
		$hoh_2 = $this->famu->allUserOptions($house['hoh_2']);

		/*
		foreach ($h_mem as $membr) {
			$members = $this->famu->allUserOptions($membr['user_id']);
		}
		*/
		$users = $this->famu->allUserOptions();
		$form = <<<EOD
		<table border="0">
		<form method="POST">
			<tr><td align="right">Household</td>
				<td><select name="join[house]">{$h_options}</select></td></tr>	
			<tr><td>&nbsp;</td>
				<td><input name="submit" class="buttons" type="submit" value="Join Household"/></td></tr>
		</form>
		<form method="POST">
			<tr><td colspan="2"><hr/></td></tr>
			<tr><td align="right">Address</td>
				<td><input type="text" class="forms" name="house[address]" value="{$house['address']}" maxlength="50"></td></tr>
			<tr><td align="right"></td>
				<td><input type="text" class="forms" name="house[address_2]" value="{$house['address_2']}" maxlength="50"></td></tr>
			<tr><td align="right">City</td>
				<td><input type="text" class="forms" name="house[city]" value="{$house['city']}" maxlength="50"></td></tr>
			<tr><td align="right">State</td>
				<td><input type="text" class="forms" name="house[state]" value="{$house['state']}" maxlength="30"></td></tr>
			<tr><td align="right">Zip</td>
				<td><input type="text" class="forms" name="house[postal_code]" value="{$house['postal_code']}" maxlength="30"></td></tr>
			<tr><td align="right">Country</td>
				<td><input type="text" class="forms" name="house[country]" value="{$house['country']}" maxlength="30"></td></tr>


			<tr><td colspan="2"><hr/></td></tr>
			<tr><td align="right">Head</td>
				<td><select name="house[hoh_1]">{$hoh_1}</select></td></tr>
			<tr><td align="right">Head</td>
				<td><select name="house[hoh_2]">{$hoh_2}</select></td></tr>
			<tr><td align="right">Add</td>
				<td><select name="house[member][]">{$users}</select></td></tr>

			<tr><td>&nbsp;</td>
				<td><input name="submit" class="buttons" type="submit" value="Save Household"/></td></tr>
		</form>
		</table>
EOD;
		return $this->tmpl->formWithInstructions("Update Household", $form, $instructions);	
	}

	function passwordChangeForm($resetCode=false) {
		if ($resetCode!==false) {
			$disabled = 'disabled="disabled"';
			$currInstruction = "<div style=\"border: 2px solid #f90; background: #fc6; margin: 10px auto; padding: 5px\">Using reset code: {$resetCode}</div>";
		} else {
			$currInstruction = '<span class="detail">Your current password first</span>';
		}

		return <<<EOD
		<div class="photoFrame">
			<h3>Change Password</h3>
			<table border="0">
			<form method="POST">
				<tr><td align="right">User</td>
					<td><strong>{$_SESSION['signin_name']}</strong></td></tr>
				<tr><td align="right">Current</td>
					<td><input type="password" class="forms" name="password[password_curr]" maxlength="32" {$disabled}/></td></tr>
				<tr><td colspan="2" align="center">{$currInstruction}</td></tr>
				<tr><td align="right">Password</td>
					<td><input type="password" class="forms" name="password[password_new]" maxlength="32"/></td></tr>
				<tr><td align="right">Confirm</td>
					<td><input type="password" class="forms" name="password[password_conf]" maxlength="32"/></td></tr>
				<tr><td colspan="2" align="center"><span class="detail">Confirm your new password</span></td></tr>

			<tr><td>&nbsp;</td>
				<td><input name="submit" class="buttons" type="submit" value="Save"/></td></tr>
			</form>
			</table>
		</div>
EOD;
	}

	function preferencesForm(BlogForm &$blog) {
		$form = $blog->blogCreateForm();
		return <<<EOD
		<div class="photoFrame">
			<h3>Your Preferences</h3>
			{$form}
		</div>
EOD;
	}

	function userProfileForm() {
		$birthday = ($this->famu->user['birth_date']!='') ? ($this->famu->user['birth_year_unknown']) ? date('M j', strtotime($this->famu->user['birth_date'])) : date('M j, Y', strtotime($this->famu->user['birth_date'])) : '';
		$gender = ($this->famu->user['gender']=='M') ? 'M' : 'F';
		$checked["gender_{$gender}"] = 'checked="checked"';
		$checked['yearunknown'] = ($this->famu->user['birth_year_unknown']==1) ? 'checked="checked"' : '';
		$checked['deceased'] = ($this->famu->user['deceased']==1) ? 'checked="checked"' : '';
		$checked['tz_dst'] = ($this->famu->user['tz_dst']==1) ? 'checked="checked"' : '';
		$checked['superuser'] = ($this->famu->user['superuser']==1) ? 'checked="checked"' : '';
		$checked['active'] = ($this->famu->user['active']==1) ? 'checked="checked"' : '';
		$checked['nickname_is_full'] = ($this->famu->user['nickname_is_full']==1) ? 'checked="checked"' : '';
		$smsOptions = $this->tmpl->getSmsGatewayOptions($this->famu->user['sms_carrier']);
		$tz_Options = $this->tmpl->getTimezoneOptions($this->famu->user['tz_offset'], $this->famu->user['tz_dst']);

		return <<<EOD
		<div class="photoFrame">
			<h3>Profile Information</h3>
			<form method="POST">
			<input type="hidden" class="forms" name="profile[user_id]" value="{$this->famu->user['user_id']}"/>
			<table width="100%" border="0">
				<tr><td align="right">First Name</td>
					<td><input type="text" class="forms" name="profile[first_name]" value="{$this->famu->user['first_name']}" maxlength="25"/></td></tr>
				<tr><td align="right">Nickname</td>
					<td><input type="text" class="forms" name="profile[nickname]" value="{$this->famu->user['nickname']}" maxlength="25"/></td></tr>
				<tr><td align="right"></td>
					<td><input type="checkbox" class="forms" name="profile[nickname_is_full]" value="on" {$checked['nickname_is_full']}/><span class="detail">nickname is full name</span></td></tr>
				<tr><td align="right">Last Name</td>
					<td><input type="text" class="forms" name="profile[last_name]" value="{$this->famu->user['last_name']}" maxlength="25"/></td></tr>
				<tr><td align="right">Gender</td>
					<td><span class="detail"><input type="radio" class="forms" name="profile[gender]" value="M" {$checked['gender_M']}/> Male <input type="radio" class="forms" name="profile[gender]" value="F" {$checked['gender_F']}/> Female</span></td></tr>
				<tr><td align="right">Birth Date</td>
					<td><input type="text" class="forms" name="profile[birth_date]" value="{$birthday}" /></td></tr>
				<tr><td align="right"></td>
					<td><input type="checkbox" class="forms" name="profile[birth_year_unknown]" value="on" {$checked['yearunknown']}/><span class="detail">birth year unknown</span></td></tr>
				<tr><td align="right">Deceased</td>
					<td><input type="checkbox" class="forms" name="profile[deceased]" value="on" {$checked['deceased']}/><span class="detail">blank if you're still here</span></td></tr>
				<tr><td align="right">Timezone</td>
					<td><select class="forms" name="profile[timezone]">{$tz_Options}</select></td></tr>
				<tr><td></td>
					<td><input type="submit" class="buttons" value="Save Changes" /></td></tr>
			</table>
			</form>
		</div>
EOD;
	}
}

?>