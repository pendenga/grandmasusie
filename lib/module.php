<?php

include_once 'dbobject.php';

class Module {
	protected $db;
	protected $famu;
	protected $tmpl;
	protected $site = array();

	function __construct(DBObject &$db, SiteUser &$famu, ModuleDisplay &$template) {
		$this->db = $db;
		$this->famu = $famu;
		$this->tmpl = $template;
	}

	function ajaxPage($title, $module) {

	}


	/**
	 * Override standard email function to come from Site Registration
	 */
	protected function sendEmail($to, $subject, $body) {
		$headers = "MIME-Version: 1.0\r\n";
		$headers .= "Content-type: text/plain; charset=iso-8859-1\r\n";
		$headers .= "From: {$this->site['site_name']} administration <{$this->config['admin_email']}>\r\n";
		$headers .= "Bcc: pendenga@gmail.com\r\n";
		return mail($to, $subject, $body, $headers);
	}
}

interface ModuleDisplay {
	function pageHeader();
	function pageFooter();
}


?>