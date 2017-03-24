<?php

include_once 'module.php';
include_once 'photo.php';

class UserForm extends Module {

	function getHeader($page) {
		$name = $this->famu->getUserName();
		$page = ucwords($page);
		$avtr = $this->famu->getFullAvatarLink($this->famu->user['avatar_id'], $this->famu->user['user_id'], $name, "<h4>{$name}</h4>");
		//$avtr = $this->famu->getFullAvatarLink($feat['avatar_id'], $feat['user_id'], $name, $name, 32);
		$blog = $this->famu->getBlogId();
		$blogLink = ($blog) ? "<li><a href=\"/read/blog/{$blog}/\" title=\"List of all blog entries.\" class=\"help\">Blog</a></li>" : '';


		return <<<EOD
		<div class="photoAuthor clearfix">
			{$avtr}
			<ul class="userLinks">
				<li><a href="/user/{$this->famu->active_id}/profile/" title="Phone number, address, family members, etc." class="help">Profile</a></li>
				{$blogLink}
				<li><a href="/user/{$this->famu->active_id}/photos/" title="All photos uploaded by this user." class="help">Photos</a></li>
				<li><a href="/user/{$this->famu->active_id}/favorites/" title="All photos marked as favorite." class="help">Favorites</a></li>
				<li><a href="/user/{$this->famu->active_id}/featured/" title="All photos featuring this user." class="help">Featuring</a></li>
			</ul>
		</div>

EOD;
	}

	function getImmediateFamily() {
		$relatives = $this->db->do_sql("SELECT * FROM parentgroup_view WHERE src_id={$this->famu->active_id} {$directClause} ORDER BY degree");

		if (count($relatives)>0) {
			foreach ($relatives as $rel) {
				$avtr = $this->famu->getFullAvatar($rel['dest_avatar_id']);
				if ($rel['direct']=='1') {
					$direct .= <<<EOD
					<li class="clearfix"><a href="/user/{$rel['dest_id']}/editfamily/"><img src="{$avtr}" class="avatar"/></a><strong>{$rel['dest_common_name']}</strong><div>{$rel['dest_to_src']} <input type="button" class="smallbuttons" value="remove" onclick="new Ajax.Updater('set_family', '/user_server/{$this->famu->active_id}/removefamily/{$rel['dest_id']}/')"/></div></li>
EOD;
				} else {
					$extended .= <<<EOD
					<a href="/user/{$rel['dest_id']}/editfamily/"><img src="{$avtr}" title="{$rel['dest_common_name']} - {$rel['dest_to_src']}" class="help"/></a>
EOD;
				}
			}
			$extended = ($extended!='') ? "<h4>Extended Family</h4><p>{$extended}</p>" : '';
		}

		return <<<EOD
	<ul>{$direct}</ul>{$extended}

EOD;
	}

	function editFamily() {
		$family = $this->getImmediateFamily();	
		return <<<EOD
		<div class="photoFrame">
			<h4>Immediate Family</h4>
			<form id="addFamilyRelation" class="sideSearch" method="POST">
				<input type="text" id="feat" name="searchString"/> as 
				<select name="relationship"><option value="c">Child</option><option value="p">Parent</option><option value="s">Spouse</option></select>
				<input type="button" class="buttons" value="Add" onclick="new Ajax.Updater('set_family', '/user_server/{$this->famu->active_id}/addfamily/', {method: 'post', parameters: $('addFamilyRelation').serialize(true), onSuccess: function() {\$('feat').value=''; \$('feat').focus();}})"/>
				<div id="feat_auto_complete" class="auto_complete"></div>
			</form>
			<script>
			Event.observe(window, 'load', function() {
				new Ajax.Autocompleter('feat', 'feat_auto_complete', '/search_server/users/', {});
			});
			</script>
			<div id="set_family">{$family}</div>				
		</div>
EOD;
	}

	function addFamilyRelationship($name, $type) {
		if (trim($name)=='') {
			return "<span class=\"error\">Please provide a name</span>";
		}
		$users = $this->db->do_sql("SELECT user_id FROM user_name WHERE full_name='{$name}'");
		if (count($users) > 1) {
			return "<span class=\"error\">This name is ambiguous</span>";
		} elseif (count($users) < 1) {
			return "<span class=\"error\">Name Not Found</span>";
		} else {
			$dest_id = $users[0]['user_id'];
			if ($type=='c') {
				$this->db->do_sql("call parentgroup_addchild({$this->famu->active_id}, {$dest_id})");
			} elseif ($type=='p') {
				$this->db->do_sql("call parentgroup_addchild({$dest_id}, {$this->famu->active_id})");
			} elseif ($type=='s') {
				$this->db->do_sql("call parentgroup_addspouse({$this->famu->active_id}, {$dest_id})");
			}
			return "$name added";
		}
	}

	function removeFamilyRelationship($dest_id) {
		$this->db->do_sql("call parentgroup_remove({$this->famu->active_id}, {$dest_id})");
		return "All relevant relationships deleted";
	}

	function getProfile($moderator=false) {
		$row_sms = $this->profile_smsRows();
		$row_bdy = $this->profile_birthdayRows();
		$row_mai = $this->profile_emailRows();
		$row_hou = $this->profile_householdRows();
		$row_rel = $this->profile_relations($moderator);

		return <<<EOD
		<table id="contactInfo" width="100%" border="0">
		<tr><td></td></tr>
		{$row_bdy}
		{$row_mai}
		{$row_sms}
		{$row_hou}
		{$row_rel}
		</table>
EOD;
	}

	private function profile_relations($editLink=false) {
		$rs = $this->db->do_sql("SELECT * FROM parentgroup_view WHERE src_id={$this->famu->active_id}");
		foreach ($rs as $fam) {
			$avtr = $this->famu->getFullAvatar($fam['dest_avatar_id']);
			$output .= <<<EOD
			<a href="/user/{$fam['dest_id']}/profile/" title="{$fam['dest_full_name']} - {$fam['dest_to_src']}" class="help">
				<img src="{$avtr}" style="float: left" border="0"/>
			</a>
EOD;
		}
		if ($output != '') {
			$link = ($editLink) ? "<a href=\"/user/{$this->famu->active_id}/editfamily/\" class=\"detail\">[ edit ]</a>" : '';
			return <<<EOD
			<tr><td colspan="2"><strong>Site Users Related to {$this->famu->user['first_name']}</strong> {$link}</td></tr>
			<tr><td colspan="2"><div class="avatarGallery">{$output}<br style="clear: both"/></div></td></tr>
EOD;
		} elseif ($editLink) {
			return <<<EOD
			<tr><td colspan="2"><strong><a href="/user/{$this->famu->active_id}/editfamily/">Add Family Relationships</a></td></tr>
EOD;
		}
	}

	private function profile_birthdayRows() {
		if ($this->famu->user['birth_date']!='') {
			$deceased = ($this->famu->user['deceased']) ? ' <span class="detail">deceased</span>' : '';
			$birthday = ($this->famu->user['birth_year_unknown']) ? date('M j', strtotime($this->famu->user['birth_date'])) : date('M j, Y', strtotime($this->famu->user['birth_date']));
			return "<tr><td align=\"right\">Birthday: </td><td>{$birthday}{$deceased}</td></tr>";
		}
	}

	/**
	 * displays optional email
	 */
	private function profile_emailRows() {		
		if ($this->famu->user['email_address']!='') {
			return <<<EOD
			<tr><td align="right">Email: </td>
				<td><a href="mailto:{$this->famu->user['email_address']}">{$this->famu->user['email_address']}</a></td></tr>
EOD;
		}
	}

	private function profile_householdRows() {
		if ($this->famu->user['household_id']!='') {
			$rs = $this->db->do_sql("SELECT * FROM household WHERE house_id={$this->famu->user['household_id']}");
			if (count($rs)>0) {
				$ho = $rs[0];
				if ($ho['hoh_1_first']!='' && $ho['hoh_2_first']!='') {
					if ($ho['hoh_1_last']==$ho['hoh_2_last']) {
						if ($ho['hoh_1_first']==$ho['hoh_2_first']) {
							$h_name = "{$ho['hoh_1_first']} {$ho['hoh_2_last']}";
						} else {
							$h_name = "{$ho['hoh_1_first']} & {$ho['hoh_2_first']} {$ho['hoh_2_last']}";
						}
					} else {
						$h_name = "{$ho['hoh_1_first']} {$ho['hoh_1_last']} & {$ho['hoh_2_first']} {$ho['hoh_2_last']}";
					}
				} else {
					$h_name = trim("{$ho['hoh_1_first']}{$ho['hoh_2_first']} {$ho['hoh_1_last']}{$ho['hoh_2_last']}");
				}

				// format household address
				$addr = ($ho['address']) ? "{$ho['address']}<br/>" : ''; 
				$addr2 = ($ho['address_2']) ? "{$ho['address_2']}<br/>" : '';
				$locale = ($ho['city']!='' && $ho['state']!='') ? "{$ho['city']}, {$ho['state']}" : "{$ho['city']}{$ho['state']}";
				$locale = trim("{$locale}  {$ho['postal_code']}");
				$country = ($ho['country'] != '' && $ho['country'] != 'US' && $locale!='') ? "<br/>{$ho['country']}" : '';
				$fullAddress = "{$addr}{$addr2}{$locale}{$country}";
				if (trim($fullAddress)!='') {
					$addressRow = "<tr><td align=\"right\" valign=\"top\">Address:</td><td>{$fullAddress}</td></tr>";
				}

				// format phone number
				$phoneRow = ($ho['home_phone']) ? "<tr><td align=\"right\">Phone:</td><td>{$ho['home_phone']}</td></tr>" : '';
				
				// output
				if (trim("{$addressRow}{$phoneRow}")!='') {
					return <<<EOD
					<tr><td colspan="2"><strong>{$h_name}'s House</strong></td></tr>
					{$phoneRow}
					{$addressRow}				
EOD;
				}
			}
		}
	}


	/**
	 * displays optional mobile number with optional form for sending sms message
	 */
	private function profile_smsRows() {
		// get form for sending text messages
		if ($this->famu->user['sms_confirmed']==1) {
			if ($this->famu->user['sms_cost']!='') {
				$cost = ($this->famu->user['sms_cost']==0) ? "(it's free for me)" : "(I pay per message)";
			}

			$smsform = <<<EOD
			<tr><td></td>
				<td><form id="smsForm">Send me a text message:<br/>
					<input type="text" size="30" maxlength="160" name="sms[message]"/><br/>
					<input id="smsSend" class="smallbuttons" type="button" value="Send Message" onclick="new Ajax.Request('/user_server/{$this->famu->active_id}/send_sms/', { method: 'post', parameters: $('smsForm').serialize(true), onSuccess: function(transport, json) { alert(transport.responseText); }});"/> {$cost}</form>
				</td></tr>
EOD;
		}
		
		// output form for cell number
		if ($this->famu->user['sms_number']!='') {
			$smsn = GTools::formatPhoneNumber($this->famu->user['sms_number']);
			$output = <<<EOD
			<tr><td align="right">Cell: </td>
				<td>{$smsn}</td></tr>
			{$smsform}
EOD;
		}
		return $output;
	}

	function sendSms($post) {
		if (!$this->famu->user['sms_confirmed']) {
			return "This user does not have a confirmed SMS number";
		} elseif (trim($post['message'])=='') {
			return "Please type a message, then press 'Send Message'";
		} else {
			$address = "{$this->famu->user['sms_number']}@{$this->famu->user['sms_carrier']}";
			GTools::logOutput(" SMS: {$address}, {$post['message']}");
			return (mail($address, $this->tmpl->activeName(true), $post['message'])) ? "Your message was sent" : "There was an error sending your message";		
		}
	}

}

?>