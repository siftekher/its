-- MySQL dump 10.13  Distrib 5.1.26-rc, for redhat-linux-gnu (i686)
--
-- Host: 192.168.1.200    Database: its
-- ------------------------------------------------------
-- Server version	5.1.26-rc

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `authorized_sources`
--

DROP TABLE IF EXISTS `authorized_sources`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `authorized_sources` (
  `source_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `authorized_sources`
--

LOCK TABLES `authorized_sources` WRITE;
/*!40000 ALTER TABLE `authorized_sources` DISABLE KEYS */;
/*!40000 ALTER TABLE `authorized_sources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `email_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `headers` text NOT NULL,
  `subject` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `send_date` datetime NOT NULL,
  `app_name` varchar(100) NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `email_logs`
--

LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `last_run`
--

DROP TABLE IF EXISTS `last_run`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `last_run` (
  `cron_name` varchar(128) NOT NULL,
  `last_runtime` datetime NOT NULL,
  `start_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `elapsed_sec` float NOT NULL,
  `status` tinyint(100) DEFAULT NULL,
  `pid` int(11) DEFAULT NULL,
  PRIMARY KEY (`cron_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Keeps track of last cron run time';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `last_run`
--

LOCK TABLES `last_run` WRITE;
/*!40000 ALTER TABLE `last_run` DISABLE KEYS */;
INSERT INTO `last_run` VALUES ('ticket_maker_cron','2010-10-28 11:36:02','2010-10-28 11:36:02','2010-10-28 11:36:02',0.00558805,0,21202);
/*!40000 ALTER TABLE `last_run` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `running_cron`
--

DROP TABLE IF EXISTS `running_cron`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `running_cron` (
  `cron_id` varchar(32) NOT NULL,
  `running_status` tinyint(4) NOT NULL,
  PRIMARY KEY (`cron_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `running_cron`
--

LOCK TABLES `running_cron` WRITE;
/*!40000 ALTER TABLE `running_cron` DISABLE KEYS */;
/*!40000 ALTER TABLE `running_cron` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `source_resolvers`
--

DROP TABLE IF EXISTS `source_resolvers`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `source_resolvers` (
  `source_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `resolver_type` tinyint(4) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `status_date` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `source_resolvers`
--

LOCK TABLES `source_resolvers` WRITE;
/*!40000 ALTER TABLE `source_resolvers` DISABLE KEYS */;
/*!40000 ALTER TABLE `source_resolvers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `source_settings`
--

DROP TABLE IF EXISTS `source_settings`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `source_settings` (
  `source_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `short_name` varchar(20) DEFAULT NULL,
  `pop_email` varchar(150) DEFAULT NULL,
  `pop_password` varchar(150) DEFAULT NULL,
  `pop_server` varchar(100) DEFAULT NULL,
  `min_image_attachment_size` int(11) DEFAULT NULL,
  `footer_text` text,
  `reply_from_name` varchar(100) DEFAULT NULL,
  `reply_from_address` varchar(100) DEFAULT NULL,
  `new_ticket_email_subject` varchar(200) DEFAULT NULL,
  `new_ticket_email_template` text,
  `existing_ticket_email_subject` varchar(200) DEFAULT NULL,
  `existing_ticket_email_template` text,
  `status_reply_email_subject` varchar(200) DEFAULT NULL,
  `status_reply_email_template` text,
  `list_ticket_email_subject` varchar(200) DEFAULT NULL,
  `list_ticket_email_template` text,
  `max_response_time` bigint(20) DEFAULT NULL,
  `enable_rss_feed` tinyint(4) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `auto_assign_staff` tinyint(4) DEFAULT NULL,
  `auto_notify_supervisor` tinyint(4) DEFAULT NULL,
  `auto_notify_executive` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`source_id`),
  KEY `source_id` (`source_id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `source_settings`
--

LOCK TABLES `source_settings` WRITE;
/*!40000 ALTER TABLE `source_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `source_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `tags` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`tag_id`),
  UNIQUE KEY `tag` (`tag`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_assignments`
--

DROP TABLE IF EXISTS `ticket_assignments`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ticket_assignments` (
  `ticket_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `assigned_date` datetime DEFAULT NULL,
  `completion_date` datetime DEFAULT NULL,
  `closed_date` datetime DEFAULT NULL,
  `deleted_date` datetime DEFAULT NULL,
  `hours_spent` float DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `ticket_assignments`
--

LOCK TABLES `ticket_assignments` WRITE;
/*!40000 ALTER TABLE `ticket_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_attachments`
--

DROP TABLE IF EXISTS `ticket_attachments`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ticket_attachments` (
  `attachment_id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) DEFAULT NULL,
  `details_id` int(11) NOT NULL,
  `original_filename` varchar(100) DEFAULT NULL,
  `server_fqpn` varchar(254) DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`attachment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `ticket_attachments`
--

LOCK TABLES `ticket_attachments` WRITE;
/*!40000 ALTER TABLE `ticket_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_auth_keys`
--

DROP TABLE IF EXISTS `ticket_auth_keys`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ticket_auth_keys` (
  `ticket_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `auth_key` varchar(100) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `ticket_auth_keys`
--

LOCK TABLES `ticket_auth_keys` WRITE;
/*!40000 ALTER TABLE `ticket_auth_keys` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_auth_keys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_details`
--

DROP TABLE IF EXISTS `ticket_details`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ticket_details` (
  `details_id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `notes` text,
  `user_id` int(11) DEFAULT NULL,
  `create_date` datetime DEFAULT NULL,
  PRIMARY KEY (`details_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `ticket_details`
--

LOCK TABLES `ticket_details` WRITE;
/*!40000 ALTER TABLE `ticket_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_history`
--

DROP TABLE IF EXISTS `ticket_history`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ticket_history` (
  `ticket_id` int(11) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `status_date` datetime DEFAULT NULL,
  `changed_by_user_id` int(11) DEFAULT NULL,
  `change_method` tinyint(4) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `ticket_history`
--

LOCK TABLES `ticket_history` WRITE;
/*!40000 ALTER TABLE `ticket_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_lessons`
--

DROP TABLE IF EXISTS `ticket_lessons`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ticket_lessons` (
  `ticket_id` int(11) DEFAULT NULL,
  `staff_user_id` int(11) DEFAULT NULL,
  `supervisor_user_id` int(11) DEFAULT NULL,
  `supervisor_rating` tinyint(4) DEFAULT NULL,
  `supervisor_review_notes` text,
  `executive_user_id` int(11) DEFAULT NULL,
  `executive_review_notes` text,
  `executive_rating` tinyint(4) DEFAULT NULL,
  `source_user_id` int(11) DEFAULT NULL,
  `source_review_notes` text,
  `source_rating` tinyint(4) DEFAULT NULL,
  `staff_notes` text,
  `self_rating` tinyint(4) DEFAULT NULL,
  `average_rating` float DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `ticket_lessons`
--

LOCK TABLES `ticket_lessons` WRITE;
/*!40000 ALTER TABLE `ticket_lessons` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_lessons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_sources`
--

DROP TABLE IF EXISTS `ticket_sources`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ticket_sources` (
  `ticket_id` int(11) NOT NULL,
  `source_id` int(11) NOT NULL,
  KEY `source_id` (`source_id`),
  KEY `ticket_id` (`ticket_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `ticket_sources`
--

LOCK TABLES `ticket_sources` WRITE;
/*!40000 ALTER TABLE `ticket_sources` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_sources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_tags`
--

DROP TABLE IF EXISTS `ticket_tags`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ticket_tags` (
  `ticket_id` int(11) DEFAULT NULL,
  `tag_id` int(11) DEFAULT NULL,
  `tagged_by_user` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `ticket_tags`
--

LOCK TABLES `ticket_tags` WRITE;
/*!40000 ALTER TABLE `ticket_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_user_settings`
--

DROP TABLE IF EXISTS `ticket_user_settings`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ticket_user_settings` (
  `user_id` int(11) DEFAULT NULL,
  `enable_auto_reminder` tinyint(4) DEFAULT NULL,
  `enable_twitter_push` tinyint(4) DEFAULT NULL,
  `twitter_username` varchar(100) DEFAULT NULL,
  `twitter_auth_key` varchar(100) DEFAULT NULL,
  `enable_rss_feed` tinyint(4) DEFAULT NULL,
  `vacation_mode` tinyint(4) DEFAULT NULL,
  `enable_issues_created_by_me` tinyint(4) NOT NULL,
  `enable_issues_assigned_to_me` tinyint(4) NOT NULL,
  `enable_issues_has_my_involvement` tinyint(4) NOT NULL,
  `enable_issues_submitted_by_anyone` tinyint(4) NOT NULL,
  `show_issues_per_page` tinyint(4) NOT NULL,
  `include_rss` tinyint(4) NOT NULL,
  `number_of_issues_for_rss` int(11) NOT NULL,
  `show_tag_type` varchar(32) NOT NULL,
  `no_of_shown_tags` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `ticket_user_settings`
--

LOCK TABLES `ticket_user_settings` WRITE;
/*!40000 ALTER TABLE `ticket_user_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_user_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `tickets` (
  `ticket_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) DEFAULT NULL,
  `priority` tinyint(4) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `executive_complaint` tinyint(4) DEFAULT NULL,
  `create_date` datetime DEFAULT NULL,
  `update_date` datetime DEFAULT NULL,
  PRIMARY KEY (`ticket_id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `status` (`status`),
  KEY `create_date` (`create_date`),
  KEY `update_date` (`update_date`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `tickets`
--

LOCK TABLES `tickets` WRITE;
/*!40000 ALTER TABLE `tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(120) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `auth_key` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2010-10-28 18:39:08
