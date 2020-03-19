-- Adminer 4.6.3 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `music`;
CREATE TABLE `music` (
  `id` mediumtext NOT NULL,
  `fileid` mediumtext NOT NULL,
  `name` mediumtext NOT NULL,
  `artist` mediumtext NOT NULL,
  `pic` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `signin`;
CREATE TABLE `signin` (
  `date` mediumtext NOT NULL,
  `userid` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `userid` mediumtext NOT NULL,
  `integral` mediumtext NOT NULL,
  `morning` mediumtext NOT NULL,
  `night` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- 2020-03-19 07:14:16