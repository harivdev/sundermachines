-- MySQL dump 10.13  Distrib 8.0.36, for Win64 (x86_64)
--
-- Host: localhost    Database: sanruth
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
-- Table structure for table `brand`
--

DROP TABLE IF EXISTS `brand`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `brand` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `brandName` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_8mw73aryipaafhvqpgt1ioud4` (`brandName`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf32;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brand`
--

LOCK TABLES `brand` WRITE;
/*!40000 ALTER TABLE `brand` DISABLE KEYS */;
INSERT INTO `brand` VALUES (1,'owner@sanruth.com','2024-02-22 19:21:59.016289','owner@sanruth.com','2024-02-22 19:21:59.016289','SANRUTH SV'),(2,'owner@sanruth.com','2024-02-22 20:49:42.857766','owner@sanruth.com','2025-01-11 10:54:47.024827','SANRUTH'),(3,'owner@sanruth.com','2024-02-23 10:10:04.250705','owner@sanruth.com','2024-02-23 10:10:04.250705','KING'),(6,'owner@sanruth.com','2024-02-27 18:01:29.093006','owner@sanruth.com','2024-02-27 18:01:29.093006','GTC'),(7,'owner@sanruth.com','2024-03-16 09:08:40.638868','owner@sanruth.com','2025-01-11 10:44:04.646819','MERRITT TA1'),(9,'owner@sanruth.com','2025-01-11 18:50:59.678109','owner@sanruth.com','2025-01-11 18:50:59.678109','GARLON '),(10,'owner@sanruth.com','2025-01-15 18:38:51.639773','owner@sanruth.com','2025-01-15 18:38:51.639773','EDGE'),(11,'owner@sanruth.com','2025-03-26 18:54:17.663056','owner@sanruth.com','2025-03-26 18:54:17.663056','HAYA'),(12,'owner@sanruth.com','2025-12-16 14:10:23.261163','owner@sanruth.com','2025-12-16 14:10:23.261163','SIO'),(14,'owner@sanruth.com','2026-04-07 00:03:43.061733','owner@sanruth.com','2026-04-08 15:14:15.009256','mahi');
/*!40000 ALTER TABLE `brand` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-20 22:04:19
