<?php
/**
 * @fileoverview
 * @author Garth Henson (http://www.guahanweb.com)
 * @since 1.0
 * @version 1.0
 */

/**
 * Abstraction layer that allows for a singleton instance of a
 * database connection to be used across the queue
 *
 * @author Garth Henson (http://www.guahanweb.com)
 */
class SimpleQueueDB {
	static protected $user = '';
	static protected $pass = '';
	static protected $host = '';
	static protected $dbname = '';

	static protected $instance;
	static public $errors = array();

	/**
	 * Gets a singleton instance of the database connection
	 * 
	 * @static
	 * @access public
	 * @return SimpleQueueDB
	 */
	static public function getInstance() {
		if (NULL === self::$instance) {
			self::$instance = self::connect();
			if (FALSE === self::$instance) {
				$err = implode("\n\n", self::$errors);
				$msg = "Database error occurred. Turn on debugging to see detailed errors.\n\n";
				if (defined('DEBUG') && TRUE === DEBUG) {
					$msg .= $err;
				}

				throw new Exception($msg);
			}
		}

		return self::$instance;
	}

	/**
	 * Connects to the database with the currently defined credentials
	 *
	 * @static
	 * @access protected
	 * @return void
	 */
	static protected function connect() {
		if (FALSE === ($con = @mysql_connect(self::$host, self::$user, self::$pass, TRUE))) {
			self::$errors[] = @mysql_error();
		} elseif (FALSE === @mysql_select_db(self::$dbname, $con)) {
			self::$errors[] = @mysql_error();
		} else {
			return $con;
		}

		return FALSE;
	}
}
?>
