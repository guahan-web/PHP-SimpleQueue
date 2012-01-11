<?php
class QueueLogger extends Logger {
    private static $instance;
    private $count = 0;
    private $filename = 'SimpleQueue.log';

    public static function getInstance($path = NULL) {
	if (!isset(self::$instance)) {
	    $className = __CLASS__;
	    self::$instance = new $className($path);
	}
	return self::$instance;
    }

    public function __construct($path) {
	$this->path = is_dir($path) ? $path : (defined('LOG_PATH') ? LOG_PATH : '.');
	$this->path = rtrim($this->path, '/');
	$file = $this->path . '/' . $this->filename;
	parent::__construct($file);
    }
}
?>