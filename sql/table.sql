CREATE TABLE IF NOT EXISTS simple_queue (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`queued` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`context` VARCHAR(200) DEFAULT NULL,
	`request` MEDIUMTEXT NOT NULL, -- JSON encoded data
	`attempts` TINYINT UNSIGNED DEFAULT 0,
	PRIMARY KEY (`id`),
	INDEX `idx_queued` (`queued`),
	INDEX `idx_context` (`context`),
	INDEX `idx_context_queued` (`context`, `queued`)
);

