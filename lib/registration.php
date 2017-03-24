<?php

include_once 'module.php';

class Reg_NoUserException extends Exception{}

abstract class Registration extends Module {
	abstract function signupConfirmationEmail($username, $password, $email, $code);
	abstract function signupForm();
	abstract function signinFailed($instructions);
	abstract function signupFailed($instructions);
	abstract function signupNotes($instructions);
	abstract function signupSuccess($instructions);

	function newSignupUser($username, $password, $email) {
		//$signcode = $this->encrypt($username);
		$signcode = uniqid(str_replace(' ', '_', $this->site['site_name']));

		// create signup user
		$this->do_sql("INSERT INTO user_signup (signup_code, signup_site, signup_email, signup_username, signup_password) VALUES ('{$signcode}', {$this->site['site_id']}, '{$email}', '{$username}', '{$password}')");

		list($subject, $body) = $this->signupConfirmationEmail($username, $password, $email, $signcode);
		return $this->sendEmail($email, $subject, $body);
	}

	/**
	 * Check to see that a username exists in the system or the signup table.
	 * Returns array of user data if found, false if not.  If found "username exists".
	 */
	function checkUsers($username) {
		$query = "SELECT username, password, email_address AS email, 'none' AS signup_code, 'user' AS status FROM users WHERE username = '{$username}' UNION ALL SELECT signup_username AS username, signup_password AS password, signup_email AS email, signup_code, 'signup' AS status FROM user_signup WHERE signup_username='{$username}'";
		$existing = $this->do_sql($query);
		return (count($existing)>0) ? $existing[0] : false;
	}


	/**
	 * Tries to create user, then attach to site.  Returns a pair of boolean
	 * values for whether the user was created, then whether he was attached
	 * to a site (true) or is pending approval (false).
	 */
	function insertSignupUser($signup_code) {
		$signup_code = trim($signup_code);
		$result = $this->do_sql("SELECT u.signup_id, u.signup_username, u.signup_password, u.signup_email, u.signup_site, s.site_name, s.open_enroll FROM user_signup u INNER JOIN site s ON u.signup_site = s.site_id WHERE u.signup_code='{$signup_code}' AND completed=0");

		$user = (count($result)<1) ? false : $result[0];
		if ($user === false) {
			return array(false, false);
		} else {
			$this->do_sql("INSERT INTO users (username, password, email_address, email_confirmed) VALUES ('{$user['signup_username']}', '{$user['signup_password']}', '{$user['signup_email']}', 1)");
						
			// see if site allows open enrollment
			$pending = ($user['open_enroll']==0) ? 1 : 0;

			// insert into user-size association
			$this->do_sql("INSERT INTO site_member (site_id, user_id, pending) VALUES ({$user['signup_site']}, LAST_INSERT_ID(), {$pending})");

			// complete signup record
			$this->do_sql("UPDATE user_signup SET completed=1 WHERE signup_id={$user['signup_id']} LIMIT 1");
			
			return array($user['signup_username'], ($pending==1) ? true : false, $user['site_name']);
		}
	}
}

?>