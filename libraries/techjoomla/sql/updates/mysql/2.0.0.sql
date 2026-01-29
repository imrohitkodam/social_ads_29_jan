ALTER TABLE `#__tj_houseKeeping` CHANGE `title` `title` varchar(100) NOT NULL DEFAULT '' COMMENT 'The descriptive title for the housekeeping task';
ALTER TABLE `#__tj_houseKeeping` CHANGE `client` `client` varchar(50) NOT NULL DEFAULT '' COMMENT 'Client extension name';
ALTER TABLE `#__tj_houseKeeping` CHANGE `version` `version` varchar(11) NOT NULL DEFAULT '' COMMENT 'Version for housekeeping task';
ALTER TABLE `#__tj_houseKeeping` CHANGE `status` `status` tinyint(3) NOT NULL DEFAULT 0;
ALTER TABLE `#__tj_houseKeeping` CHANGE `lastExecutedOn` `lastExecutedOn` datetime DEFAULT NULL;
ALTER TABLE `#__tj_houseKeeping` CHANGE `params` `params` text DEFAULT NULL;

ALTER TABLE `#__tj_media_files` CHANGE `title` `title` varchar(250) NOT NULL DEFAULT '';
ALTER TABLE `#__tj_media_files` CHANGE `type` `type` varchar(250) NOT NULL DEFAULT '';
ALTER TABLE `#__tj_media_files` CHANGE `path` `path` varchar(250) COLLATE utf8mb4_bin NOT NULL DEFAULT '';
ALTER TABLE `#__tj_media_files` CHANGE `state` `state` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `#__tj_media_files` CHANGE `source` `source` varchar(250) NOT NULL DEFAULT '';
ALTER TABLE `#__tj_media_files` CHANGE `original_filename` `original_filename` varchar(250) COLLATE utf8mb4_bin NOT NULL DEFAULT '';
ALTER TABLE `#__tj_media_files` CHANGE `size` `size` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `#__tj_media_files` CHANGE `storage` `storage` varchar(250) NOT NULL DEFAULT '';
ALTER TABLE `#__tj_media_files` CHANGE `created_by` `created_by` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `#__tj_media_files` CHANGE `access` `access` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `#__tj_media_files` CHANGE `created_date` `created_date` datetime DEFAULT NULL;
ALTER TABLE `#__tj_media_files` CHANGE `params` `params` text DEFAULT NULL;

ALTER TABLE `#__tj_media_files_xref` CHANGE `media_id` `media_id` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `#__tj_media_files_xref` CHANGE `client_id` `client_id` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `#__tj_media_files_xref` CHANGE `client` `client` varchar(250) NOT NULL DEFAULT '';
ALTER TABLE `#__tj_media_files_xref` CHANGE `is_gallery` `is_gallery` tinyint(1) NOT NULL DEFAULT 0;
