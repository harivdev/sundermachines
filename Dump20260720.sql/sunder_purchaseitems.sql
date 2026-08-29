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
-- Table structure for table `purchaseitems`
--

DROP TABLE IF EXISTS `purchaseitems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf32;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchaseitems`
--

LOCK TABLES `purchaseitems` WRITE;
/*!40000 ALTER TABLE `purchaseitems` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchaseitems` ENABLE KEYS */;
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
