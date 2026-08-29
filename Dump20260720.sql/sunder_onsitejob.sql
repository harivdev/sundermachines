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
-- Table structure for table `onsitejob`
--

DROP TABLE IF EXISTS `onsitejob`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf32;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `onsitejob`
--

LOCK TABLES `onsitejob` WRITE;
/*!40000 ALTER TABLE `onsitejob` DISABLE KEYS */;
INSERT INTO `onsitejob` VALUES (1,'owner@sunder.com','2025-08-05 10:44:12.675745','owner@sunder.com','2025-08-05 10:44:12.675745','Service','New Job',NULL,NULL,'2508T00001',634,NULL,1327,10),(2,'owner@sunder.com','2026-04-11 10:08:36.031972','owner@sunder.com','2026-04-11 10:08:36.031972','Service','New Job','2311189','with rod','2604T00001',2646,NULL,2670,4);
/*!40000 ALTER TABLE `onsitejob` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-20 22:05:25
