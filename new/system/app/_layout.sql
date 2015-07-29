-- Adminer 3.5.1 MySQL dump

SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = 'SYSTEM';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `calendar`;
CREATE TABLE `calendar` (
  `item_id` int(11) NOT NULL,
  `item_page` varchar(50) COLLATE utf8_bin NOT NULL,
  `from` datetime NOT NULL,
  `to` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `from_to` (`from`,`to`),
  KEY `item_page` (`item_page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `item`;
CREATE TABLE `item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page` varchar(250) COLLATE utf8_bin NOT NULL COMMENT 'url for showing',
  `posted` datetime NOT NULL COMMENT 'date and time when it was posted',
  `sortable1` varchar(255) COLLATE utf8_bin DEFAULT NULL COMMENT '1st column for sorting',
  `sortable2` varchar(255) COLLATE utf8_bin DEFAULT NULL COMMENT '2st column for sorting',
  `sortable3` varchar(255) COLLATE utf8_bin DEFAULT NULL COMMENT '3st column for sorting',
  `authorEmail` varchar(250) COLLATE utf8_bin NOT NULL COMMENT 'email for author of this item',
  `authorHash` varchar(255) COLLATE utf8_bin NOT NULL COMMENT 'hash - with it, author can edit this item',
  `authorIP` text COLLATE utf8_bin NOT NULL COMMENT 'IP and domain name of user who add it',
  `editIP` text COLLATE utf8_bin NOT NULL COMMENT 'IP and domain name of user who made last edit',
  `content` text COLLATE utf8_bin NOT NULL COMMENT 'content in json format',
  PRIMARY KEY (`id`),
  UNIQUE KEY `authorHash` (`authorHash`),
  KEY `pageId` (`page`),
  KEY `sortable1` (`sortable1`),
  KEY `sortable2` (`sortable2`),
  KEY `sortable3` (`sortable3`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='items from all pages';


DROP TABLE IF EXISTS `permission`;
CREATE TABLE `permission` (
  `userId` int(11) NOT NULL,
  `url` varchar(250) COLLATE utf8_bin NOT NULL COMMENT 'url of page',
  `level` tinyint(4) NOT NULL COMMENT '0-normal user, 1-trustfull user,2-fullPerm',
  PRIMARY KEY (`url`,`userId`),
  KEY `userId` (`userId`),
  CONSTRAINT `permission_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'user id - auto increment',
  `username` varchar(250) COLLATE utf8_bin NOT NULL COMMENT 'username for login',
  `password` varchar(250) COLLATE utf8_bin NOT NULL COMMENT 'password  (hashed)',
  `isAdmin` tinyint(1) NOT NULL COMMENT 'is user admin?',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='table with users for more levels o permissions';


-- 2012-11-22 22:45:18