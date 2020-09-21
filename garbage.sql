-- phpMyAdmin SQL Dump
-- version 4.5.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 18, 2017 at 07:23 AM
-- Server version: 10.1.10-MariaDB
-- PHP Version: 5.6.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `garbage`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL DEFAULT '',
  `firstname` varchar(255) NOT NULL DEFAULT '',
  `lastname` varchar(255) NOT NULL DEFAULT '',
  `phonenumber` varchar(15) DEFAULT NULL,
  `activesubscription` int(11) NOT NULL DEFAULT '0',
  `subscriptionid` varchar(36) DEFAULT NULL,
  `subscription_start` datetime DEFAULT NULL,
  `subscription_end` date DEFAULT NULL,
  `billingday` int(11) NOT NULL,
  `card_last4` varchar(4) NOT NULL,
  `subscription_amount` double DEFAULT '0',
  `ipaddress` varchar(39) DEFAULT NULL,
  `lastlogin` datetime DEFAULT NULL,
  `loginattempts` int(11) NOT NULL DEFAULT '0',
  `active` int(11) NOT NULL DEFAULT '0',
  `apartment_rooms` int(11) DEFAULT NULL,
  `complex_name` varchar(255) DEFAULT NULL,
  `address_1` varchar(255) DEFAULT NULL,
  `address_2` varchar(255) DEFAULT NULL,
  `building_num` varchar(255) DEFAULT NULL,
  `gate_code` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(2) DEFAULT NULL,
  `zip_code` varchar(5) DEFAULT NULL,
  `pickup_day1` int(11) DEFAULT NULL,
  `pickup_day2` int(11) DEFAULT NULL,
  `timeslot_day1` int(11) DEFAULT NULL,
  `timeslot_day2` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `serverinfo`
--

CREATE TABLE `serverinfo` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT '',
  `siteurl` varchar(255) DEFAULT '',
  `serverpath` varchar(255) DEFAULT NULL,
  `passwordtoken` varchar(255) DEFAULT NULL,
  `mapkey` varchar(255) DEFAULT NULL,
  `originaddress` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `serverinfo`
--

INSERT INTO `serverinfo` (`id`, `title`, `siteurl`, `serverpath`, `passwordtoken`, `mapkey`, `originaddress`) VALUES
(1, 'Trash It', 'http://localhost/garbage', NULL, '"inKQS*''kbahZryi"_378ex<GYDv!F', 'AIzaSyBDvWlhNh3cQx_Ztw3nUtoy9o-xaXaEDbY', '75+N+WOODWARD+AVE+TALLAHASSEE+FL');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` varchar(36) NOT NULL DEFAULT '',
  `amount` double NOT NULL,
  `card_last4` varchar(4) NOT NULL DEFAULT '',
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `serverinfo`
--
ALTER TABLE `serverinfo`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `serverinfo`
--
ALTER TABLE `serverinfo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
