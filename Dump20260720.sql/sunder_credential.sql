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
-- Table structure for table `credential`
--

DROP TABLE IF EXISTS `credential`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credential`
--

LOCK TABLES `credential` WRITE;
/*!40000 ALTER TABLE `credential` DISABLE KEYS */;
INSERT INTO `credential` VALUES (1,'SYSTEM','2024-02-03 20:21:46.771089','SYSTEM','2024-02-03 20:21:46.771089','$2a$10$boexGG6nGOA3prcZJ7FYE..JKhRMW5EpSZJVsTZs1oKmBQICcfH92','IT','support@sanruth.com'),(2,'SYSTEM','2024-02-03 20:21:46.992680','SYSTEM','2024-02-03 20:21:46.992680','$2a$10$fkjAU2vYEYqqyxpkicn4leySh5TCoPrs9YXYYnksWWN.6k3c5aNWm','Admin','owner@sunder.com'),(3,'SYSTEM','2024-02-03 20:21:47.464366','SYSTEM','2024-02-03 20:21:47.464366','$2a$10$X6nhgVggmas5hwy5M9womeXOIW6JJiCk.q6hLG0RZOiNs.d2IvqsG','Mechanic','mechanic@sanruth.com'),(4,'SYSTEM','2024-02-03 20:21:47.799251','SYSTEM','2024-02-03 20:21:47.799251','$2a$10$8./HmMFm55taMcZEXc4TGe/Q6upQJwghZz2vuPIIOk1TP1LLU8.qS','Sales','sales@sanruth.com'),(5,'SYSTEM','2024-02-03 20:21:48.242194','SYSTEM','2024-02-03 20:21:48.242194','$2a$10$WcR8gCcSkLSxhhioHDP1YuS21VcftYKvvxXw5V7AXOUJr1a54CB0K','Billing','bill@sanruth.com'),(6,'SYSTEM','2024-02-03 20:21:48.647243','SYSTEM','2024-02-03 20:21:48.647243','$2a$10$l56zEOeCdbX/JFUfb2sZ2O8LR8lRb7dxAzx5Qqv/.5wxfpWVcRJbG','Cashier','cashier@sanruth.com'),(7,'owner@sunder.com','2025-01-24 09:57:10.994964','owner@sunder.com','2025-01-24 09:57:15.947071',NULL,'Mechanic','E00001@sanruth.com'),(8,'owner@sunder.com','2025-01-25 19:32:13.236585','owner@sunder.com','2025-01-25 19:32:13.236585','$2a$10$OTFvb2aTNFUYsx.6J9iNJ.rU8G6uOJ1UdxCzckZtIGeqxnR0Czlem','Admin','E00002@sanruth.com'),(9,'owner@sunder.com','2025-01-27 09:50:58.894350','owner@sunder.com','2025-01-27 09:50:58.894350',NULL,'Billing','E00003@sanruth.com'),(10,'owner@sunder.com','2025-01-27 09:52:19.337656','owner@sunder.com','2025-01-27 09:52:21.506715',NULL,'Billing','E00004@sanruth.com'),(11,'owner@sunder.com','2025-01-27 09:55:29.817725','owner@sunder.com','2025-01-27 09:55:29.817725','$2a$10$UNL5HpEK5OzdllmBQ8azpeNVA8qz.hzbuCM1CM2B03rjzzHeMYNZm','Billing','E00005@sanruth.com'),(12,'owner@sunder.com','2025-01-27 09:57:42.371138','owner@sunder.com','2025-01-27 09:57:42.371138','$2a$10$zh4HYpBy.ao/yhluk9XZDuVbi4XOANwJtHexAYjFKKsyrn4KcOPpS','Admin','E00006@sanruth.com'),(13,'owner@sunder.com','2025-01-27 10:01:04.894878','owner@sunder.com','2025-01-27 10:01:04.894878','$2a$10$PMjjyP1GKTDVL/Zmx5xI5O2O0KQhqso4C4Lt/GpQs4PU/vowsIQUm','Sales','E00007@sanruth.com'),(14,'owner@sunder.com','2025-03-18 17:58:13.203245','owner@sunder.com','2025-03-18 17:58:13.203245',NULL,'Mechanic','E00008@sanruth.com');
/*!40000 ALTER TABLE `credential` ENABLE KEYS */;
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
