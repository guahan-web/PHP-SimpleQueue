<?php
class Logger {
	protected $file;

	/**
	 * Constructor attempts to open the connection to the log file
	 *	
	 * @access public
	 * @param String $file The name of the file to which to write logs
	 * @return Logger
	 */
	public function __construct($file) {
		if (FALSE === $fp = fopen($file, 'a+', TRUE)) {
			throw new Exception(sprintf('Could not open file [%s] for writing.', $file));
		}

		$this->file = $fp;
	}

	/**
	 * Writes a DEBUG entry to the log if DEBUG has been set
	 *
	 * @access public
	 * @param String $msg The log message
	 * @param String $override If provided, overrides the "DEBUG" tag on the log
	 * @return void
	 */
	public function debug($msg, $override = NULL) {
		if (defined('DEBUG') && DEBUG === TRUE) {
			$this->writeLn((NULL === $override) ? 'DEBUG' : $override, $msg);
		}
	}

	/**
	 * Sets a debug entry for the latest MySql error
	 * 
	 * @access public
	 * @param String $q The SQL that failed
	 * @return void
	 */
	public function debugQuery($q = NULL) {
		$this->debug($q, 'SQL');
		$this->debug(mysql_error());
	}

	/**
	 * Creates a ERROR entry in the log file
	 *
	 * @access public
	 * @param String $msg The log entry
	 * @return void
	 */
	public function error($msg) {
		$this->writeLn('ERROR', $msg);
	}

	/**
	 * Creates a FATAL entry in the log file
	 *
	 * @access public
	 * @param String $msg The log entry
	 * @return void
	 */
	public function fatal($msg) {
		$this->writeLn('FATAL', $msg);
	}

	/**
	 * Creates an INFO entry in the log file
	 *
	 * @access public
	 * @param String $msg The log entry
	 * @return void
	 */
	public function info($msg) {
		$this->writeLn('INFO', $msg);
	}

	/**
	 * Writes a line to the log file
	 *
	 * @access protected
	 * @param String $type The type of log entry
	 * @param String $msg The log entry
	 * @return void
	 */
	protected function writeLn($type, $msg) {
		if (NULL === $this->file) {
			throw new Exception('QueueLogger not properly initialized. Cannot write line to log file.');
		}

		$msg = sprintf("%s [%s] %s\n", date('Y-m-d H:i:s'), $type, $msg);
		if (FALSE === fwrite($this->file, $msg)) {
			throw new Exception(sprintf('QueueLogger could not write line to file. LINE: [%s]', $msg));
		}

		return TRUE;
	}
}
?>
