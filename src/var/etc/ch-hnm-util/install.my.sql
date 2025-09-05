CREATE TABLE `expl_page_link` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`type` VARCHAR(255) NULL DEFAULT NULL,
	`linked_page_id` INT(10) UNSIGNED NULL DEFAULT NULL,
	`url` VARCHAR(1023) NULL DEFAULT NULL,
	`show_explicit` VARCHAR(255) NULL DEFAULT NULL,
	`label` VARCHAR(255) NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	INDEX `expl_page_link_index_1` (`linked_page_id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=2
;