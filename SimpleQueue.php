<?php
/**
 * @fileoverview
 * This file defines the logic of the SimpleQueue object
 * 
 * @author Garth Henson (http://www.guahanweb.com)
 * @since 1.0
 * @version 1.0
 */

require('SimpleQueueDB.php');
require('QueueLogger.php');

/**
 * SimpleQueue
 *
 * @author Garth Henson (http://www.guahawneb.com)
 */
class SimpleQueue {
    protected $limit   = 0;
    protected $retries = 3;
    protected $delay   = 15;
    protected $table   = 'simple_queue';
    protected $callbacks = array();

    protected $logger;
    protected $dequeue_time;

    public function __construct() {
	$this->logger = QueueLogger::getInstance();
    }

    public function setExecLimit($n) {
        $this->limit = intval($n);
	$this->logger->debug(sprintf('Execution limit set: %d', $n));
    }

    public function setRetryLimit($n) {
	$this->retries = intval($n);
	$this->logger->debug(sprintf('Retry limit set: %d', $n));
    }

    public function setRetryDelay($n) {
	$this->delay = intval($n);
	$this->logger->debug(sprintf('Retry delay set: %d minutes', $n));
    }
    
    public function setCallbackAction($fn, $context = 'root') {
        $this->callbacks[$context] = $fn;
        $this->logger->debug(sprintf('Setting callback for context [%s] to [%s]', $context, $fn));
    }
    
    public function queue($data, $context = NULL) {
        $this->logger->info('QUEUING...');
        $q = sprintf("INSERT INTO %s (context, request) VALUES (%s, '%s')",
            $this->table,
            is_null($context) ? 'NULL' : "'" . mysql_real_escape_string($context) . "'",
            mysql_real_escape_string($data)
        );
        
        if (FALSE !== ($sql = mysql_query($q))) {
            $this->logger->info(sprintf('Successfully queued data: [%s] with ID [%d]',
                $data,
                mysql_insert_id()
            ));
            return TRUE;
        } else {
            $this->logger->debugQuery($q);
            $this->logger->error(sprintf('Failed to queue data: [%s]', $data));
        }
    }
    
    public function dequeue($context = NULL) {
        $this->logger->info('DEQUEUING...');
	$q = sprintf("SELECT id, queued, context, request, attempts, CURRENT_TIMESTAMP as ctime, CURRENT_TIMESTAMP + INTERVAL %d minute AS ntime FROM %s WHERE queued < CURRENT_TIMESTAMP AND %s ORDER BY queued",
            $this->delay,
            $this->table,
            (NULL === $context) ? 'context IS NULL' : sprintf("context = '%s'", mysql_real_escape_string($context))
        );
	if ($this->limit !== 0) {
	    $q .= sprintf(" LIMIT %d", $this->limit);
	}
        
        if (FALSE === ($sql = mysql_query($q))) {
            $this->logger->debugQuery($q);
            $this->logger->error(sprintf('Could not retrieve queue for context [%s]', $context));
            return FALSE;
        }
        
        $context = NULL === $context ? 'root' : $context;
        if (!isset($this->callbacks[$context])) {
            $this->logger->fatal(sprintf('Cannot process context [%s] because there is no callback defined', $context));
            return FALSE;
        }
        
        $c = mysql_num_rows($sql);
        if ($c > 0) {
            $this->logger->debug(sprintf('Found %d items to dequeue', $c));
            while ($item = mysql_fetch_assoc($sql)) {
                $this->logger->debug(sprintf('Calling [%s] on data [%s]', $this->callbacks[$context], $item['request']));
                
                // Process the user callback on each item and remove from the queue if successful
                if (TRUE === call_user_func($this->callbacks[$context], $item['request'])) {
                    $this->logger->info(sprintf('Successfully dequeued request [%d::%s].', $item['id'], $item['request']));
                    $this->deleteItem($item['id']);
                } else {
                    $this->logger->info(sprintf('Callback process failed. Requeuing request [%d::%s]', $item['id'], $item['request']));
                    $this->requeue($item);
                }
            }
        } else {
            $this->logger->debug(sprintf('No items to dequeue'));
        }
    }

    public function purgeQueue() {
	$q = sprintf("DELETE FROM %s WHERE attempts >= %d", $this->table, $this->retries);
	if (FALSE === ($sql = @mysql_query($q))) {
	    $this->logger->debugQuery($q);
	    $this->logger->error('Could not purge queue');
	} else {
	    $this->logger->info(sprintf('Queue purged successfully: deleted %d expired items', mysql_affected_rows()));
	}
    }
    
    protected function requeue($item) {
        $attempts = intval($item['attempts']) + 1;
        $q = sprintf("UPDATE %s SET queued = CURRENT_TIMESTAMP + INTERVAL %d MINUTE, attempts = %d WHERE id = %d LIMIT 1",
            $this->table,
            $this->delay,
            $attempts,
            intval($item['id'])
        );
        
        if (FALSE === @mysql_query($q)) {
            $this->logger->debugQuery($q);
            $this->logger->error(sprintf('Could not requeue item ID [%d]', $item['id']));
        } else {
            $this->logger->debug(sprintf('Item [%d] requeued successfully. Item has been attempted %d time(s)', $item['id'], $attempts));
        }
    }
    
    protected function deleteItem($id) {
        $q = sprintf("DELETE FROM %s WHERE id = %d LIMIT 1", $this->table, intval($id));
        if (FALSE === mysql_query($q)) {
            $this->logger->debugQuery($q);
            $this->logger->error(sprintf("Could not delete queue ID [%d]", intval($id)));
        } else {
            $this->logger->debug(sprintf('Successfully deleted ID [%d] from the queue', intval($id)));
        }
    }
}
?>
