<?php

class Inflector {

	/**
	 * Takes a plural word and makes it singular
	 */	
	static function singular($str) {
	    $str = strtolower(trim($str));
	    $end = substr($str, -3);    
	    if ($end == 'ies') {
	        $str = substr($str, 0, strlen($str)-3).'y';
	    } elseif ($end == 'ses') {
	        $str = substr($str, 0, strlen($str)-2);
	    } else {
	        if (substr($str, -1) == 's') {
	            $str = substr($str, 0, strlen($str)-1);
	        }
	    }
	    return $str;
	}


	/**
	 * Takes a singular word and makes it plural
	 */	
	static function plural($str, $force=false) {
	    $str = strtolower(trim($str));
	    $end = substr($str, -1);
	    if ($end == 'y') {
	        $str = substr($str, 0, strlen($str)-1).'ies';
	    } elseif ($end == 's') {
	        if ($force == TRUE) {
	            $str .= 'es';
	        }
	    } else {
	        $str .= 's';
	    }
	    return $str;
	}

	/**
	 * Takes multiple words separated by spaces or underscores and camelizes them
	 */	
	static function camelize($str) {		
		$str = 'x'.strtolower(trim($str));
		$str = ucwords(preg_replace('/[\s_]+/', ' ', $str));
		return substr(str_replace(' ', '', $str), 1);
	}

	/**
	 * Takes multiple words separated by spaces and underscores them.  Optional $quot also converts single quotes to _
	 */	
	static function underscore($str, $quot=false) {
		$pattern = ($quot) ? '/[\s\'"]+/' : '/[\s]+/';
		return preg_replace($pattern, '_', strtolower(trim($str)));
	}

	/**
	 * Takes multiple words separated by underscores and changes them to spaces
	 */	
	static function humanize($str) {
		return ucwords(preg_replace('/[_]+/', ' ', strtolower(trim($str))));
	}

	/**
	 * Converts underscores in as string back into spaces and '
	 */
	static function reconstitute($string, $uc = false) {
		$string = str_replace(array('_s_', '_'), array("'s ", ' '), $string);
		return ($uc) ? ucwords($string) : $string;
	}
}	
?>