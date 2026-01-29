ALTER TABLE `#__tjfields_fields` MODIFY `description` TEXT DEFAULT NULL;
ALTER TABLE `#__tjfields_fields_value` ADD INDEX(`field_id`);
ALTER TABLE `#__tjfields_fields_value` ADD INDEX(`content_id`);
ALTER TABLE `#__tjfields_options` ADD `ordering` INT(11) NOT NULL DEFAULT 0 AFTER `value`;
DROP TABLE IF EXISTS `#__tjfields_client_type`;
