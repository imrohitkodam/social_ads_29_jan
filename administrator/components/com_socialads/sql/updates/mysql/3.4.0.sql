CREATE INDEX `user_id_idx` ON `#__ad_wallet_transc` (`user_id`);
CREATE INDEX `ad_id_idx` ON `#__ad_stats` (`ad_id`);
ALTER TABLE `#__ad_wallet_transc` CHANGE `spent` `spent` DECIMAL(16,5) NOT NULL;
ALTER TABLE `#__ad_wallet_transc` CHANGE `earn` `earn` DECIMAL(16,5) NOT NULL;
ALTER TABLE `#__ad_wallet_transc` CHANGE `balance` `balance` DECIMAL(16,5) NOT NULL;
UPDATE `#__menu` SET link = 'index.php?option=com_users&view=login' where link = 'index.php?option=com_socialads&view=registration';
