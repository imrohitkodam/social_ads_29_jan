--
-- Table structure for table `#__tj_houseKeeping`
--

CREATE TABLE IF NOT EXISTS `#__tj_houseKeeping` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT 'The descriptive title for the housekeeping task',
  `client` varchar(50) NOT NULL DEFAULT '' COMMENT 'Client extension name',
  `version` varchar(11) NOT NULL DEFAULT '' COMMENT 'Version for housekeeping task',
  `status` tinyint(3) NOT NULL DEFAULT 0,
  `lastExecutedOn` datetime DEFAULT NULL,
  `params` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `#__tj_media_files`
--

CREATE TABLE IF NOT EXISTS `#__tj_media_files` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(250) NOT NULL DEFAULT '',
  `type` varchar(250) NOT NULL DEFAULT '',
  `path` varchar(250) COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `state` tinyint(1) NOT NULL DEFAULT 0,
  `source` varchar(250) NOT NULL DEFAULT '',
  `original_filename` varchar(250) COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `size` int(11) NOT NULL DEFAULT 0,
  `storage` varchar(250) NOT NULL DEFAULT '',
  `created_by` int(11) NOT NULL DEFAULT 0,
  `access` tinyint(1) NOT NULL DEFAULT 0,
  `created_date` datetime DEFAULT NULL,
  `params` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

--
-- Table structure for table `#__tj_media_files_xref`
--

CREATE TABLE IF NOT EXISTS `#__tj_media_files_xref` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `media_id` int(11) NOT NULL DEFAULT 0,
  `client_id` int(11) NOT NULL DEFAULT 0,
  `client` varchar(250) NOT NULL DEFAULT '',
  `is_gallery` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;
