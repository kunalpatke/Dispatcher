--
-- Database: `dispatcher`
--

-- --------------------------------------------------------

--
-- Table structure for table `outgoing`
--

CREATE TABLE IF NOT EXISTS `outgoing` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `PHONENUMBER` bigint(20) NOT NULL,
  `MESSAGETEXT` varchar(500) NOT NULL,
  `STATUS` varchar(20) DEFAULT NULL,
  `ATTEMPTCOUNT` int(11) DEFAULT NULL,
  `TRANSACTIONMODE` varchar(5) DEFAULT NULL,
  `DELIVERYTIME` bigint(20) DEFAULT NULL,
  `SUBMITTEDTIME` bigint(30) DEFAULT NULL,
  `PROCESSEDTIME` bigint(20) DEFAULT NULL,
  `TRANSACTIONID` bigint(40) DEFAULT NULL,
  `MESSAGEID` varchar(20) DEFAULT NULL,
  `CAUSE` varchar(300) DEFAULT NULL,
  `MASK` varchar(30) DEFAULT NULL,
  `PRIORITY` int(2) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `type` enum('load_balance','regex','priority') NOT NULL DEFAULT 'load_balance',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


--
-- Table structure for table `rule_properties`
--

CREATE TABLE IF NOT EXISTS `rule_properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_id` int(11) NOT NULL,
  `vendor_load_balance` varchar(45) DEFAULT NULL,
  `vendor_regex` varchar(45) DEFAULT NULL,
  `vendor_priority` varchar(45) DEFAULT NULL,
  `vendor_failover_sequence` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rule_id` (`rule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `schemainfo`
--

CREATE TABLE IF NOT EXISTS `schemainfo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `database_type` enum('MYSQL','MSSQL','ORACLE') NOT NULL DEFAULT 'MYSQL',
  `host` varchar(20) NOT NULL,
  `database_name` varchar(20) NOT NULL,
  `username` varchar(20) DEFAULT NULL,
  `password` varchar(20) DEFAULT NULL,
  `table_name` varchar(20) NOT NULL,
  `query` varchar(500) NOT NULL,
  `columns` varchar(1000) NOT NULL,
  `condition` varchar(200) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `priority` enum('HIGH','MEDIUM','LOW') NOT NULL DEFAULT 'MEDIUM',
  `rule_id` int(11) DEFAULT NULL,
  `account_type` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rule_id` (`rule_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE IF NOT EXISTS `vendors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Table structure for table `vendor_params`
--

CREATE TABLE IF NOT EXISTS `vendor_params` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) DEFAULT NULL,
  `protocol` enum('http','https','smtp') NOT NULL DEFAULT 'http',
  `url` varchar(200) NOT NULL,
  `params` varchar(300) NOT NULL,
  `multi_message_support` tinyint(1) NOT NULL DEFAULT '0',
  `unicode_support` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `vendor_id` (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `rule_properties`
--
ALTER TABLE `rule_properties`
  ADD CONSTRAINT `rule_properties_ibfk_1` FOREIGN KEY (`rule_id`) REFERENCES `rules` (`id`);

--
-- Constraints for table `schemainfo`
--
ALTER TABLE `schemainfo`
  ADD CONSTRAINT `schemainfo_ibfk_1` FOREIGN KEY (`rule_id`) REFERENCES `rules` (`id`);

--
-- Constraints for table `vendor_params`
--
ALTER TABLE `vendor_params`
  ADD CONSTRAINT `vendor_params_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
