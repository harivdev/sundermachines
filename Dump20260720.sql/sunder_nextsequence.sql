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
-- Table structure for table `nextsequence`
--

DROP TABLE IF EXISTS `nextsequence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf32;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nextsequence`
--

LOCK TABLES `nextsequence` WRITE;
/*!40000 ALTER TABLE `nextsequence` DISABLE KEYS */;
INSERT INTO `nextsequence` VALUES (1,_binary '\0',7,'CUSTID','C',3146,0),(2,_binary '',5,'JOBCARD2024','J',87,2024),(3,_binary '',3,'STOCKBARCODE_T','T',1192,2024),(4,_binary '',5,'SALESORD2024','SO',146,2024),(5,_binary '',3,'STOCKBARCODE_N','N',172,2024),(6,_binary '',3,'STOCKBARCODE_p','p',465,2024),(7,_binary '',3,'STOCKBARCODE_S','S',1080,2024),(8,_binary '',3,'STOCKBARCODE_B','B',309,2024),(9,_binary '\0',3,'STOCKBARCODE_9','9',16,2024),(10,_binary '\0',3,'STOCKBARCODE_O','O',342,2024),(11,_binary '',5,'SALESORD2025','SO',3494,2025),(12,_binary '',5,'JOBCARD2025','J',2080,2025),(13,_binary '\0',3,'STOCKBARCODE_L','L',18,2025),(14,_binary '\0',3,'STOCKBARCODE_M','M',116,2025),(15,_binary '\0',3,'STOCKBARCODE_R','R',90,2025),(16,_binary '\0',3,'STOCKBARCODE_3','3',14,2025),(17,_binary '\0',3,'STOCKBARCODE_A','A',48,2025),(18,_binary '\0',3,'STOCKBARCODE_C','C',22,2025),(19,_binary '\0',3,'STOCKBARCODE_E','E',34,2025),(20,_binary '\0',5,'EMPID','E',8,0),(21,_binary '\0',3,'STOCKBARCODE_Z','Z',12,2025),(22,_binary '\0',3,'STOCKBARCODE_K','K',1,2025),(23,_binary '',5,'TRACKINGNO2025','T',1,2025),(24,_binary '',5,'SALESORD2026','SO',1664,2026),(25,_binary '',5,'JOBCARD2026','J',1069,2026),(26,_binary '',5,'TRACKINGNO2026','T',1,2026);
/*!40000 ALTER TABLE `nextsequence` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-20 22:05:21
