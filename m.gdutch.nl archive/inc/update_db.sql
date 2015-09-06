-- phpMyAdmin SQL Dump
-- version 3.3.8.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 09, 2011 at 08:33 PM
-- Server version: 5.0.67
-- PHP Version: 5.2.9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `goingdutch`
--

-- --------------------------------------------------------

--
-- Table structure for table `expenses_del`
--

CREATE TABLE IF NOT EXISTS `expenses_del` (
  `expense_id` int(11) NOT NULL auto_increment,
  `type` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `description` varchar(60) NOT NULL,
  `amount` float(10,2) NOT NULL,
  `expense_date` datetime NOT NULL,
  `event_id` int(11) NOT NULL default '0',
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `currency` int(11) NOT NULL,
  `del_date` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`expense_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=39 ;

-- --------------------------------------------------------

--
-- Table structure for table `groups_del`
--

CREATE TABLE IF NOT EXISTS `groups_del` (
  `group_id` int(11) NOT NULL auto_increment,
  `name` varchar(30) NOT NULL,
  `description` varchar(60) NOT NULL,
  `reg_date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `del_date` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`group_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Table structure for table `users_expenses_del`
--

CREATE TABLE IF NOT EXISTS `users_expenses_del` (
  `user_id` int(11) NOT NULL,
  `expense_id` int(11) NOT NULL,
  `del_date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`user_id`,`expense_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users_groups_del`
--

CREATE TABLE IF NOT EXISTS `users_groups_del` (
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `join_date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `del_date` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`user_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


ALTER TABLE `users_groups` ADD `removed` TINYINT NOT NULL DEFAULT '0' AFTER `role_id`;