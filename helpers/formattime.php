<?php
/**
 * Time Helper class file.
 * Based on TimeHelper from CakePHP(tm)
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 */

/**
 * Time Helper class for easy use of time data.
 * Manipulation of time data.
 */
class FormatTime {
	protected static $tz_offset = -8;
	protected static $tz_dst = 1;

	/**
	 * Returns a UNIX timestamp, given either a UNIX timestamp or a valid strtotime() date string.
	 * @param string $date_string Datetime string
	 * @return string Formatted date string
	 */
	private static function fromString($date_string) {
		if (is_integer($date_string) || is_numeric($date_string)) {
			return intval($date_string);
		} else {
			return strtotime($date_string);
		}
	}

	/**
	 * Pretty formats the age in days for printing
	 */
	static function ageInDays($ageDays) {
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

	/**
	 * Returns a nicely formatted date string for given Datetime string.
	 * @param string $date_string Datetime string or Unix timestamp
	 * @return string Formatted date string
	 */
	static function nice($date_string = null) {
		if ($date_string != null) {
			$date = self::fromString($date_string);
		} else {
			$date = time();
		}
		return date("D, M jS Y, H:i", $date);
	}


	/**
	 * Returns a formatted descriptive date string for given datetime string.
	 * If the given date is today, the returned string could be "Today, 16:54".
	 * If the given date was yesterday, the returned string could be "Yesterday, 16:54".
	 * If $date_string's year is the current year, the returned string does not
	 * include mention of the year.
	 * @param string $date_string Datetime string or Unix timestamp
	 * @return string Described, relative date string
	 */
	static function niceShort($date_string = null) {
		$date = $date_string ? self::fromString($date_string) : time();

		$y = self::isThisYear($date) ? '' : ' Y';

		if (self::isToday($date)) {
			$ret = "Today, " . date("H:i", $date);
		} elseif (self::wasYesterday($date)) {
			$ret = "Yesterday, " . date("H:i", $date);
		} else {
			$ret = date("M jS{$y}, H:i", $date);
		}
		return $ret;
	}

	/**
	 * Returns a partial SQL string to search for all records between two dates.
	 * @param string $date_string Datetime string or Unix timestamp
	 * @param string $end Datetime string or Unix timestamp
	 * @param string $field_name Name of database field to compare with
	 * @return string Partial SQL string.
	 */
	static function daysAsSql($begin, $end, $field_name) {
		$begin = self::fromString($begin);
		$end = self::fromString($end);
		$begin = date('Y-m-d', $begin) . ' 00:00:00';
		$end = date('Y-m-d', $end) . ' 23:59:59';
		return "($field_name >= '$begin') AND ($field_name <= '$end')";
	}

	/**
	 * Returns a partial SQL string to search for all records between two times
	 * occurring on the same day.
	 * @param string $date_string Datetime string or Unix timestamp
	 * @param string $field_name Name of database field to compare with
	 * @return string Partial SQL string.
	 */
	static function dayAsSql($date_string, $field_name) {
		$date = self::fromString($date_string);
		return self::daysAsSql($date_string, $date_string, $field_name);
	}

	/**
	 * Returns true if given datetime string is today.
	 * @param string $date_string Datetime string or Unix timestamp
	 * @return boolean True if datetime string is today
	 */
	static function isToday($date_string) {
		$date = self::fromString($date_string);
		return date('Y-m-d', $date) == date('Y-m-d', time());
	}

	/**
	 * Returns true if given datetime string is within this week
	 * @param string $date_string
	 * @return boolean True if datetime string is within current week
	 */
	static function isThisWeek($date_string) {
		$date = self::fromString($date_string) + 86400;
		return date('W Y', $date) == date('W Y', time());
	}

	/**
	 * Returns true if given datetime string is within this month
	 * @param string $date_string
	 * @return boolean True if datetime string is within current month
	 */
	static function isThisMonth($date_string) {
		$date = self::fromString($date_string);
		return date('m Y',$date) == date('m Y', time());
	}

	/**
	 * Returns true if given datetime string is within current year.
	 * @param string $date_string Datetime string or Unix timestamp
	 * @return boolean True if datetime string is within current year
	 */
	static function isThisYear($date_string) {
		$date = self::fromString($date_string);
		return  date('Y', $date) == date('Y', time());
	}

	/**
	 * Returns true if given datetime string was yesterday.
	 * @param string $date_string Datetime string or Unix timestamp
	 * @return boolean True if datetime string was yesterday
	 */
	static function wasYesterday($date_string) {
		$date = self::fromString($date_string);
		return date('Y-m-d', $date) == date('Y-m-d', strtotime('yesterday'));
	}

	/**
	 * Returns true if given datetime string is tomorrow.
	 * @param string $date_string Datetime string or Unix timestamp
	 * @return boolean True if datetime string was yesterday
	 */
	static function isTomorrow($date_string) {
		$date = self::fromString($date_string);
		return date('Y-m-d', $date) == date('Y-m-d', strtotime('tomorrow'));
	}

	/**
	 * Returns the quart
	 * @param string $date_string
	 * @param boolean $range if true returns a range in Y-m-d format
	 * @return boolean True if datetime string is within current week
	 */
	static function toQuarter($date_string, $range = false) {
		$time = self::fromString($date_string);
		$date = ceil(date('m', $time) / 3);

		if ($range === true) {
			$range = 'Y-m-d';
		}

		if ($range !== false) {
			$year = date('Y', $time);

			switch ($date) {
				case 1:
					$date = array($year.'-01-01', $year.'-03-31');
					break;
				case 2:
					$date = array($year.'-04-01', $year.'-06-30');
					break;
				case 3:
					$date = array($year.'-07-01', $year.'-09-30');
					break;
				case 4:
					$date = array($year.'-10-01', $year.'-12-31');
					break;
			}
		}
		return $date;
	}

	/**
	 * Returns a UNIX timestamp from a textual datetime description. Wrapper for PHP static function strtotime().
	 * @param string $date_string Datetime string to be represented as a Unix timestamp
	 * @return integer Unix timestamp
	 */
	static function toUnix($date_string) {
		return self::fromString($date_string);
	}

	/**
	 * Returns a date formatted for Atom RSS feeds.
	 * @param string $date_string Datetime string or Unix timestamp
	 * @return string Formatted date string
	 */
	static function toAtom($date_string) {
		return date('Y-m-d\TH:i:s\Z', self::fromString($date_string));
	}

	/**
	 * Formats date for RSS feeds
	 * @param string $date_string Datetime string or Unix timestamp
	 * @return string Formatted date string
	 */
	static function toRSS($date_string) {
		return date("r", self::fromString($date_string));
	}


	/**
	 * Time ago in words, formatting for post age
	 */
	static function postTime($timeStr) {
		if ($timeStr == '') {
			return '';
		}
		$time = strtotime($timeStr);
		$diff = time()-$time;

		if ($diff < 1) {
			return "moments ago";
		} elseif ($diff < 61) {
			return $diff." seconds ago";
		} elseif (($diff/60) < 90) {
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


	/**
	 * Returns either a relative date or a formatted date depending
	 * on the difference between the current time and given datetime.
	 * $datetime should be in a <i>strtotime</i>-parsable format, like MySQL's datetime datatype.
	 *
	 * Relative dates look something like this:
	 *	3 weeks, 4 days ago
	 *	15 seconds ago
	 * Formatted dates look like this:
	 *	on 02/18/2004
	 *
	 * The returned string includes 'ago' or 'on' and assumes you'll properly add a word
	 * like 'Posted ' before the static function output.
	 *
	 * @param string $date_string Datetime string or Unix timestamp
	 * @param array $options Default format if timestamp is used in $date_string
	 * @param string $backwards False if $date_string is in the past, true if in the future
	 * @return string Relative time string.
	 */
	static function timeAgoInWords($datetime_string, $options = array(), $backwards = null) {
		$in_seconds = self::fromString($datetime_string);

		if ($backwards === null && $in_seconds > time()) {
			$backwards = true;
		}

		$format = 'j/n/y';
		$end = '+1 month'; //when to show format

		if (is_array($options)) {
			if (isset($options['format'])) {
				$format = $options['format'];
				unset($options['format']);
			}
			if (isset($options['end'])) {
				$end = $options['end'];
				unset($options['end']);
			}
		} else {
			$format = $options;
		}

		if ($backwards) {
			$start = abs($in_seconds - time());
		} else {
			$start = abs(time() - $in_seconds);
		}

		$months = floor($start / 2638523.0769231);
		$diff = $start - $months * 2638523.0769231;
		$weeks = floor($diff / 604800);
		$diff -= $weeks * 604800;
		$days = floor($diff / 86400);
		$diff -= $days * 86400;
		$hours = floor($diff / 3600);
		$diff -= $hours * 3600;
		$minutes = floor($diff / 60);
		$diff -= $minutes * 60;
		$seconds = $diff;

		$relative_date = '';

		if ($start > abs(time() - self::fromString($end))) {
			$relative_date = 'on ' . date($format, $in_seconds);
		} else {
			if (abs($months) > 0) {
				// months, weeks and days
				$relative_date .= ($relative_date ? ', ' : '') . $months . ' month' . ($months > 1 ? 's' : '');
				$relative_date .= $weeks > 0 ? ($relative_date ? ', ' : '') . $weeks . ' week' . ($weeks > 1 ? 's' : '') : '';
				$relative_date .= $days > 0 ? ($relative_date ? ', ' : '') . $days . ' day' . ($days > 1 ? 's' : '') : '';
			} elseif (abs($weeks) > 0) {
				// weeks and days
				$relative_date .= ($relative_date ? ', ' : '') . $weeks . ' week' . ($weeks > 1 ? 's' : '');
				$relative_date .= $days > 0 ? ($relative_date ? ', ' : '') . $days . ' day' . ($days > 1 ? 's' : '') : '';
			} elseif (abs($days) > 0) {
				// days and hours
				$relative_date .= ($relative_date ? ', ' : '') . $days . ' day' . ($days > 1 ? 's' : '');
				$relative_date .= $hours > 0 ? ($relative_date ? ', ' : '') . $hours . ' hour' . ($hours > 1 ? 's' : '') : '';
			} elseif (abs($hours) > 0) {
				// hours and minutes
				$relative_date .= ($relative_date ? ', ' : '') . $hours . ' hour' . ($hours > 1 ? 's' : '');
				$relative_date .= $minutes > 0 ? ($relative_date ? ', ' : '') . $minutes . ' minute' . ($minutes > 1 ? 's' : '') : '';
			} elseif (abs($minutes) > 0) {
				// minutes only
				$relative_date .= ($relative_date ? ', ' : '') . $minutes . ' minute' . ($minutes > 1 ? 's' : '');
			} else {
				// seconds only
				$relative_date .= ($relative_date ? ', ' : '') . $seconds . ' second' . ($seconds != 1 ? 's' : '');
			}

			if (!$backwards) {
				$relative_date .= ' ago';
			}
		}
		return $relative_date;
	}

	/**
	 * Alias for timeAgoInWords, but can also calculate dates in the future
	 * @param string $date_string Datetime string or Unix timestamp
	 * @param string $format Default format if timestamp is used in $date_string
	 * @return string Relative time string.
	 * @see		timeAgoInWords
	 */
	static function relativeTime($datetime_string, $format = 'j/n/y') {
		return self::timeAgoInWords($datetime_string, $format, (time() <= strtotime($datetime_string)));
	}

	/**
	 * Returns true if specified datetime was within the interval specified, else false.
	 * @param mixed $timeInterval the numeric value with space then time type. Example of valid types: 6 hours, 2 days, 1 minute.
	 * @param mixed $date_string the datestring or unix timestamp to compare
	 * @return bool
	 */
	static function wasWithinLast($timeInterval, $date_string) {
		$date = self::fromString($date_string);
		$result = preg_split('/\\s/', $timeInterval);
		$numInterval = $result[0];
		$textInterval = $result[1];
		$currentTime = floor(time());
		$seconds = ($currentTime - floor($date));

		switch($textInterval) {
			case "seconds":
			case "second":
				$timePeriod = $seconds;
				$ret = $return;
			break;

			case "minutes":
			case "minute":
				$minutes = floor($seconds / 60);
				$timePeriod = $minutes;
			break;

			case "hours":
			case "hour":
				$hours = floor($seconds / 3600);
				$timePeriod = $hours;
			break;

			case "days":
			case "day":
				$days = floor($seconds / 86400);
				$timePeriod = $days;
			break;

			case "weeks":
			case "week":
				$weeks = floor($seconds / 604800);
				$timePeriod = $weeks;
			break;

			case "months":
			case "month":
				$months = floor($seconds / 2638523.0769231);
				$timePeriod = $months;
			break;

			case "years":
			case "year":
				$years = floor($seconds / 31556926);
				$timePeriod = $years;
			break;

			default:
				$days = floor($seconds / 86400);
				$timePeriod = $days;
			break;
		}

		if ($timePeriod <= $numInterval) {
			$ret = true;
		} else {
			$ret = false;
		}

		return $ret;
	}

	/**
	 * Returns gmt, given either a UNIX timestamp or a valid strtotime() date string.
	 * @param string $date_string Datetime string
	 * @return string Formatted date string
	 */
	static function gmt($string = null) {
		if ($string != null) {
			$string = self::fromString($string);
		} else {
			$string = time();
		}
		$string = self::fromString($string);
		$hour = intval(date("G", $string));
		$minute = intval(date("i", $string));
		$second = intval(date("s", $string));
		$month = intval(date("n", $string));
		$day = intval(date("j", $string));
		$year = intval(date("Y", $string));

		return gmmktime($hour, $minute, $second, $month, $day, $year);
	}
}

?>