ALTER TABLE `page_content_t`
	CHANGE COLUMN `se_description` `se_description` VARCHAR(500) NULL DEFAULT NULL AFTER `se_title`;
	
ALTER TABLE `page_content_t`
	CHANGE COLUMN `se_title` `se_title` VARCHAR(255) NULL DEFAULT NULL AFTER `n2n_locale`,
	CHANGE COLUMN `se_keywords` `se_keywords` VARCHAR(255) NULL DEFAULT NULL AFTER `se_description`;
