-- MySQL dump 10.13  Distrib 8.0.36, for Win64 (x86_64)
--
-- Host: localhost    Database: sunder
-- ------------------------------------------------------
-- Server version	8.0.36

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `employee`
--

DROP TABLE IF EXISTS `employee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf32;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee`
--

LOCK TABLES `employee` WRITE;
/*!40000 ALTER TABLE `employee` DISABLE KEYS */;
INSERT INTO `employee` VALUES (1,'SYSTEM','2024-02-03 20:21:46.761063','SYSTEM','2024-02-03 20:21:46.761063',_binary '',NULL,'Support Engineer','2024-02-03','001','Permanent',NULL,'M','2024-02-03','',NULL,'Support Engineer',NULL,NULL,'',1,NULL,NULL,NULL,NULL,NULL),(2,'SYSTEM','2024-02-03 20:21:46.992680','SYSTEM','2024-02-03 20:21:46.992680',_binary '',NULL,'Owner','2024-02-03','002','Permanent',NULL,'M','2024-02-03','',NULL,'Owner',NULL,NULL,'',2,NULL,NULL,NULL,NULL,NULL),(3,'SYSTEM','2024-02-03 20:21:47.463329','SYSTEM','2024-02-03 20:21:47.463329',_binary '',NULL,'Mechanic','2024-02-03','003','Permanent',NULL,'M','2024-02-03','',NULL,'Mechanic',NULL,NULL,'',3,NULL,NULL,NULL,NULL,NULL),(4,'SYSTEM','2024-02-03 20:21:47.798249','SYSTEM','2024-02-03 20:21:47.798249',_binary '',NULL,'Sales Executive','2024-02-03','005','Permanent',NULL,'M','2024-02-03','',NULL,'Sales Executive',NULL,NULL,'',4,NULL,NULL,NULL,NULL,NULL),(5,'SYSTEM','2024-02-03 20:21:48.241200','SYSTEM','2024-02-03 20:21:48.241200',_binary '',NULL,'Billing','2024-02-03','005','Permanent',NULL,'M','2024-02-03','',NULL,'Billing',NULL,NULL,'',5,NULL,NULL,NULL,NULL,NULL),(6,'SYSTEM','2024-02-03 20:21:48.646241','SYSTEM','2024-02-03 20:21:48.646241',_binary '',NULL,'Cashier','2024-02-03','005','Permanent',NULL,'M','2024-02-03','',NULL,'Cashier',NULL,NULL,'',6,NULL,NULL,NULL,NULL,NULL),(7,'owner@sunder.com','2025-01-24 09:57:10.989920','owner@sunder.com','2025-01-24 09:57:15.947071',_binary '','ANTHIYUR','Mechanic','1996-02-12','E00001','Permanent','2075-01-23','F','2022-11-20','KALLANKAATU PUTHUR','KEELVANI','NITHYAKALYANI','8760567343','8760567343','638502',7,NULL,NULL,NULL,NULL,NULL),(8,'owner@sunder.com','2025-01-25 19:32:13.236585','owner@sunder.com','2025-01-31 19:46:04.560018',_binary '','GOBI','Owner','2013-12-10','E00002','Permanent','2115-01-24','M','2024-12-31','SWASTIK ILLAM','ASIRIYAR NAGAR','SANJAY E','9043761441','9965561326','638476',8,NULL,NULL,NULL,NULL,NULL),(9,'owner@sunder.com','2025-01-27 09:50:58.894350','owner@sunder.com','2025-04-30 10:26:20.597424',_binary '','GOBI','Billing','2019-08-06','E00003','Permanent','2115-01-26','M','2025-01-05','D','','E. Ruthvik ','000','0','638458',9,'1234567892',NULL,'00000',NULL,NULL),(10,'owner@sunder.com','2025-01-27 09:52:19.337656','owner@sunder.com','2025-04-30 10:25:26.700589',_binary '','GOBI','Billing','1994-10-02','E00004','Permanent','2115-01-26','F','2025-01-05','C','','E. Priya ','00000','000','638458',10,'1234567892',NULL,'00000',NULL,NULL),(11,'owner@sunder.com','2025-01-27 09:55:29.817725','owner@sunder.com','2025-04-15 16:34:45.329423',_binary '','GOBI','Billing','2003-08-12','E00005','Permanent','2115-01-26','M','2025-01-27','C','','Elango ','0000',NULL,'638458',11,'1234567892',NULL,'000',NULL,NULL),(12,'owner@sunder.com','2025-01-27 09:57:42.371138','owner@sunder.com','2025-04-30 10:26:58.778651',_binary '','GOBI','Mechanic','2003-08-12','E00006','Permanent','2115-01-26','M','2025-01-05','ALAMPALAYAM','NAMBIYUR','Jaheer ','000',NULL,'638458',12,'1234567892',NULL,'00000',NULL,NULL),(13,'owner@sunder.com','2025-01-27 10:01:04.894878','owner@sunder.com','2026-05-12 06:41:00.901271',_binary '','GOBI','Mechanic','2003-08-12','E00007','Permanent','2026-02-25','M','2025-01-05','ALAMPALAYAM','NAMBIYUR','Elango Sanruth ','000',NULL,'638458',13,'12345+67892',NULL,'0000',NULL,NULL),(14,'owner@sunder.com','2025-03-18 17:58:13.203245','owner@sunder.com','2026-05-12 06:43:22.239565',_binary '','ERODE','Support Engineer','2000-01-04','E00008','Permanent','2115-03-17','F','2024-08-26','NO.34 ROAD DAM 3 STREET ','SALANGAPALAYAM KAVUNTHAMPADI','Sanruth ','9384177523','8072669282','638455',14,'884435025079','tmp/AadharCard/ba274a7cdd014bef985a9eca2f1dedb3.jpg','00000',NULL,NULL);
/*!40000 ALTER TABLE `employee` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-20 22:05:24
