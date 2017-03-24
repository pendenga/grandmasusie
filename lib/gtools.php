<?php
error_reporting(E_ERROR|E_WARNING|E_PARSE);
//error_reporting(E_ALL);

/**
 * Config class can be loaded on every page and independent (before) any other
 * class.  This code should be fast, low level, and independent of any other class
 */
class GTools {
    const CONFIG_FILE = '/../conf/config.xml';
	const CRYPT_KEY = 'Grundy';

	static function convertBytes($bytes) {
		if ($bytes < 1024) { return "{$bytes} bytes"; }
		$kbyte = ($bytes / 1024);
		if ($kbyte < 1024) { return sprintf("%d Kb", $kbyte); }
		$mbyte = ($kbyte / 1024);
		if ($mbyte < 1024) { return sprintf("%0.1f Mb", $mbyte); }
		$gbyte = ($mbyte / 1024);
		if ($gbyte < 1024) { return sprintf("%0.2f Gb", $gbyte); }
		$tbyte = ($kbyte / 1024);
		return sprintf("%0.2f Tb", $tbyte);
	}

	static function curl_get_file($url) {
		$ch = curl_init();
		$timeout = 5; // set to zero for no timeout
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$file_contents = curl_exec($ch);
		curl_close($ch);

		return $file_contents;
	}

	static function curl_get_store($url, $filename) {
		$content = self::curl_get_file($url);
		$success = false;
		if ($fh = fopen($filename, 'w+')) {
			$success = (fwrite($fh, $content) !== false);
			fclose($fh);
		}
		return $success;
	}

	/*
	Plugin Name: DateDiff
	Version: 1.0
	Plugin URI: http://maseko.com/project/wp-plugins/wp-datediff/
	Description: Calculate the difference between two dates like Microsoft Excel's datedif.
	Author: maseko
	Author URI: http://maseko.com/


	License information
	Copyright 2005
	Released under the GPL license
	http://www.gnu.org/licenses/gpl.txt

		This file is a plugin for WordPress
		WordPress is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License as published by
		the Free Software Foundation; either version 2 of the License, or
		(at your option) any later version.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.
	*/
	static function datediff($start_date,$end_date="now",$unit="D") {
		$unit = strtoupper($unit);
		$start=strtotime($start_date);
		if ($start === -1) {
			print("invalid start date");
		}

		$end=strtotime($end_date);
		if ($end === -1) {
			print("invalid end date");
		}

		if ($start > $end) {
			$temp = $start;
			$start = $end;
			$end = $temp;
		}

		$diff = $end-$start;

		$day1 = date("j", $start);
		$mon1 = date("n", $start);
		$year1 = date("Y", $start);
		$day2 = date("j", $end);
		$mon2 = date("n", $end);
		$year2 = date("Y", $end);

		switch($unit) {
		case "D":
			return intval($diff/(24*60*60));
			break;
		case "M":
			if($day1>$day2) {
				$mdiff = (($year2-$year1)*12)+($mon2-$mon1-1);
			} else {
				$mdiff = (($year2-$year1)*12)+($mon2-$mon1);
			}
			return $mdiff;
			break;
		case "Y":
			if(($mon1>$mon2) || (($mon1==$mon2) && ($day1>$day2))){
				$ydiff = $year2-$year1-1;
			} else {
				$ydiff = $year2-$year1;
			}
			return $ydiff;
			break;
		case "YM":
			if($day1>$day2) {
				if($mon1>=$mon2) {
					$ymdiff = 12+($mon2-$mon1-1);
				} else {
					$ymdiff = $mon2-$mon1-1;
				}
			} else {
				if($mon1>$mon2) {
					$ymdiff = 12+($mon2-$mon1);
				} else {
					$ymdiff = $mon2-$mon1;
				}
			}
			return $ymdiff;
			break;
		case "YD":
			if(($mon1>$mon2) || (($mon1==$mon2) &&($day1>$day2))) {
				$yddiff = intval(($end - mktime(0, 0, 0, $mon1, $day1, $year2-1))/(24*60*60));
			} else {
				$yddiff = intval(($end - mktime(0, 0, 0, $mon1, $day1, $year2))/(24*60*60));
			}
			return $yddiff;
			break;
		case "MD":
			if($day1>$day2) {
				$mddiff = intval(($end - mktime(0, 0, 0, $mon2-1, $day1, $year2))/(24*60*60));
			} else {
				$mddiff = intval(($end - mktime(0, 0, 0, $mon2, $day1, $year2))/(24*60*60));
			}
			return $mddiff;
			break;
		default:
			return "{Datedif Error: Unrecognized \$unit parameter. Valid values are 'Y', 'M', 'D', 'YM'. Default is 'D'.}";
		}
	}

	/**
	 * $decrypted = decrypt($encrypted,self::CRYPT_KEY); //decrypts the data using the key
	 *	echo $decrypted;
	 */
	static function decrypt($s) {
		$s=base64_decode(urldecode($s));
		for($i=1;$i<=strlen($s);$i++) $s[$i-1]=chr(ord($s[$i-1])-ord(substr(md5(self::CRYPT_KEY),($i % strlen(md5(self::CRYPT_KEY)))-1,1)));
		for($i=1;$i<=strlen($s)-2;$i=$i+2) $r.=$s[$i];
		return $r;
	}

	static function defaultValue(&$variable, $default) {
		$variable = ($variable == '') ? $default : $variable;
		return $variable;
	}

	/**
	 * Encrypt and Decrypt found on php.net with the 'crypt' function comments
	 * Code by geniz70
	 * $encrypted = encrypt('input text',self::CRYPT_KEY); //encrypts the data using the key
	 * echo "$encrypted<hr>";
	 */
	static function encrypt($s) {
		for($i=0;$i<=strlen($s);$i++)
		$r.=substr(str_shuffle(md5(self::CRYPT_KEY)),($i % strlen(md5(self::CRYPT_KEY))),1).$s[$i];
		for($i=1;$i<=strlen($r);$i++) $s[$i-1]=chr(ord($r[$i-1])+ord(substr(md5(self::CRYPT_KEY),($i % strlen(md5(self::CRYPT_KEY)))-1,1)));
		return urlencode(base64_encode($s));
	}

	/**
	 * Forks a shell command, obviously
	 */
	static function fork($shellCmd) {
		self::logOutput("FORK: $shellCmd");
		exec("nice $shellCmd > /dev/null 2>&1 &");
	}

	/**
	 * Pretty formats the age in days for printing
	 */
	static function formatAge($ageDays) {
		if (abs($ageDays) == 0) {
			return "born this day";

		// age in days
		} elseif (abs($ageDays) < 14) {
			return ($ageDays==1) ? "one day old" : "{$ageDays} days old";

		// age in weeks
		} elseif (abs($ageDays) < 9*12) {
			$weeks = floor($ageDays/7);
			return ($weeks==1) ? "one week old" : "{$weeks} weeks old";

		// age in months
		} elseif (abs($ageDays) < 365*2) {
			$months = floor($ageDays/30.4375);
			return ($months==1) ? "one month old" : "{$months} months old";

		// age in years/months
		} elseif (abs($ageDays/365.25) < 5) {
			$months = floor($ageDays/30.4375);
			if ($months % 12 == 0) {
				$years = floor($months/12);
				return ($years==1) ? "one year old" : "{$years} years old";
			} else {
				return floor($months/12)." yr, ".($months % 12)." mon";
			}

		// age in years only
		} else {
			return (floor($ageDays/365.25) < 18) ? floor($ageDays/365.25)." years old" : floor($ageDays/365.25);
		}
	}

	static function formatDuration($difference, $minutes=false) {
		// convert seconds to minutes
		if (!$minutes) {
			$difference = $difference/60;
		}

		$day = floor($difference/1440);
		$hrs = floor(($difference % 1400)/60);
		$min = ($difference % 60);

		$outstr = Array();
		if ($day > 0) {
			$outstr[] = ($day==1) ? "one day" : "$day days";
		}
		if ($hrs > 0) {
			$outstr[] = ($hrs==1) ? "one hour" : "$hrs hrs";
		}
		if ($min > 0) {
			$outstr[] = ($min==1) ? "one min" : "$min min";
		}
		return implode(', ',$outstr);
	}

	static function formatPhoneNumber($number) {
		if (strlen($number)==10) {
			$area = substr($number, 0, 3);
			$firs = substr($number, 3, 3);
			$last = substr($number, 6, 4);
			return "({$area}) {$firs}-{$last}";
		} elseif (strlen($number)==7) {
			$firs = substr($number, 3, 3);
			$last = substr($number, 6, 4);
			return "{$firs}-{$last}";
		} else {
			return $number;
		}
	}

	function getFullAvatar($avatar_id=false) {
        $xml = simplexml_load_file(realpath(dirname(__FILE__) . self::CONFIG_FILE));
		if ($avatar_id!==false && is_file(trim($xml->photo) . "avatar/{$avatar_id}.jpg")) {
			return "http://{$_SERVER['HTTP_HOST']}/static/avatar/{$avatar_id}.jpg";
		} else {
			return "http://{$_SERVER['HTTP_HOST']}/static/avatar/default.jpg";
		}
	}

	/**
	 * Regex from: http://fightingforalostcause.net/misc/2006/compare-email-regex.php
	 */
	static function isValidEmailAddress($address='') {
		$p = '/^([a-zA-Z0-9_\'+*$%\^&!\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9:]{2,4})+$/';
		$isValid = preg_match($p, $address);
		return $isValid;
	}

	static function logOutput($output) {
        $xml = simplexml_load_file(realpath(dirname(__FILE__) . self::CONFIG_FILE));
        $debug_log = trim($xml->debug);

        // check for directory
		if (!is_dir(dirname($debug_log)))
			mkdir(dirname($debug_log), 0755, true);

		// output is an array
		if (is_array($output)) {
			ob_start();
			print_r($output);
			$output = ob_get_contents();
			ob_end_clean();
		}

		if (is_writable($debug_log)) {
			$fh = fopen($debug_log, "a");
			fwrite($fh,date('Ymd H:i:s').": $output\n");
			fclose($fh);
		}
	}

	static function logTimer($output) {
        $xml = simplexml_load_file(realpath(dirname(__FILE__) . self::CONFIG_FILE));
        $timer_log = trim($xml->timer);

		// check for directory
		if (!is_dir(dirname($timer_log)))
			mkdir(dirname($timer_log), 0755, true);

		if (is_writable($timer_log)) {
			$fh = fopen($timer_log,"a");
			fwrite($fh,date('Ymd H:i:s').": $output\n");
			fclose($fh);
		}
	}


	static function takeTime($timeStr, $exact=false) {
		$time = strtotime($timeStr);
		$pattern = ($exact) ? 'l M j, Y g:ia' : 'l M j, Y';
		return date($pattern, $time);
	}

	static function postTime($timeStr) {
		if ($timeStr == '') {
			return '';
		}
		$time = strtotime($timeStr);
		$diff = time()-$time;
		if ($diff < 1) {
			return "moments ago";
		} elseif ($diff < 60) {
			return $diff." seconds ago";
		} elseif (($diff/60) < 60) {
			return intval($diff/60)." minutes ago";
		} elseif (date('Yz', $time)==date('Yz')) {
			return date('g:ia', $time)." today";
		} elseif ((intval(date('Yz', $time))+1)==intval(date('Yz'))) {
			return date('g:ia', $time)." yesterday";
		} elseif ($diff/60/60/24 <= 10) {
			return ceil($diff/60/60/24)." days ago";
		} elseif (date('Y', $time)==date('Y')) {
			return date('M j', $time);
		} else {
			return date('M j, Y', $time);
		}
	}

	function truncateParagraph($para, $length=100) {
		if (strlen($para)>$length) {
			$para = substr($para, 0, $length);
			$parts = explode(' ', $para);
			array_pop($parts);
			$para = implode(' ', $parts);
			$para .= '...';
		}
		return nl2br($para);
	}
}

?>
