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
-- Temporary view structure for view `sparereorderlevelalert`
--

DROP TABLE IF EXISTS `sparereorderlevelalert`;
/*!50001 DROP VIEW IF EXISTS `sparereorderlevelalert`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `sparereorderlevelalert` AS SELECT 
 1 AS `id`,
 1 AS `reorderLevelId`,
 1 AS `itemName`,
 1 AS `machine`,
 1 AS `spare`,
 1 AS `brand`,
 1 AS `model`,
 1 AS `minUnit`,
 1 AS `availableUnit`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `machinestock`
--

DROP TABLE IF EXISTS `machinestock`;
/*!50001 DROP VIEW IF EXISTS `machinestock`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `machinestock` AS SELECT 
 1 AS `id`,
 1 AS `machineId`,
 1 AS `machineName`,
 1 AS `modelId`,
 1 AS `brandId`,
 1 AS `modelName`,
 1 AS `brandName`,
 1 AS `assembeldByUs`,
 1 AS `active`,
 1 AS `category`,
 1 AS `totalUnit`,
 1 AS `totalQuantity`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `machinereorderlevelalert`
--

DROP TABLE IF EXISTS `machinereorderlevelalert`;
/*!50001 DROP VIEW IF EXISTS `machinereorderlevelalert`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `machinereorderlevelalert` AS SELECT 
 1 AS `id`,
 1 AS `reorderLevelId`,
 1 AS `itemName`,
 1 AS `machine`,
 1 AS `spare`,
 1 AS `brand`,
 1 AS `model`,
 1 AS `minUnit`,
 1 AS `availableUnit`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `itemstockview`
--

DROP TABLE IF EXISTS `itemstockview`;
/*!50001 DROP VIEW IF EXISTS `itemstockview`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `itemstockview` AS SELECT 
 1 AS `id`,
 1 AS `machineId`,
 1 AS `spareId`,
 1 AS `itemName`,
 1 AS `modelName`,
 1 AS `brandName`,
 1 AS `modelId`,
 1 AS `brandId`,
 1 AS `category`,
 1 AS `totalUnit`,
 1 AS `totalQuantity`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `reorderlevelalert`
--

DROP TABLE IF EXISTS `reorderlevelalert`;
/*!50001 DROP VIEW IF EXISTS `reorderlevelalert`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `reorderlevelalert` AS SELECT 
 1 AS `id`,
 1 AS `reorderLevelId`,
 1 AS `itemName`,
 1 AS `machine`,
 1 AS `spare`,
 1 AS `brand`,
 1 AS `model`,
 1 AS `minUnit`,
 1 AS `availableUnit`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `laborsummary`
--

DROP TABLE IF EXISTS `laborsummary`;
/*!50001 DROP VIEW IF EXISTS `laborsummary`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `laborsummary` AS SELECT 
 1 AS `employeeId`,
 1 AS `employeeName`,
 1 AS `active`,
 1 AS `modifiedOn`,
 1 AS `totalProgressJobs`,
 1 AS `totalCompletedJobs`,
 1 AS `totalDeliveredJobs`,
 1 AS `totalJob`,
 1 AS `totalCharge`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `availablestockbymodelandbrand`
--

DROP TABLE IF EXISTS `availablestockbymodelandbrand`;
/*!50001 DROP VIEW IF EXISTS `availablestockbymodelandbrand`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `availablestockbymodelandbrand` AS SELECT 
 1 AS `machine`,
 1 AS `spare`,
 1 AS `model`,
 1 AS `brand`,
 1 AS `availableUnit`,
 1 AS `totalQuantity`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `availablesparestockbymodelandbrand`
--

DROP TABLE IF EXISTS `availablesparestockbymodelandbrand`;
/*!50001 DROP VIEW IF EXISTS `availablesparestockbymodelandbrand`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `availablesparestockbymodelandbrand` AS SELECT 
 1 AS `spare`,
 1 AS `model`,
 1 AS `brand`,
 1 AS `totalQuantity`,
 1 AS `availableUnit`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `availablemachinestockbymodelandbrand`
--

DROP TABLE IF EXISTS `availablemachinestockbymodelandbrand`;
/*!50001 DROP VIEW IF EXISTS `availablemachinestockbymodelandbrand`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `availablemachinestockbymodelandbrand` AS SELECT 
 1 AS `machine`,
 1 AS `model`,
 1 AS `brand`,
 1 AS `totalQuantity`,
 1 AS `availableUnit`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `sparestock`
--

DROP TABLE IF EXISTS `sparestock`;
/*!50001 DROP VIEW IF EXISTS `sparestock`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `sparestock` AS SELECT 
 1 AS `id`,
 1 AS `spareId`,
 1 AS `spareName`,
 1 AS `modelId`,
 1 AS `brandId`,
 1 AS `modelName`,
 1 AS `brandName`,
 1 AS `partNo`,
 1 AS `rackNumber`,
 1 AS `active`,
 1 AS `category`,
 1 AS `totalUnit`,
 1 AS `totalQuantity`*/;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `sparereorderlevelalert`
--

/*!50001 DROP VIEW IF EXISTS `sparereorderlevelalert`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`sanruth`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `sparereorderlevelalert` AS select left(md5(rand()),8) AS `id`,`rl`.`id` AS `reorderLevelId`,`rl`.`itemName` AS `itemName`,`rl`.`machine` AS `machine`,`rl`.`spare` AS `spare`,`rl`.`brand` AS `brand`,`rl`.`model` AS `model`,`rl`.`minUnit` AS `minUnit`,`s`.`totalQuantity` AS `availableUnit` from (`reorderlevel` `rl` join `availablesparestockbymodelandbrand` `s` on((`s`.`spare` = `rl`.`spare`))) where ((`rl`.`spare` is not null) and (`rl`.`spare` > 0) and (`s`.`totalQuantity` < `rl`.`minUnit`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `machinestock`
--

/*!50001 DROP VIEW IF EXISTS `machinestock`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`sanruth`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `machinestock` AS select left(md5(rand()),8) AS `id`,`m`.`id` AS `machineId`,`m`.`machineName` AS `machineName`,`mo`.`id` AS `modelId`,`b`.`id` AS `brandId`,`mo`.`model` AS `modelName`,`b`.`brandName` AS `brandName`,`m`.`assembeldByUs` AS `assembeldByUs`,`m`.`active` AS `active`,'MCN' AS `category`,sum(`s`.`unit`) AS `totalUnit`,sum(`s`.`availableQty`) AS `totalQuantity` from (((`machine` `m` join `stock` `s` on((((0 <> `s`.`selled`) is false) and (`s`.`machine` = `m`.`id`)))) join `model` `mo` on((`mo`.`id` = `s`.`model`))) join `brand` `b` on((`b`.`id` = `s`.`brand`))) group by `s`.`machine`,`s`.`model`,`s`.`brand` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `machinereorderlevelalert`
--

/*!50001 DROP VIEW IF EXISTS `machinereorderlevelalert`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`sanruth`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `machinereorderlevelalert` AS select left(md5(rand()),8) AS `id`,`rl`.`id` AS `reorderLevelId`,`rl`.`itemName` AS `itemName`,`rl`.`machine` AS `machine`,`rl`.`spare` AS `spare`,`rl`.`brand` AS `brand`,`rl`.`model` AS `model`,`rl`.`minUnit` AS `minUnit`,`m`.`totalQuantity` AS `availableUnit` from (`reorderlevel` `rl` join `availablemachinestockbymodelandbrand` `m` on((`m`.`machine` = `rl`.`machine`))) where ((`rl`.`machine` is not null) and (`rl`.`machine` > 0) and (`m`.`totalQuantity` < `rl`.`minUnit`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `itemstockview`
--

/*!50001 DROP VIEW IF EXISTS `itemstockview`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`sanruth`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `itemstockview` AS select left(md5(rand()),8) AS `id`,`machinestock`.`machineId` AS `machineId`,0 AS `spareId`,`machinestock`.`machineName` AS `itemName`,`machinestock`.`modelName` AS `modelName`,`machinestock`.`brandName` AS `brandName`,`machinestock`.`modelId` AS `modelId`,`machinestock`.`brandId` AS `brandId`,`machinestock`.`category` AS `category`,`machinestock`.`totalUnit` AS `totalUnit`,`machinestock`.`totalQuantity` AS `totalQuantity` from `machinestock` where (`machinestock`.`id` is not null) union select left(md5(rand()),8) AS `id`,0 AS `machineId`,`sparestock`.`spareId` AS `spareId`,`sparestock`.`spareName` AS `itemName`,`sparestock`.`modelName` AS `modelName`,`sparestock`.`brandName` AS `brandName`,`sparestock`.`modelId` AS `modelId`,`sparestock`.`brandId` AS `brandId`,`sparestock`.`category` AS `category`,`sparestock`.`totalUnit` AS `totalUnit`,`sparestock`.`totalQuantity` AS `totalQuantity` from `sparestock` where (`sparestock`.`id` is not null) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `reorderlevelalert`
--

/*!50001 DROP VIEW IF EXISTS `reorderlevelalert`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`sanruth`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `reorderlevelalert` AS select `machinereorderlevelalert`.`id` AS `id`,`machinereorderlevelalert`.`reorderLevelId` AS `reorderLevelId`,`machinereorderlevelalert`.`itemName` AS `itemName`,`machinereorderlevelalert`.`machine` AS `machine`,`machinereorderlevelalert`.`spare` AS `spare`,`machinereorderlevelalert`.`brand` AS `brand`,`machinereorderlevelalert`.`model` AS `model`,`machinereorderlevelalert`.`minUnit` AS `minUnit`,`machinereorderlevelalert`.`availableUnit` AS `availableUnit` from `machinereorderlevelalert` union select `sparereorderlevelalert`.`id` AS `id`,`sparereorderlevelalert`.`reorderLevelId` AS `reorderLevelId`,`sparereorderlevelalert`.`itemName` AS `itemName`,`sparereorderlevelalert`.`machine` AS `machine`,`sparereorderlevelalert`.`spare` AS `spare`,`sparereorderlevelalert`.`brand` AS `brand`,`sparereorderlevelalert`.`model` AS `model`,`sparereorderlevelalert`.`minUnit` AS `minUnit`,`sparereorderlevelalert`.`availableUnit` AS `availableUnit` from `sparereorderlevelalert` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `laborsummary`
--

/*!50001 DROP VIEW IF EXISTS `laborsummary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`sanruth`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `laborsummary` AS select `e`.`id` AS `employeeId`,`e`.`name` AS `employeeName`,`e`.`active` AS `active`,date_format(`jc`.`modifiedOn`,'%Y-%m-%d') AS `modifiedOn`,sum((case when (`jc`.`jobStatus` = 'Job Progress') then 1 else 0 end)) AS `totalProgressJobs`,sum((case when (`jc`.`jobStatus` = 'Job Completed') then 1 else 0 end)) AS `totalCompletedJobs`,sum((case when (`jc`.`jobStatus` = 'Job Delivered') then 1 else 0 end)) AS `totalDeliveredJobs`,count(1) AS `totalJob`,sum(`jc`.`laborCharge`) AS `totalCharge` from (`jobcard` `jc` join `employee` `e` on((`e`.`id` = `jc`.`employee`))) group by `e`.`id`,date_format(`jc`.`modifiedOn`,'%Y-%m-%d') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `availablestockbymodelandbrand`
--

/*!50001 DROP VIEW IF EXISTS `availablestockbymodelandbrand`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`sanruth`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `availablestockbymodelandbrand` AS select `m`.`machine` AS `machine`,0 AS `spare`,`m`.`model` AS `model`,`m`.`brand` AS `brand`,`m`.`availableUnit` AS `availableUnit`,`m`.`totalQuantity` AS `totalQuantity` from `availablemachinestockbymodelandbrand` `m` union select 0 AS `machine`,`s`.`spare` AS `spare`,`s`.`model` AS `model`,`s`.`brand` AS `brand`,`s`.`availableUnit` AS `availableUnit`,`s`.`totalQuantity` AS `totalQuantity` from `availablesparestockbymodelandbrand` `s` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `availablesparestockbymodelandbrand`
--

/*!50001 DROP VIEW IF EXISTS `availablesparestockbymodelandbrand`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`sanruth`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `availablesparestockbymodelandbrand` AS select `s`.`spare` AS `spare`,`s`.`model` AS `model`,`s`.`brand` AS `brand`,sum(`s`.`availableQty`) AS `totalQuantity`,sum(`s`.`unit`) AS `availableUnit` from `stock` `s` where (((0 <> `s`.`selled`) is false) and (`s`.`spare` is not null) and (`s`.`spare` > 0)) group by `s`.`spare`,`s`.`model`,`s`.`brand` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `availablemachinestockbymodelandbrand`
--

/*!50001 DROP VIEW IF EXISTS `availablemachinestockbymodelandbrand`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`sanruth`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `availablemachinestockbymodelandbrand` AS select `s`.`machine` AS `machine`,`s`.`model` AS `model`,`s`.`brand` AS `brand`,sum(`s`.`availableQty`) AS `totalQuantity`,sum(`s`.`unit`) AS `availableUnit` from `stock` `s` where (((0 <> `s`.`selled`) is false) and (`s`.`machine` is not null) and (`s`.`machine` > 0)) group by `s`.`machine`,`s`.`model`,`s`.`brand` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `sparestock`
--

/*!50001 DROP VIEW IF EXISTS `sparestock`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`sanruth`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `sparestock` AS select left(md5(rand()),8) AS `id`,`sp`.`id` AS `spareId`,`sp`.`spareName` AS `spareName`,`mo`.`id` AS `modelId`,`b`.`id` AS `brandId`,`mo`.`model` AS `modelName`,`b`.`brandName` AS `brandName`,`sp`.`partNo` AS `partNo`,`sp`.`rackNumber` AS `rackNumber`,`sp`.`active` AS `active`,'SPR' AS `category`,sum(`s`.`unit`) AS `totalUnit`,sum(`s`.`availableQty`) AS `totalQuantity` from (((`spares` `sp` join `stock` `s` on((((0 <> `s`.`selled`) is false) and (`s`.`spare` = `sp`.`id`)))) join `model` `mo` on((`mo`.`id` = `s`.`model`))) join `brand` `b` on((`b`.`id` = `s`.`brand`))) group by `sp`.`id`,`s`.`model`,`s`.`brand` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-20 22:05:26
