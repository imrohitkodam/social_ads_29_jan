-- Table structure for table `#__tj_consent`
CREATE TABLE IF NOT EXISTS `#__tj_consent` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `client` varchar(250) NOT NULL COMMENT 'component name.view name For e.g com_jgive.campaign',
  `client_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Joomla user id',
  `purpose` text NOT NULL COMMENT 'For what purpose has record this data',
  `accepted` tinyint(1) NOT NULL,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',

  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

