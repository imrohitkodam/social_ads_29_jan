ALTER TABLE `#__tj_consent` ENGINE = InnoDB;

ALTER TABLE `#__tj_consent` CHANGE `purpose` `purpose` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `#__tj_consent` CHANGE `client` `client` VARCHAR(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
