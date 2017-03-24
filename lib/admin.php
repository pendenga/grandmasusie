<?php

class Admin {
	protected $famu;

	function __construct(&$famu) {
		if ($famu->signin_id != 1) {
			die();
		}
	}

}

?>