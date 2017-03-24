<?php
/**
 * Class to combine all the text-formatting options I've got going.
 *
 * SmartyPants - converts single and double-quotes to the cool ones instead of the basic ' or "
 * Markdown - is a complete editor syntax markup thingie
 * simplifyPunctuation - converts the stuff created from SmartyPants and saved to the database that way back to simple ' or "
 * strip_tags - cleans up all but allowed tags
 */
require_once '../lib/extlib/smartypants.php';
require_once '../lib/extlib/markdown.php';

class FormatLongText {
	public static $nl2br = true;
	public static $markdown = true;
	public static $simplify = true;
	public static $smarty_pants = true;
	public static $strip_tags = true;

	/**
	 * Format data for insertion into database by stripping unauthorized tags, simplifying punctuation
	 */
	static function for_db($input) {
		$output = $input;
		if (self::$strip_tags) {
			$output = strip_tags($output, '<a><p><img><object><embed><param>');
		}
		if (self::$simplify) {
			$output = self::simplifyPunctuation($output);
		}
		return $output;
	}

	/**
	 * Format data for printing by stripping unauthorized tags, simplifying punctuation, then applying smartypants and markdown
	 */
	static function for_print($input) {
		$output = $input;
		if (self::$strip_tags) {
			$output = strip_tags($output, '<a><p><img><object><embed><param>');
		}
		if (self::$simplify) {
			$output = self::simplifyPunctuation($output);
		}
		if (self::$markdown) {
			$output = Markdown($output);		
		} elseif (self::$nl2br) {
			$output = nl2br($output);
		}
		if (self::$smarty_pants) {
			$output = SmartyPants($output);
		}
		return $output;
	}

	static function markdown($input) { 
		$output = Markdown($input);
		return $output;
	}

	static function smartypants($input) {
		$output = SmartyPants($input);
		return $output;
	}

	static function simplifyPunctuation($input) {
		//print "SIMPLIFYING";
		$output = preg_replace("/`/", "'", $input) ;
		$output=preg_replace("@\-@","-",$output);
		$output=preg_replace("@\xe2\x80\x99@","'",$output);
		$output=preg_replace("@\xe2\x80\x9c@",'"',$output);
		$output=preg_replace("@\xe2\x80\x9d@",'"',$output);
		$output=preg_replace("@\xe2\x80\xa6@",' ',$output);
		$output=preg_replace("@\xe2\x80\x94@",'-',$output);
		$output=preg_replace("@\xe2\x80\x9[0-9a-fA-F]@",'*',$output);
		return $output;
	}
}

?>