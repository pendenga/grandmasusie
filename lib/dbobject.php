<?php

include 'gtools.php';

/**
 * Database Connect Exception
 *
 * @package Pendenga
 */
class Database_ConnectException extends Exception {}

/**
 * Database Query Exception
 *
 * @package Pendenga
 */
class Database_QueryException extends Exception {}

/**
 * Database object, all connections to the database and queries are run through
 * this object.
 *
 * @package	Pendenga
 * @author	Grant Anderson <grant@pendenga.com>
 */
class DBObject {
    const CONFIG_FILE = '/../conf/dbconfig.xml';

    protected $dbh;
	protected $db_server;
	protected $db_name;
	protected $db_user;
	protected $db_password;
	protected $db_errors;
	protected $db_log;

	/**
	 * Use configOverride to override specific parameters in the $this->config array.
	 * Use prod_id to specify a different set of parameters in /conf/dbconfig.xml.
	 */
	function __construct(array $configOverride=array(), $prod_id='production') {
        $xml = simplexml_load_file(realpath(dirname(__FILE__) . self::CONFIG_FILE));
        $this->db_server = trim($xml->production->hostname);
        $this->db_name = trim($xml->production->database);
        $this->db_user = trim($xml->production->username);
        $this->db_password = trim($xml->production->password);
        $this->db_errors = trim($xml->production->dberrors);
        $this->db_log = trim($xml->production->logfile);

        // set debug output
		if ($this->db_errors=='off') {
			$oldval = ini_set("display_errors", "off");
		}

		// MySQL 5 connect
		$this->connect();

		// reset debug level
		if (!$this->db_errors) {
			ini_set("display_errors", $oldval);
		}
	}

	private function connect() {
		$ts_start = microtime(true);
		$this->dbh = new mysqli($this->db_server, $this->db_user, $this->db_password, $this->db_name);
		if (mysqli_connect_errno()) {
			throw new Database_ConnectException("Could not sign in: ".mysqli_connect_error());
		}
		self::logDB("database connect", $ts_start);
	}

	/**
	 * Perform the query operation, then format the result in an associative
	 *  array if the operation is expectint a result.  Otherwise return true.
	 *
	 * Sybase: the sybase_query function returns true if no results are found,
	 *  false if query failed, and result set if rows were returned.
	 */
	function do_sql($query) {
		$this->logDB($query);

		// Check connection
		if (!isset($this->dbh)) {
			$this->connect();
		}

		// MySQL 5 query
		if ($this->dbh->multi_query($query) === false) {
			throw new Database_QueryException("Query Error (mysqli_multi_query): ".$this->dbh->error.". Attempted Query: {$query}");
		} else {
			$return_array = array();
			do {
				if ($result = $this->dbh->store_result()) {
					while ($row = $result->fetch_assoc()) {
						$return_array[] = $row;
					}
					$result->close();
				}
			} while ($this->dbh->next_result());

			// no results, but rows affected = insert or update
			if (count($return_array)==0 && $this->dbh->field_count>0) {
				return array();
			} else {
				return $return_array;
			}
		}
	}

	protected function escape_string($escapestr) {
		if (!isset($this->dbh)) {
			$this->connect();
		}
		return $this->dbh->real_escape_string($escapestr);
	}

	function logDB($output) {
		if (is_writable($this->db_log)) {
			$fh = fopen($this->db_log, "a");
			fwrite($fh,sprintf("%s %s\n", date('Ymd H:i:s'), $output));
			fclose($fh);
		}
	}
}

?>
