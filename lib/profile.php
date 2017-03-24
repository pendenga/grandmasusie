<?php

include_once 'module.php';

class Profile extends Module {

	function saveAvatar($post) {
		$photo = $this->db->do_sql("SELECT * FROM photo WHERE photo_id={$post['photo_id']}");
		$phto = new Photo($this->db, $this->famu, $photo[0]);
		$avatar_id = $phto->makeAvatar($post['width'], $post['x1'], $post['y1']);
		$this->famu->setAvatar($avatar_id, $post['user_id']);
		return $this->tmpl->getBoxNotify("Confirmed", "Your avatar was changed.");
	}

	function confirmContact($post) {
		if ($post['code']=='') {
			return $this->tmpl->getBoxError("Incomplete Form", "Please enter your code");
		} else {
			$lookup = $this->db->do_sql("SELECT * FROM user_confirm WHERE code='{$post['code']}'");
			if (count($lookup)<1) {
				return $this->tmpl->getBoxError("Code Not Found", "The confirmation code '{$post['code']}' was not found in our records.  Please check your code and enter it again.");
			} elseif ($lookup[0]['completed']==1) {
				return $this->tmpl->getBoxError("Invalid Code", "The confirmation code '{$post['code']}' has already been used to verify a user.  Please check your code and enter it again.");
			} else {
				$this->db->do_sql("UPDATE user_confirm SET completed=1 WHERE code='{$post['code']}'");
				if ($lookup[0]['type'] == 'sms') {
					$this->db->do_sql("UPDATE users SET sms_confirmed=1 WHERE user_id={$lookup[0]['user_id']}");
					return $this->tmpl->getBoxNotify("Confirmed", "Your mobile number was confirmed");
				} elseif ($lookup[0]['type'] == 'email') {
					$this->db->do_sql("UPDATE users SET email_confirmed=1 WHERE user_id={$lookup[0]['user_id']}");
					return $this->tmpl->getBoxNotify("Confirmed", "Your email address was confirmed");
				}
			}
		}
	}

	function getHouseholds() {
		return $this->db->do_sql("SELECT * FROM household");
	}

	function getHousehold() {
		$house = $this->db->do_sql("SELECT * FROM user_household WHERE house_id={$this->famu->user['household_id']}");
		$membr = (count($house)>0) ? $this->db->do_sql("SELECT * FROM users WHERE household_id={$this->famu->user['household_id']}") : array();
		return array($house[0], $membr);
	}

	function getHouseholdMembers() {
		if ($this->famu->user['household_id']!='') {
		} else {
			return array();
		}
	}

	function joinHousehold($post) {
		$this->db->do_sql("UPDATE users SET household_id={$post['house']} WHERE user_id={$this->famu->active_id}");
		return $this->tmpl->getBoxNotify("Household Joined", "This user has been re-assigned to this household");
	}

	function saveContact($post) {
		if ($post['email_address']!='' && !GTools::isValidEmailAddress($post['email_address'])) {
			return $this->tmpl->getBoxError("Invalid Email", "Please enter a valid email address."); 
		} elseif ($post['pref_sms']==1 && ($post['sms_number']=='' || $post['sms_carrier']=='NULL')) {
			return $this->tmpl->getBoxError("Choose a carrier.", "To use SMS as your preferred contact, we need to know the carrier on your phone.  If you are with a carrier other than the ones in the list, let us know.");
		} elseif ($post['pref_sms']==0 && $post['sms_number']!='' && $post['sms_carrier']=='NULL') {
			$output = $this->tmpl->getBoxAlert("Please choose a carrier.", "We are gathering your number in this place for the purpose of sending text messages to you.  We need to know the carrier on your phone.  If you are with a carrier other than the ones in the list, let us know.");
		}

		// update preference
		$arguments = array("pref_sms={$post['pref_sms']}");

		// update sms contacts
		if ($post['sms_number']!=$this->famu->user['sms_number'] ||
			$post['sms_carrier']!=$this->famu->user['sms_carrier']) {
			$arguments[] = "sms_number='{$post['sms_number']}'";
			$arguments[] = "sms_carrier='{$post['sms_carrier']}'";
			$arguments[] = "sms_confirmed=0";
			$this->famu->user['sms_confirmed'] = 0;
		}

		$cost = ($post['sms_cost']=='yes') ? 0 : 1; 
		$arguments[] = "sms_cost={$cost}";

		// confirm sms?
		if ($this->famu->user['sms_confirmed']==0 && $post['sms_number']!='' && $post['sms_carrier']!='') {
			if (mail("{$post['sms_number']}@{$post['sms_carrier']}", '', $this->smsConfirmation())) {
				$output .= $this->tmpl->getBoxNotify("Confirmation Sent", "A confirmatinon code was sent to {$post['sms_number']}@{$post['sms_carrier']}.  Follow the instructions in that message to confirm your mobile number."); 
			} else {
				$output .= $this->tmpl->getBoxError("Confirmation Error", "An error occured when sending a message to '{$post['sms_number']}@{$post['sms_carrier']}'");
			}
		}

		// update email contacts
		if ($post['email_address']!=$this->famu->user['email_address']) {
			$arguments[] = ($post['email_address']=='') ? "email_address=NULL" : "email_address='{$post['email_address']}'";
			$arguments[] = "email_confirmed=0";
			$this->famu->user['email_confirmed'] = 0;
		}

		// confirm email?
		if ($this->famu->user['email_confirmed']==0 && $post['email_address']!='') {
			list($subject, $body) = $this->emailConfirmation();
			if (mail("{$post['email_address']}", $subject, $body)) {
				$output .= $this->tmpl->getBoxNotify("Confirmation Sent", "A confirmatinon code was sent to {$post['email_address']}.  Follow the instructions in that message to confirm your email address."); 
			} else {
				$output .= $this->tmpl->getBoxError("Confirmation Error", "An error occured when sending a message to '{$post['email_address']}'");
			}
		}

		$this->db->do_sql("UPDATE users SET ".implode(', ', $arguments)." WHERE user_id={$this->famu->active_id} LIMIT 1");
		return $output;
	}

	function savePassword($post) {
		$signedIn = $this->db->do_sql("SELECT * FROM users WHERE user_id='{$this->famu->signin_id}' LIMIT 1");
		// form must be filled
		if ($post['password_curr']=='' || $post['password_new']=='' || $post['password_conf']=='') {
			return $this->tmpl->getBoxError("Incomplete Form", "You must fill out all the fields on this form.");
		// old password must be correct
		} elseif ($post['password_curr']!=$signedIn[0]['password']) {
			return $this->tmpl->getBoxError("Wrong Password", "The current password you entered does not match the existing password on file.");
		// new passwords must match
		} elseif ($post['password_new'] != $post['password_conf']) {
			return $this->tmpl->getBoxError("Password Mismatch", "The new passwords you provided do not match each other.");
		// all good, save to database
		} else {
			$query = "UPDATE users SET password='{$post['password_new']}' WHERE user_id={$this->famu->signin_id} LIMIT 1";
			$this->db->do_sql($query);
			$this->famu->setPasswordList();
			return $this->tmpl->getBoxNotify("Password Updated", "Your password was updated.  Next time you log in, use your new password.");
		}
	}

	function saveProfile($post) {
		// prerequisites
		$lastName = ($post['last_name']=='') ? 'NULL' : "'".addslashes($post['last_name'])."'";
		$nickname = ($post['nickname']=='') ? 'NULL' : "'".addslashes($post['nickname'])."'";
		$firstName = ($post['first_name']=='') ? 'NULL' : "'".addslashes($post['first_name'])."'";
		$nick_full = (isset($post['nickname_is_full'])) ? 1 : 0;
		$birthyear = (isset($post['birth_year_unknown'])) ? 1 : 0;
		$birthday = (strtotime($post['birth_date'])===false) ? 'NULL' : "'".date('Y-m-d', strtotime($post['birth_date']))."'";
		$deceased = (isset($post['deceased'])) ? 1 : 0;
		$gender = (isset($post['gender'])) ? 1 : 0;
		list($tzOff, $tzDST) = ($post['timezone']!='') ? explode('|', $post['timezone']) : array('NULL', 'NULL');

		// form query
		$this->db->do_sql("UPDATE users SET first_name={$firstName}, last_name={$lastName}, nickname={$nickname}, nickname_is_full={$nick_full}, gender={$gender}, birth_date={$birthday}, birth_year_unknown={$birthyear}, deceased={$deceased}, tz_offset={$tzOff}, tz_dst={$tzDST}, updated_dt=CURRENT_TIMESTAMP WHERE user_id={$post['user_id']}");
		$this->famu->refreshUser();
		return $this->tmpl->getBoxNotify("Profile Updated", "Your information has been saved.  Review the saved information in the form below to ensure correctness.");

	}

	// generate confirmation email for sms
	function smsConfirmation() {
		$code = sprintf('%04s', dechex(rand(0, hexdec('ffff'))));
		$this->db->do_sql("UPDATE user_confirm SET completed=1 WHERE user_id={$this->famu->active_id} AND type='sms'");
		$this->db->do_sql("INSERT INTO user_confirm (user_id, type, code, completed) VALUES ({$this->famu->active_id}, 'sms', '{$code}', 0)");
		return "To confirm your mobile number, go to www.grandmasusie.com/useredit/confirm/ and enter this code: {$code}";
	}

	// generate confirmation email for email address
	function emailConfirmation() {
		$code = sprintf('%04s', dechex(rand(0, hexdec('ffff'))));
		$this->db->do_sql("UPDATE user_confirm SET completed=1 WHERE user_id={$this->famu->active_id} AND type='email'");
		$this->db->do_sql("INSERT INTO user_confirm (user_id, type, code, completed) VALUES ({$this->famu->active_id}, 'email', '{$code}', 0)");
		$subject = "Email Confirmation - GrandmaSusie.com";
		$body = <<<EOD
\nDear {$this->famu->user['first_name']} {$this->famu->user['last_name']},

This automatically generated email allows you to confirm your email address by responding.

To confirm your email address, visit the following url in your web browser:
	
	http://www.grandmasusie.com/useredit/confirm/ 

and enter this code: 

	{$code}

Thank you for using GrandmaSusie.com.

GrandmaSusie.com Support
http://www.grandmasusie.com
EOD;
		return array($subject, $body);
	}
}

?>