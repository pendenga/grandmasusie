<?php

include_once '../lib/admin.php';
include_once '../lib/module.php';

class AdminForm extends Module {
	protected $admin = false;

	function __singleton() {
		if ($this->admin===false) {
			$this->admin = new Admin($this->famu);
		}
	}
}

?>