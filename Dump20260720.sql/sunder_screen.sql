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
-- Table structure for table `screen`
--

DROP TABLE IF EXISTS `screen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `screen` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `screenName` varchar(255) NOT NULL,
  `screenPath` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_215vb1n50d9vn50pucg438uij` (`screenName`),
  UNIQUE KEY `UK_mcwri881l9i6yo06h33ecth79` (`screenPath`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf32;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `screen`
--

LOCK TABLES `screen` WRITE;
/*!40000 ALTER TABLE `screen` DISABLE KEYS */;
INSERT INTO `screen` VALUES (1,'Config Access','/view/secured/config/access'),(2,'Update Password','/view/secured/update/password'),(3,'Dashboard','/view/secured/dashboard'),(4,'Sales Order','/view/secured/order/sales'),(5,'Purchase Order','/view/secured/order/purchase'),(6,'Employee','/view/secured/admin/employee'),(7,'Stock','/view/secured/admin/stocks/list'),(8,'Machine','/view/secured/admin/machine'),(9,'Model','/view/secured/admin/machine/model'),(10,'Machine Type','/view/secured/admin/machine/type'),(11,'Brand','/view/secured/admin/machine/brand'),(12,'Spare','/view/secured/admin/spare'),(13,'Supplier','/view/secured/admin/supplier'),(14,'Customer','/view/secured/admin/customer'),(15,'Job Card','/view/secured/job/card'),(16,'Reorder Level','/view/secured/admin/stocks/minStockConfig'),(17,'Ledger','/view/secured/admin/stocks/ledger'),(18,'OnSite Service','/view/secured/service/onsite');
/*!40000 ALTER TABLE `screen` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-20 22:04:16
