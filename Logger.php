<?php
class Logger {
    protected $file;

    public function __construct($file) {
	if (FALSE === $fp = fopen($file, 'a+', TRUE)) {
	    throw new Exception(sprintf('Could not open file [%s] for writing.', $file));
	}

	$this->file = $fp;
    }

    public function debug($msg, $override = NULL) {
	if (defined('DEBUG') && DEBUG === TRUE) {
	    $this->writeLn((NULL === $override) ? 'DEBUG' : $override, $msg);
	}
    }
    
    public function debugQuery($q = NULL) {
        $this->debug($q, 'SQL');
        $this->debug(mysql_error());
    }

    public function error($msg) {
	$this->writeLn('ERROR', $msg);
    }

    public function fatal($msg) {
	$this->writeLn('FATAL', $msg);
    }

    public function info($msg) {
	$this->writeLn('INFO', $msg);
    }

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