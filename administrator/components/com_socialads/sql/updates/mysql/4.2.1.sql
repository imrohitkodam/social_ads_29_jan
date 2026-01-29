ALTER TABLE `#__ad_campaign` add column `start_date` DATE DEFAULT NULL;
ALTER TABLE `#__ad_campaign` add column `end_date` DATE DEFAULT NULL;
ALTER TABLE `#__ad_zone` add column  `use_image_ratio` TINYINT(1)  NOT NULL DEFAULT 0;
ALTER TABLE `#__ad_zone` add column `img_width_ratio` INT(11)  NOT NULL DEFAULT 0 COMMENT 'Width ratio of ad image';
ALTER TABLE `#__ad_zone` add column `img_height_ratio` INT(11)  NOT NULL DEFAULT 0 COMMENT 'Height ratio of ad image';
