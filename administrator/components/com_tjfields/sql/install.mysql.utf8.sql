CREATE TABLE IF NOT EXISTS `#__tjfields_fields` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`core` int(11) NOT NULL DEFAULT 0,
	`asset_id` int(11) NOT NULL DEFAULT 0,
	`label` varchar(255) NOT NULL DEFAULT '',
	`name` varchar(255) NOT NULL DEFAULT '',
	`title` varchar(255) NOT NULL DEFAULT '',
	`type` varchar(255) NOT NULL DEFAULT '',
	`state` tinyint(1) NOT NULL DEFAULT 0,
	`required` varchar(255) NOT NULL DEFAULT '',
	`readonly` int(11) NOT NULL DEFAULT 0,
	`created_by` int(11) NOT NULL DEFAULT 0,
	`description` text DEFAULT NULL,
	`js_function` text DEFAULT NULL,
	`validation_class` text DEFAULT NULL,
	`ordering` int(11) NOT NULL DEFAULT 0,
	`filterable` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 - For not filterable field. 1 for filterable field',
	`client` varchar(255) NOT NULL DEFAULT '',
	`group_id` int(11) NOT NULL DEFAULT 0,
	`showonlist` tinyint(1) NOT NULL DEFAULT 0,
	`params` text DEFAULT NULL,
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `#__tjfields_fields_value` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`field_id` int(11) NOT NULL COMMENT 'Field table ID' DEFAULT 0,
	`content_id` int(11) NOT NULL COMMENT 'client specific id' DEFAULT 0,
	`value` text DEFAULT NULL,
	`option_id` int(11) NOT NULL DEFAULT 0,
	`user_id` int(11) NOT NULL DEFAULT 0,
	`email_id` varchar(255) NOT NULL DEFAULT '',
	`client` varchar(255) NOT NULL COMMENT 'client(eg com_jticketing.event)' DEFAULT '',
	PRIMARY KEY (`id`),
	KEY `field_id` (`field_id`),
	KEY `content_id` (`content_id`)
) DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `#__tjfields_groups` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`ordering` int(11) NOT NULL DEFAULT 0,
	`asset_id` int(11) NOT NULL DEFAULT 0,
	`state` tinyint(1) NOT NULL DEFAULT 0,
	`created_by` int(11) NOT NULL DEFAULT 0,
	`name` varchar(255) NOT NULL DEFAULT '',
	`title` varchar(255) NOT NULL DEFAULT '',
	`client` varchar(255) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `#__tjfields_options` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`field_id` int(11) NOT NULL DEFAULT 0,
	`options` varchar(255) NOT NULL DEFAULT '',
	`value` varchar(255) NOT NULL DEFAULT '',
	`ordering` int(11) NOT NULL DEFAULT 0,
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `#__tjfields_category_mapping` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `field_id` INT(11) NOT NULL DEFAULT 0,
  `category_id` INT(11) NOT NULL COMMENT 'CATEGORY ID FROM JOOMLA CATEGORY TABLE FOR CLIENTS EG CLIENT=COM_QUICK2CART.PRODUCT' DEFAULT 0,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;
