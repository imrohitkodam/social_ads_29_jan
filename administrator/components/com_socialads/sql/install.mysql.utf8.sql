CREATE TABLE IF NOT EXISTS `#__ad_archive_stats` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`ad_id` INT(11)  NOT NULL DEFAULT 0 COMMENT 'FK to #__ad_data',
`date` DATETIME DEFAULT NULL COMMENT 'Record date',
`impression` INT(11)  NOT NULL DEFAULT 0 COMMENT 'Count of impressions from #__ad_stats selected days',
`click` INT(11)  NOT NULL DEFAULT 0 COMMENT 'Count of clicks from #__ad_stats selected days',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ad_campaign` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`ordering` INT(11)  NOT NULL DEFAULT 0,
`state` TINYINT(1)  NOT NULL DEFAULT 0,
`checked_out` INT(11)  NOT NULL DEFAULT 0,
`checked_out_time` DATETIME DEFAULT NULL,
`start_date` DATE DEFAULT NULL,
`end_date` DATE DEFAULT NULL,
`created_by` INT(11)  NOT NULL DEFAULT 0,
`campaign` VARCHAR(255)  NOT NULL DEFAULT '' COMMENT 'Name of campaign',
`daily_budget` INT(11)  NOT NULL DEFAULT 0 COMMENT 'Daily budget assigned for campaign',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ad_wallet_transc` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`time` DOUBLE(15,2) NOT NULL DEFAULT 0 COMMENT 'Time at which transaction made',
`user_id` INT(11)  NOT NULL DEFAULT 0 COMMENT 'userid who added a money in wallet',
`spent` DECIMAL(16,5)  NOT NULL DEFAULT 0 COMMENT 'Amount debited from users wallet',
`earn` DECIMAL(16,5)  NOT NULL DEFAULT 0 COMMENT 'Amount credited to users wallet',
`balance` DECIMAL(16,5)  NOT NULL DEFAULT 0 COMMENT 'Remaining balance in users wallet',
`type` VARCHAR(255)  NOT NULL DEFAULT '' COMMENT 'Type of transaction O is transaction for adding money in a wallet, migrate to wallet mode to pay per ad mode and vice versa and C is click and impression deduction from wallet',
`type_id` INT(11) NULL COMMENT 'Order Id or a campaign Id',
`comment` VARCHAR(50) NULL COMMENT 'Lanuage constant of a comment for a transaction type',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ad_contextual_target` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`ad_id` INT(11)  NOT NULL DEFAULT 0 COMMENT 'FK to #__ad_data',
`keywords` TEXT DEFAULT NULL COMMENT 'Meta keywords for contextual targeting',
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ad_contextual_terms` (
`link_id` INT(10)  NOT NULL DEFAULT 0,
`term_id` INT(10)  NOT NULL DEFAULT 0,
`weight` FLOAT NOT NULL DEFAULT 0,
`term` VARCHAR(75)  NOT NULL DEFAULT '',
`indexdate` DATE DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ad_coupon` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`ordering` INT(11)  NOT NULL DEFAULT 0,
`state` TINYINT(1)  NOT NULL DEFAULT 0,
`checked_out` INT(11)  NOT NULL DEFAULT 0,
`checked_out_time` DATETIME DEFAULT NULL,
`created_by` INT(11)  NOT NULL DEFAULT 0,
`name` VARCHAR(100)  NOT NULL DEFAULT '' COMMENT 'Coupon name',
`code` VARCHAR(100)  NOT NULL DEFAULT '' COMMENT 'Unique code for coupon',
`value` INT(11)  NOT NULL DEFAULT 0 COMMENT 'Amount given for coupon',
`val_type` TINYINT(4)  NOT NULL DEFAULT 0 COMMENT '0 - coupon applied for flat discount, 1 - coupon applied on percentage discount',
`max_use` INT(11)  NOT NULL DEFAULT 0 COMMENT 'Max number of coupon usage',
`max_per_user` INT(11)  NOT NULL DEFAULT 0 COMMENT 'Max number of time one user can use single coupon',
`description` TEXT DEFAULT NULL COMMENT 'Coupon description',
`params` TEXT DEFAULT NULL COMMENT 'For extra details',
`from_date` DATETIME DEFAULT NULL COMMENT 'Coupon valid date',
`exp_date` DATETIME DEFAULT NULL COMMENT 'Coupon expires on',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ad_data` (
`ad_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`ordering` INT(11)  NOT NULL DEFAULT 0,
`state` TINYINT(1)  NOT NULL DEFAULT 0,
`checked_out` INT(11)  NOT NULL DEFAULT 0,
`checked_out_time` DATETIME DEFAULT NULL,
`created_by` INT(11)  NOT NULL DEFAULT 0,
`ad_url1` MEDIUMTEXT DEFAULT NULL COMMENT 'User want to use http or https for a ad link',
`ad_url2` MEDIUMTEXT DEFAULT NULL COMMENT 'After clicking on ad on which page advertiser wants to link',
`ad_title` VARCHAR(100)  NOT NULL DEFAULT '' COMMENT 'Title of a ad',
`ad_body` MEDIUMTEXT DEFAULT NULL COMMENT 'Content of a ad',
`ad_image` VARCHAR(200)  NOT NULL DEFAULT '' COMMENT 'Image for a ad',
`display_ad_on` VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'This field specify display the ad on the device',
`ad_startdate` DATE DEFAULT NULL COMMENT 'Date on which user wants to start ad displaying',
`ad_enddate` DATE DEFAULT NULL COMMENT 'Date on which user want to stop ad displaying',
`ad_noexpiry` TINYINT(2)  NOT NULL DEFAULT 0 COMMENT 'Unlimited ads',
`ad_payment_type` TINYINT(2)  NOT NULL DEFAULT 0 COMMENT 'Payment type selected  for ad',
`ad_credits` INT(10)  NOT NULL DEFAULT 0 COMMENT 'Number of credits avilable for a ad.',
`ad_credits_balance` INT(10)  NOT NULL DEFAULT 0 COMMENT 'Number of credits remaining for a ad',
`ad_created_date` DATETIME DEFAULT NULL COMMENT 'Ad creation date.',
`ad_modified_date` DATETIME DEFAULT NULL COMMENT 'Ad modification date.',
`ad_approved` TINYINT(4)  NOT NULL DEFAULT 0 COMMENT 'Payment of ad is done or not',
`ad_alternative` TINYINT(4)  NOT NULL DEFAULT 0 COMMENT 'If no ad is matching. show alternative ad',
`ad_guest` TINYINT(4)  NOT NULL DEFAULT 0 COMMENT 'Show ad to a guest user',
`ad_affiliate` TINYINT(4)  NOT NULL DEFAULT 0 COMMENT 'Ad created from google adsence',
`ad_zone` INT(11)  NOT NULL DEFAULT 0 COMMENT 'Ad created in this zone',
`layout` VARCHAR(200)  NOT NULL COMMENT 'Zone layout selcted for this ad',
`camp_id` INT(11)  NOT NULL DEFAULT 0 COMMENT 'If Ad wallet mode then campaign id from a wallet',
`bid_value` DOUBLE(11,2)  NOT NULL DEFAULT 0 COMMENT 'Bid value for charging the ad',
`clicks` float NOT NULL DEFAULT 0 COMMENT 'for number of clicks of perticular ad',
`impressions` float NOT NULL DEFAULT 0 COMMENT 'For number of impressions of perticular ad',
`pay_initial_fee` TINYINT(2)  NOT NULL DEFAULT 0 COMMENT 'Need to pay initial payment for Ad placement',
`pay_initial_fee_amout` INT(11)  NOT NULL DEFAULT 0 COMMENT 'Initial fee for placing the Ad',
`params` text DEFAULT NULL COMMENT 'To save additional information against ad',
PRIMARY KEY (`ad_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ad_fields_mapping` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`mapping_id` INT(11)  NOT NULL DEFAULT 0,
`mapping_fieldid` INT(11)  NOT NULL DEFAULT 0 COMMENT 'Mapping field id from social sites',
`mapping_fieldtype` VARCHAR(50)  NOT NULL DEFAULT '' COMMENT 'Field type like date text radio button',
`mapping_label` VARCHAR(100)  NOT NULL DEFAULT '' COMMENT 'Label for a field',
`mapping_fieldname` VARCHAR(200)  NOT NULL DEFAULT '' COMMENT 'Name of a mapping field',
`mapping_options` TEXT DEFAULT NULL,
`mapping_category` INT(11)  NOT NULL DEFAULT 0,
`mapping_publish` TINYINT(4)  NOT NULL DEFAULT 0,
`mapping_check` TINYINT(4)  NOT NULL DEFAULT 0,
`mapping_match` TINYINT(4)  NOT NULL DEFAULT 0,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ad_geo_target` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`ad_id` INT(11)  NOT NULL DEFAULT 0 COMMENT 'FK to #__ad_data',
`country` TEXT DEFAULT NULL COMMENT 'Country selected for a geo targeting',
`region` TEXT DEFAULT NULL COMMENT 'Region selected for a geo targeting',
`city` TEXT DEFAULT NULL COMMENT 'City selected for a geo targeting',
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ad_ignore` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`adid` INT(11)  NOT NULL DEFAULT 0 COMMENT 'FK to #__ad_data',
`userid` INT(11)  NOT NULL DEFAULT 0 COMMENT 'User who ignore a ad',
`ad_feedback` TEXT DEFAULT NULL COMMENT 'User seleced feedback option to ignore a ad',
`idate` TIMESTAMP NULL COMMENT 'Date on which ad is ignored',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ad_orders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `prefix_oid` VARCHAR(23) NOT NULL DEFAULT '',
  `cdate` datetime DEFAULT NULL COMMENT 'Order creation date',
  `mdate` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Order modification date',
  `payment_info_id` int(11) NOT NULL DEFAULT 0 COMMENT 'Payment id',
  `transaction_id` varchar(100) NOT NULL DEFAULT '' COMMENT 'Payment transaction id',
  `payee_id` varchar(100) NOT NULL DEFAULT '' COMMENT 'User who did a payment',
  `amount` float NOT NULL  DEFAULT 0 COMMENT 'Amount of a payment',
  `status` varchar(100) NOT NULL DEFAULT '' COMMENT 'Payment status like confirmed pending, etc',
  `extras` text DEFAULT NULL COMMENT 'Fileds like url from which payment is did, order id, payment status, payment value etc',
  `processor` varchar(100) NOT NULL DEFAULT '' COMMENT 'Payment gateway',
  `ip_address` varchar(100) NOT NULL DEFAULT '' COMMENT 'Ip address from which payment is did',
  `comment` varchar(255) NOT NULL DEFAULT '' COMMENT 'Comment added by user while doing payment',
  `original_amount` float NOT NULL  DEFAULT 0 COMMENT 'Amount needs to paid by a user',
  `coupon` varchar(100) NOT NULL DEFAULT '' COMMENT 'Coupon Id',
  `tax` float(10,2) NOT NULL  DEFAULT 0 COMMENT 'Tax if applied',
  `tax_details` text DEFAULT NULL COMMENT 'Infromation about a tax',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ad_payment_info` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT 'FK to #__ad_orders',
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Recurring payment',
  `ad_id` int(11) NOT NULL DEFAULT 0 COMMENT 'FK to #__ad_data',
  `recurring_frequency` varchar(100) DEFAULT NULL COMMENT 'Duration of payment like daily, monthly, etc',
  `recurring_count` int(11) DEFAULT 0 COMMENT 'How many times payment will br done',
  `subscr_id` varchar(100) DEFAULT NULL COMMENT 'Subscription ID of a user',
  `ad_credits_qty` int(11) NOT NULL DEFAULT 0 COMMENT 'COunt of ad credits',
  `comment` text DEFAULT NULL COMMENT 'Comment added for ad payment',
  `cdate` datetime DEFAULT NULL COMMENT 'Payment date',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ad_stats` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`ad_id` INT(11)  NOT NULL DEFAULT 0 COMMENT 'FK to #__ad_data',
`user_id` INT(11)  NOT NULL DEFAULT 0 COMMENT 'FK to #__ad_users',
`display_type` TINYINT(4)  NOT NULL DEFAULT 0 COMMENT 'Impression - 0 or Click - 1',
`time` TIMESTAMP NOT NULL COMMENT 'Time on which click or impression is done',
`ip_address` VARCHAR(100)  NOT NULL DEFAULT '' COMMENT 'IP address of a machine from where click or impression is done',
`spent` DECIMAL(11,2)  NOT NULL DEFAULT 0 COMMENT 'Advertisee spent how much time on ad',
`referer` VARCHAR(150)  NOT NULL DEFAULT '' COMMENT 'Site name where ad is displayed',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ad_users` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`orderid` INT(11)  NOT NULL DEFAULT 0 COMMENT 'FK to #__ad_orders',
`user_id` INT(11)  NOT NULL DEFAULT 0 COMMENT 'User id in a joomla users table',
`ad_id` INT(11) NOT NULL DEFAULT 0 COMMENT 'ad table foreign key',
`user_email` VARCHAR(255)  NOT NULL DEFAULT '' COMMENT 'Email ID of user',
`firstname` VARCHAR(250)  NOT NULL DEFAULT '' COMMENT 'First name of user',
`lastname` VARCHAR(250)  NOT NULL DEFAULT '' COMMENT 'Last name of user',
`vat_number` VARCHAR(250)  NOT NULL DEFAULT '' COMMENT 'vat number of user',
`tax_exempt` TINYINT(4)  NOT NULL DEFAULT 0 ,
`country_code` VARCHAR(51)  NOT NULL DEFAULT '' COMMENT 'Country code of user',
`address` VARCHAR(255)  NOT NULL DEFAULT '' COMMENT 'Address of user',
`city` VARCHAR(50)  NOT NULL DEFAULT '' COMMENT 'City of user',
`state_code` VARCHAR(50)  NOT NULL DEFAULT '' COMMENT 'State code of user',
`zipcode` VARCHAR(255)  NOT NULL DEFAULT '' COMMENT 'Zip code of user',
`phone` VARCHAR(50)  NOT NULL DEFAULT '' COMMENT 'Phone number of user',
`approved` TINYINT(1)  NOT NULL DEFAULT 0 COMMENT 'Users state',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ad_zone` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`ordering` INT(11)  NOT NULL DEFAULT 0 ,
`state` TINYINT(1)  NOT NULL DEFAULT 0 ,
`checked_out` INT(11)  NOT NULL DEFAULT 0 ,
`checked_out_time` DATETIME DEFAULT NULL,
`zone_name` VARCHAR(100) NOT NULL DEFAULT '',
`orientation` TINYINT(2) NOT NULL DEFAULT 0 COMMENT 'Orientation for a specific zone Horizontal or Vertical',
`ad_type` VARCHAR(100)  NOT NULL DEFAULT '' COMMENT 'Type of ad text media or text and media and if zone supports affliate ad',
`max_title` INT(11)  NOT NULL DEFAULT 0 COMMENT 'Maximum letters in ad title',
`max_des` INT(11)  NOT NULL DEFAULT 0 COMMENT 'Maximum letter in description of ad',
`img_width` INT(11)  NOT NULL DEFAULT 0 COMMENT 'Width of ad image',
`img_height` INT(11)  NOT NULL DEFAULT 0 COMMENT 'Height of ad image',
`use_image_ratio` TINYINT(1)  NOT NULL DEFAULT 0 ,
`img_width_ratio` INT(11)  NOT NULL DEFAULT 0 COMMENT 'Width ratio of ad image',
`img_height_ratio` INT(11)  NOT NULL DEFAULT 0 COMMENT 'Height ratio of ad image',
`per_click` float NOT NULL DEFAULT 0 COMMENT 'Rate for per click',
`per_imp` float NOT NULL DEFAULT 0 COMMENT 'Rate for per impression',
`per_day` float NOT NULL DEFAULT 0 COMMENT 'Rate for per day',
`num_ads` INT(11)  NOT NULL DEFAULT 0 COMMENT 'Number of ads in zone',
`layout` VARCHAR(250)  NOT NULL DEFAULT '' COMMENT 'Layout selected for ad',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
