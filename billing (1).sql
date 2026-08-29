-- MySQL Dump - SUNDER MACHINES WORKS Fresh Database
-- Host: localhost    Database: billing
-- ------------------------------------------------------

SET FOREIGN_KEY_CHECKS=0;

--
-- Table structure for table `address`
--

DROP TABLE IF EXISTS `address`;
CREATE TABLE `address` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `line1` varchar(255) DEFAULT NULL,
  `line2` varchar(255) DEFAULT NULL,
  `zipCode` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3154 DEFAULT CHARSET=utf32;

--
-- Dumping data for table `address`
--

INSERT INTO `address` (`id`, `createdBy`, `createdOn`, `modifiedBy`, `modifiedOn`, `city`, `line1`, `line2`, `zipCode`) VALUES
(192, 'owner@sanruth.com', '2025-01-28 15:28:24.649620', 'owner@sanruth.com', '2025-01-28 15:28:24.649620', 'GOBI', 'NO.4 SANRUTH TOWERS', 'NEAR BUS STAND', 638476),
(3148, NULL, '2026-08-29 16:31:28.000000', NULL, '2026-08-29 16:36:42.000000', 'ERODE', 'RTYUNJMK,L', 'QWERTYUIOPOIU', 929433),
(3149, NULL, '2026-08-29 16:33:29.000000', NULL, '2026-08-29 16:33:29.000000', 'ERODE', NULL, NULL, NULL),
(3150, NULL, '2026-08-29 16:33:49.000000', NULL, '2026-08-29 16:33:49.000000', 'ERODE', NULL, NULL, NULL),
(3151, NULL, '2026-08-29 16:34:32.000000', NULL, '2026-08-29 16:34:32.000000', 'ERODE', NULL, NULL, NULL),
(3152, 'System Admin', '2026-08-29 17:19:00.000000', 'System Admin', '2026-08-29 17:19:00.000000', 'swsqsqsqs', 'car stret,gobi', '', 123321),
(3153, NULL, '2026-08-29 18:41:30.000000', NULL, '2026-08-29 18:41:30.000000', 'erode', '', '', 233322);

--
-- Table structure for table `brand`
--

DROP TABLE IF EXISTS `brand`;
CREATE TABLE `brand` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `brandName` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_8mw73aryipaafhvqpgt1ioud4` (`brandName`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf32;

--
-- Dumping data for table `brand`
--

INSERT INTO `brand` (`id`, `createdBy`, `createdOn`, `modifiedBy`, `modifiedOn`, `brandName`) VALUES
(1, 'owner@sanruth.com', '2024-02-22 19:21:59.016289', 'owner@sanruth.com', '2024-02-22 19:21:59.016289', 'SANRUTH SV'),
(2, 'owner@sanruth.com', '2024-02-22 20:49:42.857766', 'System Admin', '2026-08-28 12:38:37.000000', 'Sunder'),
(3, 'owner@sanruth.com', '2024-02-23 10:10:04.250705', 'owner@sanruth.com', '2024-02-23 10:10:04.250705', 'KING'),
(6, 'owner@sanruth.com', '2024-02-27 18:01:29.093006', 'owner@sanruth.com', '2024-02-27 18:01:29.093006', 'GTC'),
(7, 'owner@sanruth.com', '2024-03-16 09:08:40.638868', 'System Admin', '2026-08-29 17:20:02.000000', 'MERRITT TA1'),
(9, 'owner@sanruth.com', '2025-01-11 18:50:59.678109', 'owner@sanruth.com', '2025-01-11 18:50:59.678109', 'GARLON '),
(10, 'owner@sanruth.com', '2025-01-15 18:38:51.639773', 'owner@sanruth.com', '2025-01-15 18:38:51.639773', 'EDGE'),
(11, 'owner@sanruth.com', '2025-03-26 18:54:17.663056', 'System Admin', '2026-04-06 20:30:47.000000', 'HAYA'),
(12, 'owner@sanruth.com', '2025-12-16 14:10:23.261163', 'owner@sanruth.com', '2025-12-16 14:10:23.261163', 'SIO'),
(13, 'System Admin', '2026-04-06 20:31:28.000000', 'System Admin', '2026-04-06 20:31:28.000000', 'kathir'),
(16, 'System Admin', '2026-04-09 08:10:37.000000', 'System Admin', '2026-04-09 08:10:37.000000', 'Mahi'),
(17, 'System Admin', '2026-08-29 17:19:43.000000', 'System Admin', '2026-08-29 17:20:00.000000', 'seinh 122');

--
-- Table structure for table `credential`
--

DROP TABLE IF EXISTS `credential`;
CREATE TABLE `credential` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_p961pvq98pc0pkxa5qh22h7tp` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf32;

--
-- Dumping data for table `credential`
--

INSERT INTO `credential` (`id`, `createdBy`, `createdOn`, `modifiedBy`, `modifiedOn`, `password`, `role`, `username`) VALUES
(1, 'SYSTEM', '2024-02-03 20:21:46.771089', 'SYSTEM', '2024-02-03 20:21:46.771089', '$2a$10$boexGG6nGOA3prcZJ7FYE..JKhRMW5EpSZJVsTZs1oKmBQICcfH92', 'IT', 'support@sanruth.com'),
(2, 'SYSTEM', '2024-02-03 20:21:46.992680', 'SYSTEM', '2024-02-03 20:21:46.992680', '$2a$10$fkjAU2vYEYqqyxpkicn4leySh5TCoPrs9YXYYnksWWN.6k3c5aNWm', 'Admin', 'owner@sanruth.com'),
(3, 'SYSTEM', '2024-02-03 20:21:47.464366', 'SYSTEM', '2024-02-03 20:21:47.464366', '$2a$10$X6nhgVggmas5hwy5M9womeXOIW6JJiCk.q6hLG0RZOiNs.d2IvqsG', 'Mechanic', 'mechanic@sanruth.com'),
(4, 'SYSTEM', '2024-02-03 20:21:47.799251', 'SYSTEM', '2024-02-03 20:21:47.799251', '$2a$10$8./HmMFm55taMcZEXc4TGe/Q6upQJwghZz2vuPIIOk1TP1LLU8.qS', 'Sales', 'sales@sanruth.com'),
(5, 'SYSTEM', '2024-02-03 20:21:48.242194', 'SYSTEM', '2024-02-03 20:21:48.242194', '$2a$10$WcR8gCcSkLSxhhioHDP1YuS21VcftYKvvxXw5V7AXOUJr1a54CB0K', 'Billing', 'bill@sanruth.com'),
(6, 'SYSTEM', '2024-02-03 20:21:48.647243', 'SYSTEM', '2024-02-03 20:21:48.647243', '$2a$10$l56zEOeCdbX/JFUfb2sZ2O8LR8lRb7dxAzx5Qqv/.5wxfpWVcRJbG', 'Cashier', 'cashier@sanruth.com'),
(7, 'owner@sanruth.com', '2025-01-24 09:57:10.994964', 'owner@sanruth.com', '2025-01-24 09:57:15.947071', NULL, 'Mechanic', 'E00001@sanruth.com'),
(8, 'owner@sanruth.com', '2025-01-25 19:32:13.236585', 'owner@sanruth.com', '2025-01-25 19:32:13.236585', '$2a$10$OTFvb2aTNFUYsx.6J9iNJ.rU8G6uOJ1UdxCzckZtIGeqxnR0Czlem', 'Admin', 'E00002@sanruth.com'),
(9, 'owner@sanruth.com', '2025-01-27 09:50:58.894350', 'owner@sanruth.com', '2025-01-27 09:50:58.894350', NULL, 'Billing', 'E00003@sanruth.com'),
(10, 'owner@sanruth.com', '2025-01-27 09:52:19.337656', 'owner@sanruth.com', '2025-01-27 09:52:21.506715', NULL, 'Billing', 'E00004@sanruth.com'),
(11, 'owner@sanruth.com', '2025-01-27 09:55:29.817725', 'owner@sanruth.com', '2025-01-27 09:55:29.817725', '$2a$10$UNL5HpEK5OzdllmBQ8azpeNVA8qz.hzbuCM1CM2B03rjzzHeMYNZm', 'Billing', 'E00005@sanruth.com'),
(12, 'owner@sanruth.com', '2025-01-27 09:57:42.371138', 'owner@sanruth.com', '2025-01-27 09:57:42.371138', '$2a$10$zh4HYpBy.ao/yhluk9XZDuVbi4XOANwJtHexAYjFKKsyrn4KcOPpS', 'Admin', 'E00006@sanruth.com'),
(13, 'owner@sanruth.com', '2025-01-27 10:01:04.894878', 'owner@sanruth.com', '2025-01-27 10:01:04.894878', '$2a$10$PMjjyP1GKTDVL/Zmx5xI5O2O0KQhqso4C4Lt/GpQs4PU/vowsIQUm', 'Sales', 'E00007@sanruth.com'),
(14, 'owner@sanruth.com', '2025-03-18 17:58:13.203245', 'owner@sanruth.com', '2025-03-18 17:58:13.203245', NULL, 'Mechanic', 'E00008@sanruth.com');

--
-- Table structure for table `customer`
--

DROP TABLE IF EXISTS `customer`;
CREATE TABLE `customer` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `active` bit(1) NOT NULL,
  `customerId` varchar(255) DEFAULT NULL,
  `emailId` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `phoneNo1` varchar(255) DEFAULT NULL,
  `phoneNo2` varchar(255) DEFAULT NULL,
  `whatsAppNo` varchar(255) DEFAULT NULL,
  `address` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_f4ujgq5ch50wl6lx48iu6wcvv` (`address`),
  CONSTRAINT `FKfn5r4mug085lanufp9msc1q6n` FOREIGN KEY (`address`) REFERENCES `address` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf32;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`id`, `active`, `customerId`, `emailId`, `name`, `phoneNo1`, `phoneNo2`, `whatsAppNo`, `address`) VALUES
(1, 1, 'C0000001', 'nnison@2GMAIL.COM', 'tennison', 3344333444, '', 2323232323, 3148),
(2, 1, 'C0000002', NULL, 'tennison', '', NULL, NULL, 3149),
(3, 1, 'C0000003', NULL, 'tennison', '', NULL, NULL, 3150),
(4, 1, 'C0000004', NULL, 'tennison', '', NULL, NULL, 3151),
(5, 1, 'C0000001', '', 'willgax', 2233223322, '', '', 3153);

--
-- Table structure for table `employee`
--

DROP TABLE IF EXISTS `employee`;
CREATE TABLE `employee` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `active` bit(1) NOT NULL,
  `city` varchar(255) DEFAULT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `empId` varchar(255) DEFAULT NULL,
  `employmentType` varchar(255) DEFAULT NULL,
  `exitDate` date DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `joinedDate` date DEFAULT NULL,
  `line1` varchar(255) DEFAULT NULL,
  `line2` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `phoneNo1` varchar(255) DEFAULT NULL,
  `phoneNo2` varchar(255) DEFAULT NULL,
  `zipCode` varchar(255) DEFAULT NULL,
  `credential` bigint DEFAULT NULL,
  `aadhaarNo` varchar(255) DEFAULT NULL,
  `aadharCardPhoto` varchar(255) DEFAULT NULL,
  `drivingLicence` varchar(255) DEFAULT NULL,
  `drivingLicencePhoto` varchar(255) DEFAULT NULL,
  `profilePhoto` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_1yxxakvyi7blmiydsg8415xwd` (`credential`),
  CONSTRAINT `FK1c7h2omesuyjmly5k29regua` FOREIGN KEY (`credential`) REFERENCES `credential` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf32;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`id`, `createdBy`, `createdOn`, `modifiedBy`, `modifiedOn`, `active`, `city`, `designation`, `dob`, `empId`, `employmentType`, `exitDate`, `gender`, `joinedDate`, `line1`, `line2`, `name`, `phoneNo1`, `phoneNo2`, `zipCode`, `credential`, `aadhaarNo`, `aadharCardPhoto`, `drivingLicence`, `drivingLicencePhoto`, `profilePhoto`) VALUES
(1, 'SYSTEM', '2024-02-03 20:21:46.761063', 'SYSTEM', '2024-02-03 20:21:46.761063', 1, NULL, 'Support Engineer', '2024-02-03', 001, 'Permanent', NULL, 'M', '2024-02-03', '', NULL, 'Support Engineer', NULL, NULL, '', 1, NULL, NULL, NULL, NULL, NULL),
(2, 'SYSTEM', '2024-02-03 20:21:46.992680', 'SYSTEM', '2024-02-03 20:21:46.992680', 1, NULL, 'Owner', '2024-02-03', 002, 'Permanent', NULL, 'M', '2024-02-03', '', NULL, 'Owner', NULL, NULL, '', 2, NULL, NULL, NULL, NULL, NULL),
(3, 'SYSTEM', '2024-02-03 20:21:47.463329', 'SYSTEM', '2024-02-03 20:21:47.463329', 1, NULL, 'Mechanic', '2024-02-03', 003, 'Permanent', NULL, 'M', '2024-02-03', '', NULL, 'Mechanic', NULL, NULL, '', 3, NULL, NULL, NULL, NULL, NULL),
(4, 'SYSTEM', '2024-02-03 20:21:47.798249', 'SYSTEM', '2024-02-03 20:21:47.798249', 1, NULL, 'Sales Executive', '2024-02-03', 005, 'Permanent', NULL, 'M', '2024-02-03', '', NULL, 'Sales Executive', NULL, NULL, '', 4, NULL, NULL, NULL, NULL, NULL),
(5, 'SYSTEM', '2024-02-03 20:21:48.241200', 'SYSTEM', '2024-02-03 20:21:48.241200', 1, NULL, 'Billing', '2024-02-03', 005, 'Permanent', NULL, 'M', '2024-02-03', '', NULL, 'Billing', NULL, NULL, '', 5, NULL, NULL, NULL, NULL, NULL),
(6, 'SYSTEM', '2024-02-03 20:21:48.646241', 'SYSTEM', '2024-02-03 20:21:48.646241', 1, NULL, 'Cashier', '2024-02-03', 005, 'Permanent', NULL, 'M', '2024-02-03', '', NULL, 'Cashier', NULL, NULL, '', 6, NULL, NULL, NULL, NULL, NULL),
(7, 'owner@sanruth.com', '2025-01-24 09:57:10.989920', 'owner@sanruth.com', '2025-01-24 09:57:15.947071', 1, 'ANTHIYUR', 'Mechanic', '1996-02-12', 'E00001', 'Permanent', '2075-01-23', 'F', '2022-11-20', 'KALLANKAATU PUTHUR', 'KEELVANI', 'NITHYAKALYANI', 8760567343, 8760567343, 638502, 7, NULL, NULL, NULL, NULL, NULL),
(8, 'owner@sanruth.com', '2025-01-25 19:32:13.236585', 'owner@sanruth.com', '2025-01-31 19:46:04.560018', 1, 'GOBI', 'Owner', '2013-12-10', 'E00002', 'Permanent', '2115-01-24', 'M', '2024-12-31', 'SWASTIK ILLAM', 'ASIRIYAR NAGAR', 'SANJAY E', 9043761441, 9965561326, 638476, 8, NULL, NULL, NULL, NULL, NULL),
(9, 'owner@sanruth.com', '2025-01-27 09:50:58.894350', 'owner@sanruth.com', '2025-04-30 10:26:20.597424', 1, 'GOBI', 'Billing', '2019-08-06', 'E00003', 'Permanent', '2115-01-26', 'M', '2025-01-05', 'D', '', 'E. Ruthvik ', 000, 0, 638458, 9, 1234567892, NULL, 00000, NULL, NULL),
(10, 'owner@sanruth.com', '2025-01-27 09:52:19.337656', 'owner@sanruth.com', '2025-04-30 10:25:26.700589', 1, 'GOBI', 'Billing', '1994-10-02', 'E00004', 'Permanent', '2115-01-26', 'F', '2025-01-05', 'C', '', 'E. Priya ', 00000, 000, 638458, 10, 1234567892, NULL, 00000, NULL, NULL),
(11, 'owner@sanruth.com', '2025-01-27 09:55:29.817725', 'owner@sanruth.com', '2025-04-15 16:34:45.329423', 1, 'GOBI', 'Billing', '2003-08-12', 'E00005', 'Permanent', '2115-01-26', 'M', '2025-01-27', 'C', '', 'Elango ', 0000, NULL, 638458, 11, 1234567892, NULL, 000, NULL, NULL),
(12, 'owner@sanruth.com', '2025-01-27 09:57:42.371138', 'owner@sanruth.com', '2025-04-30 10:26:58.778651', 1, 'GOBI', 'Mechanic', '2003-08-12', 'E00006', 'Permanent', '2115-01-26', 'M', '2025-01-05', 'ALAMPALAYAM', 'NAMBIYUR', 'Jaheer ', 000, NULL, 638458, 12, 1234567892, NULL, 00000, NULL, NULL),
(13, 'owner@sanruth.com', '2025-01-27 10:01:04.894878', 'owner@sanruth.com', '2026-05-12 06:41:00.901271', 1, 'GOBI', 'Mechanic', '2003-08-12', 'E00007', 'Permanent', '2026-02-25', 'M', '2025-01-05', 'ALAMPALAYAM', 'NAMBIYUR', 'Elango Sanruth ', 000, NULL, 638458, 13, '12345+67892', NULL, 0000, NULL, NULL),
(14, 'owner@sanruth.com', '2025-03-18 17:58:13.203245', 'owner@sanruth.com', '2026-05-12 06:43:22.239565', 1, 'ERODE', 'Support Engineer', '2000-01-04', 'E00008', 'Permanent', '2115-03-17', 'F', '2024-08-26', 'NO.34 ROAD DAM 3 STREET ', 'SALANGAPALAYAM KAVUNTHAMPADI', 'Sanruth ', 9384177523, 8072669282, 638455, 14, 884435025079, 'tmp/AadharCard/ba274a7cdd014bef985a9eca2f1dedb3.jpg', 00000, NULL, NULL),
(75, NULL, '2026-08-29 16:47:52.000000', NULL, '2026-08-29 16:47:52.000000', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ZOMATO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(76, NULL, '2026-08-29 18:42:55.000000', NULL, '2026-08-29 18:42:55.000000', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'omnitrix', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Table structure for table `employee_auth`
--

DROP TABLE IF EXISTS `employee_auth`;
CREATE TABLE `employee_auth` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime DEFAULT CURRENT_TIMESTAMP,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employee_auth`
--

INSERT INTO `employee_auth` (`id`, `createdBy`, `createdOn`, `modifiedBy`, `modifiedOn`, `password`, `role`, `username`) VALUES
(1, 'SYSTEM', '2026-08-27 16:55:09', NULL, NULL, '$2y$10$eKvW2lZcEn7OJBACg92yW.whqftNBiEC7S9EY24vp6E3RaOLHH7.O', 'EMPLOYEE001', 'hari');

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `amount` float NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `jobcard`
--

DROP TABLE IF EXISTS `jobcard`;
CREATE TABLE `jobcard` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `actualAmountSum` float NOT NULL,
  `cardNo` varchar(255) DEFAULT NULL,
  `completed` bit(1) NOT NULL,
  `completedDate` date DEFAULT NULL,
  `delivered` bit(1) NOT NULL,
  `deliveryDate` date DEFAULT NULL,
  `givenDate` date DEFAULT NULL,
  `jobCategory` varchar(255) DEFAULT NULL,
  `jobStatus` varchar(255) DEFAULT NULL,
  `laborCharge` float NOT NULL,
  `quoteAmountSum` float NOT NULL,
  `receivedAmountSum` float NOT NULL,
  `customer` bigint DEFAULT NULL,
  `employee` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FKr7qhnw1v6mamxow90egb5fvkb` (`customer`),
  KEY `FK5gaox2htdx5dlyfbmj34ysot1` (`employee`),
  CONSTRAINT `FK5gaox2htdx5dlyfbmj34ysot1` FOREIGN KEY (`employee`) REFERENCES `employee` (`id`),
  CONSTRAINT `FKr7qhnw1v6mamxow90egb5fvkb` FOREIGN KEY (`customer`) REFERENCES `customer` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf32;

--
-- Dumping data for table `jobcard`
--

INSERT INTO `jobcard` (`id`, `createdBy`, `createdOn`, `modifiedBy`, `modifiedOn`, `actualAmountSum`, `cardNo`, `completed`, `completedDate`, `delivered`, `deliveryDate`, `givenDate`, `jobCategory`, `jobStatus`, `laborCharge`, `quoteAmountSum`, `receivedAmountSum`, `customer`, `employee`) VALUES
(1, 'System Admin', '2026-08-29 16:47:52.000000', 'System Admin', '2026-08-29 16:47:52.000000', 0, '2608J00001', 0, NULL, 0, NULL, '2026-08-29', 'Offsite', 'New', 0, 0, 0, 1, 75),
(2, 'System Admin', '2026-08-29 16:59:13.000000', 'System Admin', '2026-08-29 16:59:13.000000', 0, '2608J00002', 0, NULL, 0, NULL, '2026-08-29', 'Offsite', 'New', 0, 0, 0, 1, 75),
(3, 'System Admin', '2026-08-29 18:42:55.000000', 'System Admin', '2026-08-29 18:42:55.000000', 0, '2608J00003', 0, NULL, 0, NULL, '2026-08-29', 'Offsite', 'New', 0, 0, 0, 5, 76);

--
-- Table structure for table `jobcarditemimage`
--

DROP TABLE IF EXISTS `jobcarditemimage`;
CREATE TABLE `jobcarditemimage` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `base64Value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32;

--
-- Table structure for table `jobcarditems`
--

DROP TABLE IF EXISTS `jobcarditems`;
CREATE TABLE `jobcarditems` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `actualAmount` float NOT NULL,
  `assembledByUs` bit(1) NOT NULL,
  `deleted` bit(1) NOT NULL,
  `issueDetails` varchar(255) DEFAULT NULL,
  `machineName` varchar(255) DEFAULT NULL,
  `picture` varchar(255) DEFAULT NULL,
  `quoteAmount` float NOT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `serialNo` varchar(255) DEFAULT NULL,
  `machine` bigint DEFAULT NULL,
  `jobCard` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FKaul39e08v70nokcktan2f84ir` (`machine`),
  KEY `FK2uiriwxh1pmqjkbhpxe51gmw0` (`jobCard`),
  CONSTRAINT `FK2uiriwxh1pmqjkbhpxe51gmw0` FOREIGN KEY (`jobCard`) REFERENCES `jobcard` (`id`),
  CONSTRAINT `FKaul39e08v70nokcktan2f84ir` FOREIGN KEY (`machine`) REFERENCES `machine` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf32;

--
-- Dumping data for table `jobcarditems`
--

INSERT INTO `jobcarditems` (`id`, `createdBy`, `createdOn`, `modifiedBy`, `modifiedOn`, `actualAmount`, `assembledByUs`, `deleted`, `issueDetails`, `machineName`, `picture`, `quoteAmount`, `remark`, `serialNo`, `machine`, `jobCard`) VALUES
(1, 'System Admin', '2026-08-29 16:47:52.000000', 'System Admin', '2026-08-29 16:47:52.000000', 0, 0, 0, 'Replacement', 'SEIWING 20', '', 0, 'foods', 2122121112, NULL, 1),
(2, 'System Admin', '2026-08-29 16:59:13.000000', 'System Admin', '2026-08-29 16:59:13.000000', 0, 0, 0, 'Service', 'SEIWING 233', '', 0, 'qwertuio', '#23221', NULL, 2),
(3, 'System Admin', '2026-08-29 18:42:55.000000', 'System Admin', '2026-08-29 18:42:55.000000', 0, 0, 0, 'Repair', '23ww', '', 0, '', 'ww2222', NULL, 3);

--
-- Table structure for table `jobcarditemscomment`
--

DROP TABLE IF EXISTS `jobcarditemscomment`;
CREATE TABLE `jobcarditemscomment` (
  `id` varchar(255) NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `jobCardItem` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FKnea1nwmuis7rifq9m4cqkqc9k` (`jobCardItem`),
  CONSTRAINT `FKnea1nwmuis7rifq9m4cqkqc9k` FOREIGN KEY (`jobCardItem`) REFERENCES `jobcarditems` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32;

--
-- Table structure for table `jobcarditemspares`
--

DROP TABLE IF EXISTS `jobcarditemspares`;
CREATE TABLE `jobcarditemspares` (
  `id` varchar(255) NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `deleted` bit(1) NOT NULL,
  `gstPercentage` float NOT NULL,
  `gstValue` float NOT NULL,
  `itemName` varchar(255) DEFAULT NULL,
  `pricePerQty` float NOT NULL,
  `quantity` int NOT NULL,
  `serialNo` varchar(255) DEFAULT NULL,
  `totalPrice` float NOT NULL,
  `spares` bigint DEFAULT NULL,
  `stock` varchar(255) DEFAULT NULL,
  `jobCardItem` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FKpdb0to03ilwh8xin14bw16ycd` (`spares`),
  KEY `FKkhbeb4d2p4qcuopp4h6m1pcg0` (`stock`),
  KEY `FKrgk3u9qkdgu8x7u4tdon6dcku` (`jobCardItem`),
  CONSTRAINT `FKkhbeb4d2p4qcuopp4h6m1pcg0` FOREIGN KEY (`stock`) REFERENCES `stock` (`id`),
  CONSTRAINT `FKpdb0to03ilwh8xin14bw16ycd` FOREIGN KEY (`spares`) REFERENCES `spares` (`id`),
  CONSTRAINT `FKrgk3u9qkdgu8x7u4tdon6dcku` FOREIGN KEY (`jobCardItem`) REFERENCES `jobcarditems` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32;

--
-- Table structure for table `loginhisotry`
--

DROP TABLE IF EXISTS `loginhisotry`;
CREATE TABLE `loginhisotry` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `inTime` datetime(6) DEFAULT NULL,
  `outTime` datetime(6) DEFAULT NULL,
  `credential` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK58dtpgy4wgnax2tc24yftmt6t` (`credential`),
  CONSTRAINT `FK58dtpgy4wgnax2tc24yftmt6t` FOREIGN KEY (`credential`) REFERENCES `credential` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `machine`
--

DROP TABLE IF EXISTS `machine`;
CREATE TABLE `machine` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `active` bit(1) NOT NULL,
  `assembeldByUs` bit(1) NOT NULL,
  `machineName` varchar(50) DEFAULT NULL,
  `picture` varchar(255) DEFAULT NULL,
  `machineType` bigint DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=378 DEFAULT CHARSET=utf32;

--
-- Dumping data for table `machine`
--

INSERT INTO `machine` (`id`, `createdBy`, `createdOn`, `modifiedBy`, `modifiedOn`, `active`, `assembeldByUs`, `machineName`, `picture`, `machineType`) VALUES
(1, 'support@sanruth.com', '2024-02-05 05:11:20.047476', 'support@sanruth.com', '2024-02-05 05:11:20.248011', 1, 0, 'test', 'Machine/1/0f9c60048fd140f6a2dccc6ec6b984ce.jpeg', NULL),
(2, 'owner@sanruth.com', '2024-02-22 12:11:05.657018', 'owner@sanruth.com', '2024-02-22 12:11:05.657018', 1, 0, 'Sanruth 95T10', NULL, NULL),
(3, 'owner@sanruth.com', '2024-03-16 09:07:35.754388', 'owner@sanruth.com', '2024-03-16 09:07:35.935247', 1, 0, 'MERRITT ', 'Machine/3/fa228b84bc904fc9b2c650366b67cc63.jpeg', NULL),
(4, 'owner@sanruth.com', '2024-03-21 19:23:11.425283', 'owner@sanruth.com', '2024-03-21 19:23:11.425283', 1, 0, 'REVO BAG CLOSER', NULL, NULL),
(5, 'owner@sanruth.com', '2024-03-21 19:34:43.195282', 'owner@sanruth.com', '2024-03-21 19:34:43.195282', 1, 0, 'SIRUBA POWER', NULL, NULL),
(6, 'owner@sanruth.com', '2024-03-21 19:36:45.157083', 'owner@sanruth.com', '2024-03-21 19:36:45.157083', 1, 0, 'SWASTIK BAG CLOSER', NULL, NULL),
(7, 'owner@sanruth.com', '2024-03-21 19:39:02.511421', 'owner@sanruth.com', '2024-03-21 19:39:02.511421', 1, 0, 'USHA TA1', NULL, NULL),
(8, 'owner@sanruth.com', '2024-03-29 09:48:06.764545', 'owner@sanruth.com', '2024-03-29 09:48:06.764545', 1, 0, 'panther ta1', NULL, NULL),
(9, 'owner@sanruth.com', '2024-03-29 09:51:40.637600', 'owner@sanruth.com', '2024-03-29 09:51:40.637600', 1, 0, 'merritt ta1', NULL, NULL),
(10, 'owner@sanruth.com', '2024-03-29 09:59:42.105222', 'owner@sanruth.com', '2024-03-29 09:59:42.105222', 1, 0, 'MONA TA1', NULL, NULL),
(11, 'owner@sanruth.com', '2024-03-29 10:04:51.158997', 'owner@sanruth.com', '2024-03-29 10:04:51.158997', 1, 0, 'NAME LESS 95T10', NULL, NULL),
(12, 'owner@sanruth.com', '2024-03-29 10:10:10.349552', 'owner@sanruth.com', '2024-03-29 10:10:10.349552', 1, 0, 'BROTHER 95 T10', NULL, NULL),
(13, 'owner@sanruth.com', '2024-03-29 10:16:48.473894', 'owner@sanruth.com', '2024-03-29 10:16:48.473894', 1, 0, 'BRUCE POWER', NULL, NULL),
(14, 'owner@sanruth.com', '2024-03-29 10:19:35.354936', 'owner@sanruth.com', '2024-03-29 10:19:35.354936', 1, 0, 'MERRIT S.V', NULL, NULL),
(15, 'owner@sanruth.com', '2024-03-29 10:40:19.729936', 'owner@sanruth.com', '2024-03-29 10:40:19.729936', 1, 0, 'swastik 95t10', NULL, NULL),
(16, 'owner@sanruth.com', '2024-03-29 11:04:03.040762', 'owner@sanruth.com', '2024-03-29 11:04:03.040762', 1, 0, 'rama sv', NULL, NULL),
(17, 'owner@sanruth.com', '2024-03-29 11:44:28.219757', 'owner@sanruth.com', '2024-03-29 11:44:28.219757', 1, 0, 'motor with regulator only', NULL, NULL),
(18, 'owner@sanruth.com', '2024-03-29 11:46:41.352852', 'owner@sanruth.com', '2024-03-29 11:46:41.352852', 1, 0, 'jack f4', NULL, NULL),
(19, 'owner@sanruth.com', '2024-03-29 18:53:06.859013', 'owner@sanruth.com', '2024-03-29 18:53:06.859013', 1, 0, 'BROTHER TA1', NULL, NULL),
(20, 'owner@sanruth.com', '2024-03-29 19:11:34.604936', 'owner@sanruth.com', '2024-03-29 19:11:34.604936', 1, 0, 'SUNSIR POWER', NULL, NULL),
(21, 'owner@sanruth.com', '2024-03-29 19:16:34.163252', 'owner@sanruth.com', '2024-03-29 19:16:34.163252', 1, 0, 'MONA ZIG ZAG', NULL, NULL),
(22, 'owner@sanruth.com', '2024-03-30 09:33:11.171255', 'owner@sanruth.com', '2024-03-30 09:33:11.171255', 1, 0, 'MERRITT 95T10', NULL, NULL),
(23, 'owner@sanruth.com', '2024-03-30 09:36:41.352766', 'owner@sanruth.com', '2024-03-30 09:36:41.352766', 1, 0, 'SEIKO OVER LOCK', NULL, NULL),
(24, 'owner@sanruth.com', '2024-03-30 09:41:37.987475', 'owner@sanruth.com', '2024-03-30 09:41:37.987475', 1, 0, 'MONA MOTOR WITH REGULATRE ONLY', NULL, NULL),
(25, 'owner@sanruth.com', '2024-03-30 10:17:58.132180', 'owner@sanruth.com', '2024-03-30 10:17:58.132180', 1, 0, 'USHA JANOME SEW LITE ', NULL, NULL),
(26, 'owner@sanruth.com', '2024-03-30 10:29:38.338409', 'owner@sanruth.com', '2024-03-30 10:29:38.338409', 1, 0, 'REGULATOR ONLY', NULL, NULL),
(27, 'owner@sanruth.com', '2024-04-02 10:19:48.763500', 'owner@sanruth.com', '2024-04-02 10:19:48.763500', 1, 0, 'FEIYUE POWER', NULL, NULL),
(28, 'owner@sanruth.com', '2024-04-02 10:21:31.621224', 'owner@sanruth.com', '2024-04-02 10:21:31.621224', 1, 0, 'REVO O/L', NULL, NULL),
(29, 'owner@sanruth.com', '2024-04-02 10:31:53.277465', 'owner@sanruth.com', '2024-04-02 10:31:53.277465', 1, 0, 'RITA S.V ', NULL, NULL),
(30, 'owner@sanruth.com', '2024-04-02 17:59:41.147855', 'owner@sanruth.com', '2024-04-02 17:59:41.147855', 1, 0, 'ranew o/l machine', NULL, NULL),
(31, 'owner@sanruth.com', '2024-04-03 09:27:59.615560', 'owner@sanruth.com', '2024-04-03 09:27:59.615560', 1, 0, 'NAME LESS TA1', NULL, NULL),
(32, 'owner@sanruth.com', '2024-04-03 09:33:15.288503', 'owner@sanruth.com', '2024-04-03 09:33:15.288503', 1, 0, 'JIKKI POWER', NULL, NULL),
(33, 'owner@sanruth.com', '2024-04-05 11:32:52.144771', 'owner@sanruth.com', '2024-04-05 11:32:52.144771', 1, 0, 'RANEW ZIG ZAG', NULL, NULL),
(34, 'owner@sanruth.com', '2024-04-05 11:41:35.606292', 'owner@sanruth.com', '2024-04-05 11:41:35.606292', 1, 0, 'SWASTIK TA1', NULL, NULL),
(35, 'owner@sanruth.com', '2024-04-05 11:45:34.993952', 'owner@sanruth.com', '2024-04-05 11:45:34.993952', 1, 0, 'JACK A2B', NULL, NULL),
(36, 'owner@sanruth.com', '2025-01-24 09:43:05.952211', 'owner@sanruth.com', '2025-01-24 09:43:05.952211', 1, 0, 'MEGHA S.V', NULL, NULL),
(37, 'owner@sanruth.com', '2025-01-24 10:14:18.749324', 'owner@sanruth.com', '2025-01-24 10:14:18.749324', 1, 0, 'BROTHER TA1 WITH MOTOR&REGULATOR', NULL, NULL),
(38, 'owner@sanruth.com', '2025-01-24 10:21:41.243329', 'owner@sanruth.com', '2025-01-24 10:21:41.243329', 1, 0, 'USHA POWER', NULL, NULL),
(39, 'owner@sanruth.com', '2025-01-24 11:26:23.257273', 'owner@sanruth.com', '2025-01-24 11:26:23.257273', 1, 0, 'ESR BAG CLOSER', NULL, NULL),
(40, 'owner@sanruth.com', '2025-01-24 13:05:09.864059', 'owner@sanruth.com', '2025-01-24 13:05:09.864059', 1, 0, 'SWASTIK LINK MODEL', NULL, NULL),
(41, 'owner@sanruth.com', '2025-01-24 15:45:41.229360', 'owner@sanruth.com', '2025-01-24 15:45:41.229360', 1, 0, 'SUNDAR S.V', NULL, NULL),
(42, 'owner@sanruth.com', '2025-01-24 15:53:53.092094', 'owner@sanruth.com', '2025-01-24 15:53:53.092094', 1, 0, 'MERRITT S.V', NULL, NULL),
(43, 'owner@sanruth.com', '2025-01-25 12:27:32.373078', 'owner@sanruth.com', '2025-01-25 12:27:32.373078', 1, 0, 'Singe', NULL, NULL),
(44, 'owner@sanruth.com', '2025-01-25 12:28:07.356433', 'owner@sanruth.com', '2025-01-25 12:28:07.356433', 1, 1, 'Singer', NULL, NULL),
(45, 'owner@sanruth.com', '2025-01-25 12:33:00.920546', 'owner@sanruth.com', '2025-01-25 12:33:00.920546', 1, 1, 'Singer power', NULL, NULL),
(46, 'owner@sanruth.com', '2025-01-25 13:52:44.503008', 'owner@sanruth.com', '2025-01-25 13:52:44.503008', 1, 1, 'SIMA', NULL, NULL),
(47, 'owner@sanruth.com', '2025-01-27 10:39:01.501974', 'owner@sanruth.com', '2025-01-27 10:39:01.501974', 1, 0, 'DEV OVERLOCK', NULL, NULL),
(48, 'owner@sanruth.com', '2025-01-27 16:44:54.225456', 'owner@sanruth.com', '2025-01-27 16:44:54.225456', 1, 0, 'MONA BAG CLOSER', NULL, NULL),
(49, 'owner@sanruth.com', '2025-01-28 10:51:27.713426', 'owner@sanruth.com', '2025-01-28 10:51:27.713426', 1, 0, 'PADAM BAG CLOSER', NULL, NULL),
(50, 'owner@sanruth.com', '2025-01-28 11:13:45.133075', 'owner@sanruth.com', '2025-01-28 11:13:45.133075', 1, 0, 'POINEER S.V', NULL, NULL);

--
-- Table structure for table `machineimage`
--

DROP TABLE IF EXISTS `machineimage`;
CREATE TABLE `machineimage` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `base64Value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `machinetype`
--

DROP TABLE IF EXISTS `machinetype`;
CREATE TABLE `machinetype` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_4yl49ytqdyn3xqach1n5uqcs7` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `model`
--

DROP TABLE IF EXISTS `model`;
CREATE TABLE `model` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `model` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_rnt7dvqqcy5rm8oa4cp32bk11` (`model`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf32;

--
-- Dumping data for table `model`
--

INSERT INTO `model` (`id`, `createdBy`, `createdOn`, `modifiedBy`, `modifiedOn`, `model`) VALUES
(1, 'owner@sanruth.com', '2024-02-22 19:22:09.727410', 'owner@sanruth.com', '2024-02-22 19:22:09.727410', 'SV'),
(3, 'owner@sanruth.com', '2024-02-22 20:04:59.633922', 'owner@sanruth.com', '2024-02-22 20:04:59.633922', 'TA1'),
(6, 'owner@sanruth.com', '2024-02-23 09:50:55.348182', 'owner@sanruth.com', '2025-01-18 09:26:12.735910', 'POWER'),
(7, 'owner@sanruth.com', '2024-02-23 13:00:19.175720', 'owner@sanruth.com', '2024-02-23 13:00:19.175720', 'BELT'),
(8, 'owner@sanruth.com', '2024-02-23 13:24:27.571587', 'owner@sanruth.com', '2024-02-23 13:24:27.571587', 'NEEDLES'),
(9, 'owner@sanruth.com', '2024-02-23 13:25:14.438934', 'owner@sanruth.com', '2024-02-23 13:25:14.438934', 'THREAT'),
(10, 'owner@sanruth.com', '2024-02-23 13:26:19.329779', 'owner@sanruth.com', '2024-02-23 13:26:19.329779', 'TABLE SPARES'),
(11, 'owner@sanruth.com', '2024-02-23 13:27:09.872539', 'owner@sanruth.com', '2024-02-23 13:27:09.872539', 'REGULATOR SPARES'),
(12, 'owner@sanruth.com', '2024-02-23 13:27:25.900073', 'owner@sanruth.com', '2024-02-23 13:27:25.900073', 'MOTOR SPARES'),
(13, 'owner@sanruth.com', '2024-02-23 13:42:51.498423', 'owner@sanruth.com', '2024-02-23 13:42:51.498423', 'BAG CLOSER '),
(14, 'owner@sanruth.com', '2024-02-23 13:52:44.222146', 'owner@sanruth.com', '2024-02-23 13:52:44.222146', 'OIL'),
(15, 'owner@sanruth.com', '2024-02-23 14:56:36.552619', 'owner@sanruth.com', '2024-02-23 14:56:36.552619', 'SCISSORS'),
(16, 'owner@sanruth.com', '2024-02-28 13:41:14.600450', 'owner@sanruth.com', '2025-01-18 09:27:03.496824', '31K'),
(17, 'owner@sanruth.com', '2024-03-16 09:08:54.830908', 'owner@sanruth.com', '2025-01-18 09:27:31.205467', 'OVERLOCK POWER'),
(18, 'owner@sanruth.com', '2024-12-27 18:22:24.250700', 'owner@sanruth.com', '2025-01-20 12:12:06.390225', 'ESR BAG CLOSER SPARES'),
(19, 'owner@sanruth.com', '2025-01-11 18:51:37.128254', 'owner@sanruth.com', '2025-01-18 09:28:43.933108', 'ZIGZAG'),
(20, 'owner@sanruth.com', '2025-01-15 18:29:26.695318', 'owner@sanruth.com', '2025-01-15 18:29:26.695318', 'LED LIGHT'),
(21, 'owner@sanruth.com', '2025-01-16 13:47:18.828870', 'owner@sanruth.com', '2025-01-16 13:47:18.828870', 'SCREWS'),
(22, 'owner@sanruth.com', '2025-01-18 11:01:25.512627', 'owner@sanruth.com', '2025-01-18 11:01:25.512627', 'ACCESSORIES'),
(23, 'owner@sanruth.com', '2025-01-18 12:22:57.247093', 'owner@sanruth.com', '2025-01-18 12:22:57.247093', 'CUTTING MACHINES'),
(24, 'owner@sanruth.com', '2025-01-18 13:09:18.666214', 'owner@sanruth.com', '2025-01-18 13:09:18.666214', 'CUTTING MACHINES SPARES'),
(25, 'owner@sanruth.com', '2025-01-20 10:27:32.215212', 'owner@sanruth.com', '2025-01-20 10:27:32.215212', 'BAG CLOSER MACHINES'),
(26, 'owner@sanruth.com', '2025-01-20 14:19:31.619378', 'owner@sanruth.com', '2025-01-20 14:19:31.619378', 'OVER LOCK SPARES'),
(27, 'owner@sanruth.com', '2025-01-22 11:42:49.885282', 'owner@sanruth.com', '2025-01-22 11:42:49.885282', 'SCREW DRIVER'),
(28, 'owner@sanruth.com', '2025-06-16 13:29:20.117827', 'owner@sanruth.com', '2025-06-16 13:29:20.117827', 'KAJA'),
(29, 'owner@sanruth.com', '2025-12-10 14:10:58.325195', 'owner@sanruth.com', '2025-12-10 14:10:58.325195', 'EMBRODERY MACHINE'),
(30, 'System Admin', '2026-08-29 17:46:52.000000', 'System Admin', '2026-08-29 17:46:52.000000', 'hii');

--
-- Table structure for table `nextsequence`
--

DROP TABLE IF EXISTS `nextsequence`;
CREATE TABLE `nextsequence` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `generateYearWise` bit(1) NOT NULL,
  `minChars` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `prefix` varchar(255) DEFAULT NULL,
  `value` bigint NOT NULL,
  `year` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_c7kt9wmimum923ym7rdk04b5x` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `onsitejob`
--

DROP TABLE IF EXISTS `onsitejob`;
CREATE TABLE `onsitejob` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `issueDetails` varchar(255) DEFAULT NULL,
  `jobStatus` varchar(255) DEFAULT NULL,
  `machineSerialNo` varchar(255) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `trackingNo` varchar(255) DEFAULT NULL,
  `customer` bigint DEFAULT NULL,
  `employee` bigint DEFAULT NULL,
  `jobCard` bigint DEFAULT NULL,
  `machine` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_klct3mt00d22boi3ulicxhm7t` (`jobCard`),
  KEY `FK2kvbfj437pevunb6yyrvvoa6k` (`customer`),
  KEY `FK376sfws54tnwsk9j48ar2h9k5` (`employee`),
  KEY `FKb9ahh4hgtxtpta7sb55mplmsv` (`machine`),
  CONSTRAINT `FK2kvbfj437pevunb6yyrvvoa6k` FOREIGN KEY (`customer`) REFERENCES `customer` (`id`),
  CONSTRAINT `FK376sfws54tnwsk9j48ar2h9k5` FOREIGN KEY (`employee`) REFERENCES `employee` (`id`),
  CONSTRAINT `FKb9ahh4hgtxtpta7sb55mplmsv` FOREIGN KEY (`machine`) REFERENCES `machine` (`id`),
  CONSTRAINT `FKp4a8unpuuowvp46b8rnkwrl2p` FOREIGN KEY (`jobCard`) REFERENCES `jobcard` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `payment`
--

DROP TABLE IF EXISTS `payment`;
CREATE TABLE `payment` (
  `id` varchar(255) NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `amount` float NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `inward` bit(1) NOT NULL,
  `mode` varchar(255) DEFAULT NULL,
  `refNo` varchar(255) DEFAULT NULL,
  `transactionDate` date DEFAULT NULL,
  `sales` bigint DEFAULT NULL,
  `purchase` bigint DEFAULT NULL,
  `jobCard` bigint DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`id`, `createdBy`, `createdOn`, `modifiedBy`, `modifiedOn`, `amount`, `category`, `inward`, `mode`, `refNo`, `transactionDate`, `sales`, `purchase`, `jobCard`) VALUES
('PAY6a92c6c9562da', 'admin', '2026-08-29 17:17:21.000000', 'admin', '2026-08-29 17:17:21.000000', 1500, 'PURCHASE', 0, 'Cash', 4444444433333332, '2026-08-29', NULL, 2, NULL),
('PAY6a92db774824e', 'admin', '2026-08-29 18:45:35.000000', 'admin', '2026-08-29 18:45:35.000000', 2100, 'PURCHASE', 0, 'Cash', 8888888888888, '2026-08-29', NULL, 3, NULL),
('PAY6a92dded697b0', 'System Admin', '2026-08-29 18:56:05.000000', 'System Admin', '2026-08-29 18:56:05.000000', 1500, 'Sales', 1, 'Cash', NULL, '2026-08-29', 2, NULL, NULL),
('PAY6a92dded6ac63', 'System Admin', '2026-08-29 18:56:05.000000', 'System Admin', '2026-08-29 18:56:05.000000', 1500, 'Sales', 1, 'Cash', NULL, '2026-08-29', 1, NULL, NULL),
('PAY6a92dded6baaa', 'System Admin', '2026-08-29 18:56:05.000000', 'System Admin', '2026-08-29 18:56:05.000000', 1500, 'Sales', 1, 'Cash', NULL, '2026-08-29', NULL, NULL, NULL);

--
-- Table structure for table `purchase`
--

DROP TABLE IF EXISTS `purchase`;
CREATE TABLE `purchase` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `actualAmountSum` float NOT NULL,
  `orderDate` date DEFAULT NULL,
  `orderNo` varchar(255) DEFAULT NULL,
  `orderStatus` varchar(255) DEFAULT NULL,
  `paidAmountSum` float NOT NULL,
  `quoteAmountSum` float NOT NULL,
  `supplier` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FKikg2365x5p0ctt5j10o55mh6n` (`supplier`),
  CONSTRAINT `FKikg2365x5p0ctt5j10o55mh6n` FOREIGN KEY (`supplier`) REFERENCES `supplier` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf32;

--
-- Dumping data for table `purchase`
--

INSERT INTO `purchase` (`id`, `createdBy`, `createdOn`, `modifiedBy`, `modifiedOn`, `actualAmountSum`, `orderDate`, `orderNo`, `orderStatus`, `paidAmountSum`, `quoteAmountSum`, `supplier`) VALUES
(2, 'admin', '2026-08-29 17:17:21.000000', 'admin', '2026-08-29 17:17:21.000000', 0, '2026-08-29', 'PO202600001', 'New', 1500, 0, 1),
(3, 'admin', '2026-08-29 18:45:35.000000', 'admin', '2026-08-29 18:45:35.000000', 0, '2026-08-29', 'PO202600002', 'Received', 2100, 0, 2),
(4, 'System Admin', '2026-08-29 18:56:05.000000', 'System Admin', '2026-08-29 18:56:05.000000', 2000, '2026-08-29', 'PO-20260829-003', 'Received', 2000, 2000, 1),
(5, 'System Admin', '2026-08-29 18:56:05.000000', 'System Admin', '2026-08-29 18:56:05.000000', 2000, '2026-08-29', 'PO-20260829-004', 'Received', 2000, 2000, 1),
(6, 'System Admin', '2026-08-29 18:56:05.000000', 'System Admin', '2026-08-29 18:56:05.000000', 2000, '2026-08-29', 'PO-20260829-005', 'Received', 2000, 2000, 1);

--
-- Table structure for table `purchaseitems`
--

DROP TABLE IF EXISTS `purchaseitems`;
CREATE TABLE `purchaseitems` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `deleted` bit(1) NOT NULL,
  `gstPercentage` float NOT NULL,
  `gstValue` float NOT NULL,
  `itemName` varchar(255) DEFAULT NULL,
  `orderedQuantity` int NOT NULL,
  `receivedQuantity` int NOT NULL,
  `sellingPricePerQtyWOGSt` float NOT NULL,
  `totalPriceWithGst` float NOT NULL,
  `totalPriceWithoutGst` float NOT NULL,
  `brand` bigint DEFAULT NULL,
  `machine` bigint DEFAULT NULL,
  `model` bigint DEFAULT NULL,
  `spare` bigint DEFAULT NULL,
  `purchase` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FKdqjd5p7se2twxxetscav6mjkq` (`brand`),
  KEY `FK3n0y31ts5kx8rq4iryy2gah2l` (`machine`),
  KEY `FKljl6fid801qifaqkmbf348n7r` (`model`),
  KEY `FKhns7p9cmfxfxlp6egjuhf8wqs` (`spare`),
  KEY `FK3joimr4evmw7yuxmrgo4elk4` (`purchase`),
  CONSTRAINT `FK3joimr4evmw7yuxmrgo4elk4` FOREIGN KEY (`purchase`) REFERENCES `purchase` (`id`),
  CONSTRAINT `FK3n0y31ts5kx8rq4iryy2gah2l` FOREIGN KEY (`machine`) REFERENCES `machine` (`id`),
  CONSTRAINT `FKdqjd5p7se2twxxetscav6mjkq` FOREIGN KEY (`brand`) REFERENCES `brand` (`id`),
  CONSTRAINT `FKhns7p9cmfxfxlp6egjuhf8wqs` FOREIGN KEY (`spare`) REFERENCES `spares` (`id`),
  CONSTRAINT `FKljl6fid801qifaqkmbf348n7r` FOREIGN KEY (`model`) REFERENCES `model` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf32;

--
-- Dumping data for table `purchaseitems`
--

INSERT INTO `purchaseitems` (`id`, `createdBy`, `createdOn`, `modifiedBy`, `modifiedOn`, `deleted`, `gstPercentage`, `gstValue`, `itemName`, `orderedQuantity`, `receivedQuantity`, `sellingPricePerQtyWOGSt`, `totalPriceWithGst`, `totalPriceWithoutGst`, `brand`, `machine`, `model`, `spare`, `purchase`) VALUES
(3, 'admin', '2026-08-29 17:17:21.000000', 'admin', '2026-08-29 17:17:21.000000', 0, 0, 0, '8\"KNIEF', 2, 1, 0, 0, 0, 2, NULL, 24, 625, 2),
(4, 'admin', '2026-08-29 17:17:21.000000', 'admin', '2026-08-29 17:17:21.000000', 0, 0, 0, 'ALLEN BOLT 3MM', 3, 2, 0, 0, 0, 2, NULL, 21, 195, 2),
(5, 'admin', '2026-08-29 18:45:35.000000', 'admin', '2026-08-29 18:45:35.000000', 0, 0.04, 0, 'ARMATURE 1/12', 9, 2, 0.02, 0, 0, 2, NULL, 14, 115, 3),
(6, 'admin', '2026-08-29 18:45:35.000000', 'admin', '2026-08-29 18:45:35.000000', 0, 0, 0, 'ARMATURE 1/12', 3, 3, 0, 0, 0, 2, NULL, 12, 115, 3),
(7, 'System Admin', '2026-08-29 18:56:05.000000', 'System Admin', '2026-08-29 18:56:05.000000', 0, 25, 400, 'Sample Spare Stock Part #1', 5, 5, 320, 2000, 1600, 1, NULL, 1, 1, 4);

--
-- Table structure for table `purchaseitemstock`
--

DROP TABLE IF EXISTS `purchaseitemstock`;
CREATE TABLE `purchaseitemstock` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `noOfAvailableItems` int NOT NULL,
  `noOfReceivedItems` int NOT NULL,
  `noOfSaledItems` int NOT NULL,
  `pricePerQty` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `reorderlevel`
--

DROP TABLE IF EXISTS `reorderlevel`;
CREATE TABLE `reorderlevel` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `itemName` varchar(255) DEFAULT NULL,
  `minUnit` int NOT NULL,
  `brand` bigint DEFAULT NULL,
  `machine` bigint DEFAULT NULL,
  `model` bigint DEFAULT NULL,
  `spare` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FKgyc4bdu72sr6bl61il5wvnaye` (`brand`),
  KEY `FKc6v9rw4gixs5q3abh2a88uwpb` (`machine`),
  KEY `FK1d31ey9n574g3gjl1j6fwrb7j` (`model`),
  KEY `FK5cfpppwbfu27viv74xucddq6w` (`spare`),
  CONSTRAINT `FK1d31ey9n574g3gjl1j6fwrb7j` FOREIGN KEY (`model`) REFERENCES `model` (`id`),
  CONSTRAINT `FK5cfpppwbfu27viv74xucddq6w` FOREIGN KEY (`spare`) REFERENCES `spares` (`id`),
  CONSTRAINT `FKc6v9rw4gixs5q3abh2a88uwpb` FOREIGN KEY (`machine`) REFERENCES `machine` (`id`),
  CONSTRAINT `FKgyc4bdu72sr6bl61il5wvnaye` FOREIGN KEY (`brand`) REFERENCES `brand` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
CREATE TABLE `sales` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `actualAmountSum` float NOT NULL,
  `orderDate` date DEFAULT NULL,
  `orderNo` varchar(255) DEFAULT NULL,
  `orderStatus` varchar(255) DEFAULT NULL,
  `paidAmountSum` float NOT NULL,
  `customer` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FKt3sxcyawyfdjcr7wanolo98so` (`customer`),
  CONSTRAINT `FKt3sxcyawyfdjcr7wanolo98so` FOREIGN KEY (`customer`) REFERENCES `customer` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf32;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `createdBy`, `createdOn`, `modifiedBy`, `modifiedOn`, `actualAmountSum`, `orderDate`, `orderNo`, `orderStatus`, `paidAmountSum`, `customer`) VALUES
(1, 'hari', '2026-08-29 16:39:00.734540', 'hari', '2026-08-29 16:39:00.734540', 100, '2026-08-29', 202600001, 'New', 0, 1),
(2, 'admin', '2026-08-29 17:04:01.881481', 'admin', '2026-08-29 17:04:01.881481', 120, '2026-08-29', 202600002, 'Invoiced', 0, 1);

--
-- Table structure for table `salesitems`
--

DROP TABLE IF EXISTS `salesitems`;
CREATE TABLE `salesitems` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `deleted` bit(1) NOT NULL,
  `gstPercentage` float NOT NULL,
  `gstValue` float NOT NULL,
  `itemName` varchar(255) DEFAULT NULL,
  `pricePerQty` float NOT NULL,
  `quantity` int NOT NULL,
  `serialNo` varchar(255) DEFAULT NULL,
  `totalPrice` float NOT NULL,
  `machine` bigint DEFAULT NULL,
  `spare` bigint DEFAULT NULL,
  `stock` varchar(255) DEFAULT NULL,
  `sales` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FKjnb1apgqbbekyalao9g0k39xy` (`machine`),
  KEY `FKgiesap1p06s6juylbni1rqnb0` (`spare`),
  KEY `FKp2olmuodj2he18veht4qj1o6b` (`stock`),
  KEY `FK3oqcphfy6eav6e7wtqnusgevt` (`sales`),
  CONSTRAINT `FK3oqcphfy6eav6e7wtqnusgevt` FOREIGN KEY (`sales`) REFERENCES `sales` (`id`),
  CONSTRAINT `FKgiesap1p06s6juylbni1rqnb0` FOREIGN KEY (`spare`) REFERENCES `spares` (`id`),
  CONSTRAINT `FKjnb1apgqbbekyalao9g0k39xy` FOREIGN KEY (`machine`) REFERENCES `machine` (`id`),
  CONSTRAINT `FKp2olmuodj2he18veht4qj1o6b` FOREIGN KEY (`stock`) REFERENCES `stock` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf32;

--
-- Dumping data for table `salesitems`
--

INSERT INTO `salesitems` (`id`, `createdBy`, `createdOn`, `modifiedBy`, `modifiedOn`, `deleted`, `gstPercentage`, `gstValue`, `itemName`, `pricePerQty`, `quantity`, `serialNo`, `totalPrice`, `machine`, `spare`, `stock`, `sales`) VALUES
(1, 'hari', '2026-08-29 16:39:00.734540', 'hari', '2026-08-29 16:39:00.734540', 0, 0, 0, 'PRESSURE FOOT P351 SUSEI', 100, 1, 'P069', 100, NULL, 533, 'caa40a5b-b194-440f-8384-3e0b734e7768', 1),
(2, 'admin', '2026-08-29 17:04:01.881481', 'admin', '2026-08-29 17:04:01.881481', 0, 0, 0, 'BEARING GRIP WASER', 0, 1, 'M010', 0, NULL, 234, 'a16dbaf7-588e-463a-b239-ea6680003eca', 2),
(3, 'admin', '2026-08-29 17:04:01.881481', 'admin', '2026-08-29 17:04:01.881481', 0, 0, 0, '8 ROLLER PM', 120, 1, 'P001', 120, NULL, 478, '20ddfad3-a75a-4483-bbd8-eaf3b0812458', 2);

--
-- Table structure for table `screen`
--

DROP TABLE IF EXISTS `screen`;
CREATE TABLE `screen` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `screenName` varchar(255) NOT NULL,
  `screenPath` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_215vb1n50d9vn50pucg438uij` (`screenName`),
  UNIQUE KEY `UK_mcwri881l9i6yo06h33ecth79` (`screenPath`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `screenaccess`
--

DROP TABLE IF EXISTS `screenaccess`;
CREATE TABLE `screenaccess` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `allowToCreate` bit(1) NOT NULL,
  `allowToDelete` bit(1) NOT NULL,
  `allowToFixPurchasePrice` bit(1) NOT NULL,
  `allowToFixSalesPrice` bit(1) NOT NULL,
  `allowToPay` bit(1) NOT NULL,
  `allowToUpdate` bit(1) NOT NULL,
  `allowToView` bit(1) NOT NULL,
  `role` varchar(255) DEFAULT NULL,
  `screen` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FKd41bc4bwffr4bc2taoqry0n37` (`screen`),
  CONSTRAINT `FKd41bc4bwffr4bc2taoqry0n37` FOREIGN KEY (`screen`) REFERENCES `screen` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `spareimage`
--

DROP TABLE IF EXISTS `spareimage`;
CREATE TABLE `spareimage` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `base64Value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `spares`
--

DROP TABLE IF EXISTS `spares`;
CREATE TABLE `spares` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `active` bit(1) NOT NULL,
  `partNo` varchar(255) DEFAULT NULL,
  `picture` varchar(255) DEFAULT NULL,
  `rackNumber` varchar(255) DEFAULT NULL,
  `spareName` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1008 DEFAULT CHARSET=utf32;

--
-- Dumping data for table `spares`
--

INSERT INTO `spares` (`id`, `createdBy`, `createdOn`, `modifiedBy`, `modifiedOn`, `active`, `partNo`, `picture`, `rackNumber`, `spareName`) VALUES
(1, 'owner@sanruth.com', '2024-02-22 19:21:30.306717', 'owner@sanruth.com', '2025-01-15 10:57:03.624002', 1, 'S', NULL, 86, 'zzz'),
(2, 'owner@sanruth.com', '2024-02-22 19:28:37.047129', 'owner@sanruth.com', '2025-01-15 17:00:33.566760', 1, 'S003', 'Spare/2/21778087114042cd8790745cd6cb7423.jpg', 88, 'BOBBIN CASE (HAYA)-SV'),
(3, 'owner@sanruth.com', '2024-02-22 19:33:39.455051', 'owner@sanruth.com', '2025-01-13 12:30:03.607873', 1, 'S013', 'Spare/3/5efeb26c3412401b865f753775ac4c0f.jpg', 27, 'DRIVER ROD BRACKET -S.V'),
(4, 'owner@sanruth.com', '2024-02-22 19:38:24.342973', 'owner@sanruth.com', '2024-02-22 19:38:24.513243', 1, 'S007', 'Spare/4/c2678540396b4cd195b869362950c43e.jpg', 53, 'CAP SCREW SV'),
(5, 'owner@sanruth.com', '2024-02-22 19:39:03.838073', 'owner@sanruth.com', '2024-02-22 19:39:03.978326', 1, 'S008', 'Spare/5/8a5969fab2ea425a85fb7170138eaa73.jpg', 23, 'CHALL NUT (SV)'),
(6, 'owner@sanruth.com', '2024-02-22 19:40:51.262442', 'owner@sanruth.com', '2025-01-15 11:34:14.274687', 1, 'S', NULL, 2000, 'ZZ'),
(7, 'owner@sanruth.com', '2024-02-22 19:55:28.563142', 'owner@sanruth.com', '2024-02-22 19:55:28.563142', 1, 'S16', NULL, 14, 'FACE PLATE (SV)'),
(8, 'owner@sanruth.com', '2024-02-22 19:56:18.513972', 'owner@sanruth.com', '2025-01-15 11:50:05.239465', 1, 'S', NULL, 2000, 'ZZZ'),
(9, 'owner@sanruth.com', '2024-02-22 19:57:10.664633', 'owner@sanruth.com', '2024-02-22 19:57:10.664633', 1, 'S24', NULL, 41, 'NEEDLE CLAMP SV '),
(10, 'owner@sanruth.com', '2024-02-22 19:58:30.250391', 'owner@sanruth.com', '2025-01-13 12:37:16.276573', 1, 'S027', 'Spare/10/ab3124d6dd364cf0912e61c1fb92a020.jpg', 84, 'NEEDLE PLATE DULL'),
(11, 'owner@sanruth.com', '2024-02-22 19:59:25.812048', 'owner@sanruth.com', '2025-01-15 15:18:57.715006', 1, 'S033', 'Spare/11/c853d34f019f42f09525d263deaa3dca.jpg', 82, 'PRESSURE FOOT SV'),
(12, 'owner@sanruth.com', '2024-02-22 19:59:58.868253', 'owner@sanruth.com', '2025-01-15 15:20:22.954235', 1, 'S034', 'Spare/12/255102be141840c7b43dd68dc783f403.jpg', 54, 'PRESSURE SPRING SV'),
(13, 'owner@sanruth.com', '2024-02-22 20:00:29.109198', 'owner@sanruth.com', '2025-01-15 15:21:39.766187', 1, 'S037', NULL, 56, 'Z SIDE PLATE SV'),
(14, 'owner@sanruth.com', '2024-02-22 20:00:57.326521', 'owner@sanruth.com', '2025-01-15 12:17:08.327101', 1, 00, NULL, 00, 'ZZZSPOOL '),
(15, 'owner@sanruth.com', '2024-02-22 20:01:47.530906', 'owner@sanruth.com', '2025-01-15 15:32:32.207640', 1, 'S050', NULL, 'S048', 'Z TEETH SV'),
(16, 'owner@sanruth.com', '2024-02-22 20:02:19.036694', 'owner@sanruth.com', '2025-01-13 13:04:22.435998', 1, 'S053', 'Spare/16/8c7bd4b8dc904437afcdad8d932bec88.jpg', 85, 'TENSION SET SV'),
(17, 'owner@sanruth.com', '2024-02-22 20:04:06.096260', 'owner@sanruth.com', '2025-01-15 17:00:12.220637', 1, 'T005', 'Spare/17/ed45843ea38d4547b9156b0fdf57126e.jpg', 91, 'BOBBIN CASE HAYA-TA1'),
(18, 'owner@sanruth.com', '2024-02-22 20:14:18.055426', 'owner@sanruth.com', '2025-01-15 16:50:58.110605', 1, 'T013', 'Spare/18/b538cf2eafb34fb9984862ba05519841.jpg', 106, 'CITIZEN TA1'),
(19, 'owner@sanruth.com', '2024-02-22 20:15:00.254396', 'owner@sanruth.com', '2025-01-15 16:52:08.466086', 1, 'T014', 'Spare/19/6bcef9a78b2d4083a4949f77be436e4b.jpg', 119, 'CITIZEN SPRING TA1'),
(20, 'owner@sanruth.com', '2024-02-22 20:15:36.807443', 'owner@sanruth.com', '2025-01-16 11:01:23.534253', 1, 'T019', 'Spare/20/21aed5304e0148e6964be68aa3787a16.jpg', 109, 'FACE PALATE TA1'),
(21, 'owner@sanruth.com', '2024-02-22 20:17:12.905481', 'owner@sanruth.com', '2025-01-13 15:44:10.639604', 1, 'TA22', NULL, 129, 'ZZ'),
(22, 'owner@sanruth.com', '2024-02-22 20:17:49.607194', 'owner@sanruth.com', '2025-01-16 11:07:19.862037', 1, 'T026', 'Spare/22/c6d8961c00d4449fa044258a3f02a403.jpg', 97, 'HOOK SET PATHI'),
(23, 'owner@sanruth.com', '2024-02-22 20:18:24.998878', 'owner@sanruth.com', '2025-01-16 11:08:06.459837', 1, 'T027', 'Spare/23/98c4635e4bb642b2aead9d7812ce2bcd.jpg', 120, 'HOOK SET ROD TA1'),
(24, 'owner@sanruth.com', '2024-02-22 20:18:56.079313', 'owner@sanruth.com', '2025-01-16 11:12:25.986206', 1, 'T028', 'Spare/24/31a206617223461c8c7cee2246ffe2d4.jpg', 97, 'HOOK SET GTC'),
(25, 'owner@sanruth.com', '2024-02-22 20:19:48.032025', 'owner@sanruth.com', '2025-01-16 11:15:58.769647', 1, 'T030', 'Spare/25/ad9dae9d3006475ca7da2438b2a59ca2.jpg', 69, 'INDIA GATE SPRING TA1'),
(26, 'owner@sanruth.com', '2024-02-22 20:20:13.605167', 'owner@sanruth.com', '2025-01-16 11:16:33.131773', 1, 'T031', 'Spare/26/9b75cbb645694b8b8adee531c3fc1efe.jpg', 68, 'INDIA GATE TA1'),
(27, 'owner@sanruth.com', '2024-02-22 20:20:56.219702', 'owner@sanruth.com', '2025-01-16 11:19:36.553914', 1, 'T037', 'Spare/27/2c914153e6c04a7b85b17d40a9bbd9fb.jpg', 93, 'NEEDLE PLATE DULL TA1'),
(28, 'owner@sanruth.com', '2024-02-22 20:21:52.221261', 'owner@sanruth.com', '2025-01-31 15:18:07.832765', 1, 'T047', 'Spare/28/f62cd72e2d3d4fb2a1abd91b45421c72.jpg', 131, 'PRESSURE FOOT DULL TA1'),
(29, 'owner@sanruth.com', '2024-02-22 20:22:23.063988', 'owner@sanruth.com', '2025-01-16 11:37:20.543984', 1, 'T049', 'Spare/29/dcedbe643fe24c7aa0c3bb708bf0e7b0.jpg', 49, 'SAMSA PATHI TA1'),
(30, 'owner@sanruth.com', '2024-02-22 20:22:56.317874', 'owner@sanruth.com', '2025-01-16 11:42:50.090207', 1, 'T057', 'Spare/30/6f17ea1c4d7c4575b31d43e3284625d2.jpg', 96, 'TEETH TA1'),
(31, 'owner@sanruth.com', '2024-02-22 20:23:24.504589', 'owner@sanruth.com', '2025-01-16 11:44:26.353285', 1, 'T061', 'Spare/31/4ddd6a46ebcd49bc91f60af855909d8d.jpg', 130, 'TENSION TA1'),
(32, 'owner@sanruth.com', '2024-02-22 20:24:47.991258', 'owner@sanruth.com', '2025-01-16 16:22:59.062848', 1, 'P007', 'Spare/32/feb1033d01be47ae8474eba9d7a5acdc.jpg', 250, 'BOBBIN CASE HAYA Power '),
(33, 'owner@sanruth.com', '2024-02-22 20:25:18.114108', 'owner@sanruth.com', '2025-01-16 16:24:30.742425', 1, 'P008', 'Spare/33/0eedaf18bafc4b4381c6a6f7e3dcdd20.jpg', 251, 'BOBBIN CASE EDGE'),
(34, 'owner@sanruth.com', '2024-02-22 20:27:45.135722', 'owner@sanruth.com', '2025-01-16 16:27:11.606378', 1, 'P011', 'Spare/34/233895dd5afb4fd7a72c975b93f2069d.jpg', 184, 'BOBBIN WINDER SET-NEW TYPE - PM'),
(35, 'owner@sanruth.com', '2024-02-22 20:33:24.731674', 'owner@sanruth.com', '2025-01-16 16:28:18.144517', 1, 'P013', 'Spare/35/c56da0af94f34eeeb53c535335c5a01a.jpg', 202, 'CAPS SCREW PLASTIC-PM'),
(36, 'owner@sanruth.com', '2024-02-22 20:34:01.693503', 'owner@sanruth.com', '2025-01-16 16:33:17.623005', 1, 'P014', 'Spare/36/def6e4856477495baa23b4957ceb824c.jpg', 201, 'CAP SCREW SILVER-PM '),
(37, 'owner@sanruth.com', '2024-02-22 20:34:46.855382', 'owner@sanruth.com', '2025-01-18 15:56:19.252722', 1, 'P022', 'Spare/37/2fe2347ed4ab4ae38950d5538c4c7dfc.jpg', 213, 'FINISHING SPRING -PM'),
(38, 'owner@sanruth.com', '2024-02-22 20:35:26.193181', 'owner@sanruth.com', '2025-01-17 13:24:05.759015', 1, 'P034', 'Spare/38/77be502252a64019acc86803b2e78713.jpg', 266, 'NEEDLE PLATE E16'),
(39, 'owner@sanruth.com', '2024-02-22 20:36:04.554911', 'owner@sanruth.com', '2025-01-17 13:26:46.306015', 1, 'P035', 'Spare/39/d577ca30b3f04df79065ad239301fd60.jpg', 260, 'NEEDLE PLATE E18'),
(40, 'owner@sanruth.com', '2024-02-22 20:36:40.335246', 'owner@sanruth.com', '2024-02-22 20:37:20.100191', 1, 'P38', NULL, 263, 'NEEDLE PLATE E24'),
(41, 'owner@sanruth.com', '2024-02-23 10:07:40.565604', 'owner@sanruth.com', '2025-05-25 09:17:11.395086', 0, 'N004', NULL, 362, 'Z Needle 16'),
(42, 'owner@sanruth.com', '2024-02-23 10:32:59.805717', 'owner@sanruth.com', '2025-01-15 12:00:24.019184', 1, 'BE05', 'Spare/42/3907329ca20f41c2ab7e4d1ee8027702.jpg', 291, 'BELT (LEATHER)'),
(43, 'owner@sanruth.com', '2024-02-23 10:34:33.918653', 'owner@sanruth.com', '2025-01-12 08:54:34.810457', 1, 'BE31', NULL, '289/4', 'BELT 525MM'),
(44, 'owner@sanruth.com', '2024-02-23 10:36:07.988160', 'owner@sanruth.com', '2025-01-15 12:08:04.560003', 1, 'BE10', NULL, '290/3', 'ZBELT 575MM'),
(45, 'owner@sanruth.com', '2024-02-23 10:37:16.360642', 'owner@sanruth.com', '2025-01-15 12:09:52.736786', 1, 'BE10', 'Spare/45/0658c748d2034b8e8e6a9c34e37f89a6.jpg', '290/3', 'BELT 575'),
(46, 'owner@sanruth.com', '2024-02-23 10:38:16.658600', 'owner@sanruth.com', '2025-01-15 12:11:29.489143', 1, 'BE11', 'Spare/46/fd91807936354503a656c928cb0e32fd.jpg', '290/1', 'BELT 600MM'),
(47, 'owner@sanruth.com', '2024-02-23 10:38:44.604094', 'owner@sanruth.com', '2025-01-16 16:18:13.826763', 1, 'BE14', NULL, '290/5', 'BELT 700MM'),
(48, 'owner@sanruth.com', '2024-02-23 10:40:15.813058', 'owner@sanruth.com', '2025-01-13 14:18:26.363947', 1, 'Y001', 'Spare/48/78e12f6f15b4425585d501044bbf9769.jpg', 294, 'GARLON GW 3 2000M (WHITE)'),
(49, 'owner@sanruth.com', '2024-02-23 10:41:07.731540', 'owner@sanruth.com', '2025-01-13 14:18:55.795700', 1, 'Y002', 'Spare/49/dd01a5ec2e834600a32f3a5d089af179.jpg', 277, 'GARLON NW1 1000M (BLUE)'),
(50, 'owner@sanruth.com', '2024-02-23 10:42:36.610609', 'owner@sanruth.com', '2025-01-12 09:45:01.011178', 1, 'Y006', NULL, 179, 'GARLON NW 1 1000M (WHITE)');

--
-- Table structure for table `stock`
--

DROP TABLE IF EXISTS `stock`;
CREATE TABLE `stock` (
  `id` varchar(255) NOT NULL,
  `actualPricePerQty` float NOT NULL,
  `actualPricePerUnit` float NOT NULL,
  `availableQty` int NOT NULL,
  `barCode` varchar(255) DEFAULT NULL,
  `gstPercentage` float NOT NULL,
  `itemName` varchar(255) DEFAULT NULL,
  `quantity` int NOT NULL,
  `selled` bit(1) NOT NULL,
  `selledPricePerUnit` double NOT NULL,
  `sellingPricePerQty` float NOT NULL,
  `sellingPricePerUnit` float NOT NULL,
  `serialNo` varchar(255) DEFAULT NULL,
  `unit` int NOT NULL,
  `warrantyInMonths` int NOT NULL,
  `brand` bigint DEFAULT NULL,
  `machine` bigint DEFAULT NULL,
  `model` bigint DEFAULT NULL,
  `purchaseItem` bigint DEFAULT NULL,
  `spare` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK17lg7477nvywgyva1qnfan255` (`brand`),
  KEY `FKdn32ck0k6jt4dg9t7vl28ba8u` (`machine`),
  KEY `FKh2407ud8pbmwnxo4q65xftumf` (`model`),
  KEY `FKeifebnupfgukc2oojtse960ii` (`purchaseItem`),
  KEY `FK316sfaegiisdk4bcna59ynqle` (`spare`),
  CONSTRAINT `FK17lg7477nvywgyva1qnfan255` FOREIGN KEY (`brand`) REFERENCES `brand` (`id`),
  CONSTRAINT `FK316sfaegiisdk4bcna59ynqle` FOREIGN KEY (`spare`) REFERENCES `spares` (`id`),
  CONSTRAINT `FKdn32ck0k6jt4dg9t7vl28ba8u` FOREIGN KEY (`machine`) REFERENCES `machine` (`id`),
  CONSTRAINT `FKeifebnupfgukc2oojtse960ii` FOREIGN KEY (`purchaseItem`) REFERENCES `purchaseitems` (`id`),
  CONSTRAINT `FKh2407ud8pbmwnxo4q65xftumf` FOREIGN KEY (`model`) REFERENCES `model` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32;

--
-- Dumping data for table `stock`
--

INSERT INTO `stock` (`id`, `actualPricePerQty`, `actualPricePerUnit`, `availableQty`, `barCode`, `gstPercentage`, `itemName`, `quantity`, `selled`, `selledPricePerUnit`, `sellingPricePerQty`, `sellingPricePerUnit`, `serialNo`, `unit`, `warrantyInMonths`, `brand`, `machine`, `model`, `purchaseItem`, `spare`) VALUES
('0019194b-f404-48cb-a052-2913ae01dafc', 5, 125, 0, 'S872', 0, 'MOTORE BED SCREW (B.C)', 25, 1, 100, 5, 125, NULL, 1, 0, 2, NULL, 21, NULL, 212),
('00551933-1e7f-42a5-a4a9-99ab5740b902', 40, 40, 0, 'S449', 0, 'BOBBIN CASE (HAYA)', 1, 1, 40, 40, 40, '', 1, 0, 2, NULL, 1, NULL, 2),
('005edbe7-93bd-40ae-badb-254650bfefcf', 58, 3248, 53, 'T758', 0, 'CITIZEN TA1', 56, 0, 240, 60, 3360, NULL, 1, 0, 2, NULL, 3, NULL, 18),
('007715cd-05e5-49ab-8e88-a37a2d2fe7a8', 290, 7250, 15, 'E003', 0, 'CONNECTING ROD ESR', 25, 0, 9600, 300, 7500, NULL, 1, 0, 2, NULL, 18, NULL, 688),
('007f1171-9022-49ef-b220-4fc4fa9e38ea', 9, 1656, 180, 'T808', 0, 'THREE HOLES PATHI-TA1', 184, 0, 120, 10, 1840, NULL, 1, 0, 2, NULL, 3, NULL, 421),
('00915efb-2e27-4996-aff9-8a9d05eac373', 18, 18, 0, 'T824', 0, 'CAPS SCREW -TA1', 1, 1, 20, 20, 20, NULL, 1, 0, 2, NULL, 3, NULL, 378),
('009e2161-a949-46c3-ab27-3b3e32152637', 48, 1200, 17, 'p222', 0, 'TENSION SET-BABY-CUTTER-POWER', 25, 0, 1670, 50, 1250, NULL, 1, 0, 2, NULL, 6, NULL, 860),
('00adb835-5138-4206-82fe-8a90bd56df9c', 40, 40, 0, 'T546', 0, 'TENSION TA1', 1, 1, 40, 40, 40, '', 1, 0, 2, NULL, 3, NULL, 31),
('00bcc7fa-d459-423d-bc26-004f5ab38b65', 10, 10, 0, 'S545', 0, 'NEEDLE CLAMP SV ', 1, 1, 10, 10, 10, '', 1, 0, 2, NULL, 1, NULL, 9),
('00c227de-e229-40a4-ad23-573d99025a2b', 63, 1575, 24, 'N123', 0, 'DV-9(BEISSEL)', 25, 0, 65, 65, 1625, NULL, 1, 0, 2, NULL, 8, NULL, 651),
('00f07b39-af7f-4b85-8ca5-00600f9965b1', 70, 70, 0, 'O188', 0, 'OIL 500 ML', 1, 1, 70, 70, 70, '', 1, 0, 2, NULL, 14, NULL, 133),
('00f66f14-a36d-4e1f-a5a8-15b09af5fcf8', 0, 0, 1, 'S1039', 0, 'DRIVER (S.V)', 1, 0, 0, 0, 0, NULL, 1, 0, 1, NULL, 1, NULL, 155),
('015e551e-a830-4849-92aa-0769bbf2f7c9', 48, 48, 0, 'p366', 0, 'PRESSURE FOOT P351 GTC', 1, 1, 40, 50, 50, NULL, 1, 0, 2, NULL, 6, NULL, 523),
('01a8eb2b-217f-4d03-a592-f4011e65c636', 75, 1875, 24, 'S850', 0, 'SHUTTLE RACE RING', 25, 0, 80, 80, 2000, NULL, 1, 0, 2, NULL, 1, NULL, 587),
('01b0059a-33f5-4de3-bb50-d49f4964e8e3', 448, 11200, 22, 'T868', 0, 'HOOK SET(MING JING)', 25, 0, 9300, 450, 11250, NULL, 1, 0, 2, NULL, 3, NULL, 782),
('02178652-0581-4322-b2b6-7b3701c1d4e0', 5, 5, 0, 'S456', 0, 'BOBBIN WINDER RUBBER', 1, 1, 5, 5, 5, '', 1, 0, 2, NULL, 1, NULL, 3),
('022d8b0b-1f3c-4118-a8f1-1b42f71d7ace', 68, 6800, 0, 'T1065', 0, 'GARLON NW1 1000M (BLUE)', 100, 1, 7610, 70, 7000, NULL, 1, 0, 2, NULL, 9, NULL, 49),
('023f4786-cd6a-46b7-9018-227662832574', 4, 200, 9, 'S907', 0, 'TABLE SCREW ', 50, 0, 100, 5, 250, NULL, 1, 0, 2, NULL, 21, NULL, 226),
('024909ac-882c-4a63-9446-f5cbe7d9b283', 0, 0, 0, 'S1022', 0, 'LIFTER', 1, 1, 30, 0, 0, NULL, 1, 0, 1, NULL, 1, NULL, 440),
('02b90fe8-a820-4621-9d1b-9134f0a9726b', 40, 40, 0, 'T581', 0, 'TENSION TA1', 1, 1, 40, 40, 40, '', 1, 0, 2, NULL, 3, NULL, 31),
('02d838eb-2298-40ae-bb4d-1c9f977a0ac1', 5, 125, 25, 'R024', 0, 'DIODE', 25, 0, 0, 5, 125, NULL, 1, 0, 2, NULL, 11, NULL, 768),
('02dcc404-60ae-45c3-a2ad-365a73f6aeaf', 120, 120, 0, 'O010', 0, 'OIL 1000ML', 1, 1, 120, 120, 120, '', 1, 0, 2, NULL, 14, NULL, 132),
('02edd54b-4abe-420a-a43a-4468ad4040c8', 40, 40, 0, 'T645', 0, 'TENSION TA1', 1, 1, 40, 40, 40, '', 1, 0, 2, NULL, 3, NULL, 31),
('03162816-0ade-4d97-99c5-fc6602b39563', 2298, 4596, 0, 'S924', 0, 'ELESTRIC  SCISSOR', 2, 1, 4600, 2300, 4600, NULL, 1, 0, 2, NULL, 15, NULL, 962),
('0343f02c-70ca-418a-9c0c-86715e402d1b', 8, 200, 20, 'S798', 0, 'CHALL NUT (SV)', 25, 0, 60, 10, 250, NULL, 1, 0, 2, NULL, 1, NULL, 5),
('0349e2d4-f93a-423d-8e37-0d420e9554c6', 4.5, 450, 0, 'R036', 0, 'PATHI (SMALL COPPER)', 100, 1, 840, 5, 500, NULL, 1, 0, 2, NULL, 11, NULL, 111),
('035508eb-9efa-406a-a843-9105191a8625', 0, 0, 1, 'T1097', 0, 'PRESSURE BAR BRACKET SMALL-TA1', 1, 0, 0, 0, 0, NULL, 1, 0, 2, NULL, 3, NULL, 409),
('035f6900-86eb-4e43-aa8b-80ad2e99b3a7', 80, 80, 0, 'S499', 0, 'FACE PLATE (SV)', 1, 1, 80, 80, 80, '', 1, 0, 2, NULL, 1, NULL, 7),
('0385b85b-0e36-480c-81bb-95d5c79ecb89', 48, 4800, 28, 'p383', 0, 'BOBBIN CASE HAYA Power ', 100, 0, 15630, 50, 5000, NULL, 1, 0, 2, NULL, 6, NULL, 32),
('038f89c7-188d-4be1-aafd-9c8178ec9d86', 120, 120, 0, 'O015', 0, 'OIL 1000ML', 1, 1, 120, 120, 120, '', 1, 0, 2, NULL, 14, NULL, 132),
('03c0fa2a-b36e-450f-a9f4-346cffc60b50', 0, 0, 1, 'S1017', 0, 'PRESURE FOOT LIFT-SV', 1, 0, 0, 0, 0, NULL, 1, 0, 1, NULL, 1, NULL, 445),
('03e2dd4a-efab-4f61-a24b-78d7db4a1295', 98, 2450, 5, 'O219', 0, 'OIL(B.C)', 25, 0, 7810, 100, 2500, NULL, 1, 0, 2, NULL, 14, NULL, 666),
('03e4a18e-3e22-4388-ae6c-887a583e7650', 58, 1450, 13, 'M053', 0, 'WIRE MALE w/o CARBON', 25, 0, 3440, 60, 1500, NULL, 1, 0, 2, NULL, 12, NULL, 281),
('03f72a9f-4014-4ce4-aa67-b9d61cb32044', 38, 38, 0, 'T976', 0, 'HOOK SET ROD TA1', 1, 1, 40, 40, 40, NULL, 1, 0, 2, NULL, 3, NULL, 23),
('040da6d3-da74-47e1-89e0-0e5f1747acfd', 0, 0, 1, 'p407', 0, 'TENSION SET-PM SMALL ', 1, 0, 0, 100, 100, NULL, 1, 0, 2, NULL, 6, NULL, 552),
('0450d37e-4bea-4930-98a9-1f9ed2e7f119', 448, 2240, 0, 'S909', 0, 'SCISSOR 10 INCH BOLT', 5, 1, 1950, 450, 2250, NULL, 1, 0, 2, NULL, 15, NULL, 929),
('04684cb8-6504-4b65-bcb4-df374989bece', 148, 3700, 25, 'O265', 0, 'LOOPER-KL202 POWER O/L', 25, 0, 0, 150, 3750, NULL, 1, 0, 2, NULL, 17, NULL, 756),
('047cbd8a-44f5-42bc-9c23-14c925d44701', 5, 705, 131, 'T812', 0, 'TEETH ADJUST SCREW', 141, 0, 75, 5, 705, NULL, 1, 0, 2, NULL, 3, NULL, 426),
('04c9cc60-439f-4361-8811-f8b2f0d8655b', 10, 250, 0, 'S888', 0, 'STAND BOLT', 25, 1, 100, 10, 250, NULL, 1, 0, 2, NULL, 21, NULL, 228),
('04e38fbb-4679-49fe-ba3b-d4f5a0c4584a', 63, 1575, 22, 'N111', 0, 'DV 14 (FLAT LOCK)', 25, 0, 180, 65, 1625, NULL, 1, 0, 2, NULL, 8, NULL, 98),
('04ef62e9-02ca-4ffb-ba3b-6daf52e06953', 9, 225, 0, 'M010', 0, 'CARBON BRUSH (S.V)', 25, 1, 840, 10, 250, NULL, 1, 0, 2, NULL, 12, NULL, 117),
('04fd40ff-a5c8-49a1-b993-0c6a535c7fa1', 40, 40, 0, 'T494', 0, 'NEEDLE PLATE DULL TA1', 1, 1, 40, 40, 40, '', 1, 0, 2, NULL, 3, NULL, 27),
('050a2f8f-d8d4-4b85-b6b7-9cf61e391492', 65, 130, 0, 'T861', 0, 'GARLON NWI 1000M ( PINK)', 2, 1, 140, 70, 140, NULL, 1, 0, 9, NULL, 9, NULL, 59),
('051282da-2af5-40cb-87eb-e9a37377debd', 120, 120, 0, 'O050', 0, 'OIL 1000ML', 1, 1, 120, 120, 120, '', 1, 0, 2, NULL, 14, NULL, 132),
('0525ab8d-21f8-40cb-a19e-430f9c56670b', 48, 1200, 24, 'p226', 0, 'THREAD TAKE-UP LEVER COVER -POWER', 25, 0, 50, 50, 1250, NULL, 1, 0, 2, NULL, 6, NULL, 865),
('05376abe-dba3-44e3-865f-9e53987f8f98', 0, 0, 1, 'S1025', 0, 'T.T. CAM(S.V)', 1, 0, 0, 0, 0, NULL, 1, 0, 1, NULL, 1, NULL, 458),
('054730ca-7a3b-43cd-89af-cb17bc627518', 38, 950, 0, 'B159', 0, 'BELT 600MM', 25, 1, 6740, 40, 1000, NULL, 1, 0, 2, NULL, 7, NULL, 46),
('0548b866-a660-438b-ba6d-c1e4797fd430', 747, 18675, 23, 'T895', 0, 'TABLE TA1(SECONDS)', 25, 0, 6650, 750, 18750, NULL, 1, 0, 2, NULL, 10, NULL, 836),
('05571eac-06e5-4d38-a619-1eb5fad62bee', 0, 0, 1, 'S1020', 0, 'STOP MOTION WASHER (S.V)', 1, 0, 0, 0, 0, NULL, 1, 0, 1, NULL, 1, NULL, 457),
('057a7955-46c3-4e22-a874-b8da5ca72a7e', 38, 38, 0, 'T985', 0, 'NEEDLE BAR BUSH UPPER -TA1', 1, 1, 40, 40, 40, NULL, 1, 0, 2, NULL, 3, NULL, 404);

--
-- Table structure for table `stockledger`
--

DROP TABLE IF EXISTS `stockledger`;
CREATE TABLE `stockledger` (
  `id` varchar(255) NOT NULL,
  `closingQty` float NOT NULL,
  `entryDate` date DEFAULT NULL,
  `itemName` varchar(255) DEFAULT NULL,
  `machine` bigint NOT NULL,
  `openingQty` float NOT NULL,
  `partNo` varchar(255) DEFAULT NULL,
  `rackNumber` varchar(255) DEFAULT NULL,
  `selledQty` float NOT NULL,
  `spare` bigint NOT NULL,
  `brand` bigint DEFAULT NULL,
  `model` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FKeorw8gitdgkl2omh4trvnnvfm` (`brand`),
  KEY `FK9slmtdd1exixl9shbbn2hwm9d` (`model`),
  CONSTRAINT `FK9slmtdd1exixl9shbbn2hwm9d` FOREIGN KEY (`model`) REFERENCES `model` (`id`),
  CONSTRAINT `FKeorw8gitdgkl2omh4trvnnvfm` FOREIGN KEY (`brand`) REFERENCES `brand` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `supplier`
--

DROP TABLE IF EXISTS `supplier`;
CREATE TABLE `supplier` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `active` bit(1) NOT NULL,
  `emailId` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `phoneNo1` varchar(255) DEFAULT NULL,
  `phoneNo2` varchar(255) DEFAULT NULL,
  `whatsAppNo` varchar(255) DEFAULT NULL,
  `address` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_od83j1diqux7fbobjyjddllvh` (`address`),
  CONSTRAINT `FK15ewpkpum7cnphgckhbpyfjo0` FOREIGN KEY (`address`) REFERENCES `address` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf32;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`id`, `createdBy`, `createdOn`, `modifiedBy`, `modifiedOn`, `active`, `emailId`, `name`, `phoneNo1`, `phoneNo2`, `whatsAppNo`, `address`) VALUES
(1, 'owner@sanruth.com', '2025-01-28 15:28:24.649620', 'owner@sanruth.com', '2025-01-28 15:28:24.649620', 1, NULL, 'SANRUTH MACHINES', 9843361326, NULL, 9843361326, 192),
(2, 'System Admin', '2026-08-29 17:19:00.000000', 'System Admin', '2026-08-29 17:19:00.000000', 1, '', 'michel', 3434333333, '', '', 3152);

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `createdOn` datetime DEFAULT CURRENT_TIMESTAMP,
  `name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone_number_2` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `gender` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `password`, `role`, `createdOn`, `name`, `phone_number`, `phone_number_2`, `email`, `dob`, `address`, `gender`, `photo`) VALUES
(1, 'admin', 'admin123', 'ADMIN', '2026-04-08 17:11:27', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Table structure for table `userrole`
--

DROP TABLE IF EXISTS `userrole`;
CREATE TABLE `userrole` (
  `id` int NOT NULL AUTO_INCREMENT,
  `roleName` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_bf83xwomjfpkswclblso5uvms` (`roleName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SET FOREIGN_KEY_CHECKS=1;
