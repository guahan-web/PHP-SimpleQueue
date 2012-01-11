<?php
class SimpleQueueDB {
	static protected $user = '';
	static protected $pass = '';
	static protected $host = '';
	static protected $dbname = '';

	static protected $instance;
	static public $errors = array();

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
