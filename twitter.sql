-- --------------------------------------------------------
-- Host:                         192.168.200.8
-- Server versie:                5.5.34-log - MySQL Community Server (GPL) by Remi
-- Server OS:                    Linux
-- HeidiSQL Versie:              8.1.0.4545
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Structuur van  tabel nrctweets.twitter wordt geschreven
CREATE TABLE IF NOT EXISTS `twitter` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `art_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `twitter_count` int(10) unsigned NOT NULL DEFAULT '0',
  `last_crawl` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `art_id` (`art_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='twitter-count, volgens twitter';

-- Data exporteren was gedeselecteerd
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
