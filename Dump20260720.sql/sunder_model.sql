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
-- Table structure for table `model`
--

DROP TABLE IF EXISTS `model`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `createdBy` varchar(255) DEFAULT NULL,
  `createdOn` datetime(6) DEFAULT NULL,
  `modifiedBy` varchar(255) DEFAULT NULL,
  `modifiedOn` datetime(6) DEFAULT NULL,
  `model` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_rnt7dvqqcy5rm8oa4cp32bk11` (`model`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf32;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model`
--

LOCK TABLES `model` WRITE;
/*!40000 ALTER TABLE `model` DISABLE KEYS */;
INSERT INTO `model` VALUES (1,'owner@sunder.com','2024-02-22 19:22:09.727410','owner@sunder.com','2024-02-22 19:22:09.727410','SV'),(3,'owner@sunder.com','2024-02-22 20:04:59.633922','owner@sunder.com','2024-02-22 20:04:59.633922','TA1'),(6,'owner@sunder.com','2024-02-23 09:50:55.348182','owner@sunder.com','2025-01-18 09:26:12.735910','POWER'),(7,'owner@sunder.com','2024-02-23 13:00:19.175720','owner@sunder.com','2024-02-23 13:00:19.175720','BELT'),(8,'owner@sunder.com','2024-02-23 13:24:27.571587','owner@sunder.com','2024-02-23 13:24:27.571587','NEEDLES'),(9,'owner@sunder.com','2024-02-23 13:25:14.438934','owner@sunder.com','2024-02-23 13:25:14.438934','THREAT'),(10,'owner@sunder.com','2024-02-23 13:26:19.329779','owner@sunder.com','2024-02-23 13:26:19.329779','TABLE SPARES'),(11,'owner@sunder.com','2024-02-23 13:27:09.872539','owner@sunder.com','2024-02-23 13:27:09.872539','REGULATOR SPARES'),(12,'owner@sunder.com','2024-02-23 13:27:25.900073','owner@sunder.com','2024-02-23 13:27:25.900073','MOTOR SPARES'),(13,'owner@sunder.com','2024-02-23 13:42:51.498423','owner@sunder.com','2024-02-23 13:42:51.498423','BAG CLOSER '),(14,'owner@sunder.com','2024-02-23 13:52:44.222146','owner@sunder.com','2024-02-23 13:52:44.222146','OIL'),(15,'owner@sunder.com','2024-02-23 14:56:36.552619','owner@sunder.com','2024-02-23 14:56:36.552619','SCISSORS'),(16,'owner@sunder.com','2024-02-28 13:41:14.600450','owner@sunder.com','2025-01-18 09:27:03.496824','31K'),(17,'owner@sunder.com','2024-03-16 09:08:54.830908','owner@sunder.com','2025-01-18 09:27:31.205467','OVERLOCK POWER'),(18,'owner@sunder.com','2024-12-27 18:22:24.250700','owner@sunder.com','2025-01-20 12:12:06.390225','ESR BAG CLOSER SPARES'),(19,'owner@sunder.com','2025-01-11 18:51:37.128254','owner@sunder.com','2025-01-18 09:28:43.933108','ZIGZAG'),(20,'owner@sunder.com','2025-01-15 18:29:26.695318','owner@sunder.com','2025-01-15 18:29:26.695318','LED LIGHT'),(21,'owner@sunder.com','2025-01-16 13:47:18.828870','owner@sunder.com','2025-01-16 13:47:18.828870','SCREWS'),(22,'owner@sunder.com','2025-01-18 11:01:25.512627','owner@sunder.com','2025-01-18 11:01:25.512627','ACCESSORIES'),(23,'owner@sunder.com','2025-01-18 12:22:57.247093','owner@sunder.com','2025-01-18 12:22:57.247093','CUTTING MACHINES'),(24,'owner@sunder.com','2025-01-18 13:09:18.666214','owner@sunder.com','2025-01-18 13:09:18.666214','CUTTING MACHINES SPARES'),(25,'owner@sunder.com','2025-01-20 10:27:32.215212','owner@sunder.com','2025-01-20 10:27:32.215212','BAG CLOSER MACHINES'),(26,'owner@sunder.com','2025-01-20 14:19:31.619378','owner@sunder.com','2025-01-20 14:19:31.619378','OVER LOCK SPARES'),(27,'owner@sunder.com','2025-01-22 11:42:49.885282','owner@sunder.com','2025-01-22 11:42:49.885282','SCREW DRIVER'),(28,'owner@sunder.com','2025-06-16 13:29:20.117827','owner@sunder.com','2025-06-16 13:29:20.117827','KAJA'),(29,'owner@sunder.com','2025-12-10 14:10:58.325195','owner@sunder.com','2025-12-10 14:10:58.325195','EMBRODERY MACHINE');
/*!40000 ALTER TABLE `model` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-20 22:05:20
