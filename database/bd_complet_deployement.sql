-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: bd_complet_deployement
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `bd_complet_deployement`
--

/*!40000 DROP DATABASE IF EXISTS `bd_complet_deployement`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `bd_complet_deployement` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `bd_complet_deployement`;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `auditable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `auditable_id` bigint unsigned DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `institution_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_uuid_unique` (`uuid`),
  UNIQUE KEY `branches_code_unique` (`code`),
  KEY `branches_institution_id_foreign` (`institution_id`),
  CONSTRAINT `branches_institution_id_foreign` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_movements`
--

DROP TABLE IF EXISTS `cash_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `direction` enum('in','out') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `operation_type_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL,
  `currency_code` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate_to_usd` decimal(18,8) NOT NULL DEFAULT '1.00000000',
  `amount_usd` decimal(14,2) NOT NULL,
  `sale_id` bigint unsigned DEFAULT NULL,
  `commission_id` bigint unsigned DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `user_id` bigint unsigned NOT NULL,
  `occurred_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cash_movements_uuid_unique` (`uuid`),
  KEY `cash_movements_branch_id_foreign` (`branch_id`),
  KEY `cash_movements_sale_id_foreign` (`sale_id`),
  KEY `cash_movements_commission_id_foreign` (`commission_id`),
  KEY `cash_movements_user_id_foreign` (`user_id`),
  KEY `cash_movements_currency_code_foreign` (`currency_code`),
  KEY `cash_movements_operation_type_id_foreign` (`operation_type_id`),
  CONSTRAINT `cash_movements_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `cash_movements_commission_id_foreign` FOREIGN KEY (`commission_id`) REFERENCES `commission_ledger` (`id`),
  CONSTRAINT `cash_movements_currency_code_foreign` FOREIGN KEY (`currency_code`) REFERENCES `currencies` (`code`),
  CONSTRAINT `cash_movements_operation_type_id_foreign` FOREIGN KEY (`operation_type_id`) REFERENCES `cash_operation_types` (`id`),
  CONSTRAINT `cash_movements_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  CONSTRAINT `cash_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_movements`
--

LOCK TABLES `cash_movements` WRITE;
/*!40000 ALTER TABLE `cash_movements` DISABLE KEYS */;
/*!40000 ALTER TABLE `cash_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_operation_types`
--

DROP TABLE IF EXISTS `cash_operation_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_operation_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direction` enum('in','out','both') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cash_operation_types_uuid_unique` (`uuid`),
  UNIQUE KEY `cash_operation_types_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_operation_types`
--

LOCK TABLES `cash_operation_types` WRITE;
/*!40000 ALTER TABLE `cash_operation_types` DISABLE KEYS */;
INSERT INTO `cash_operation_types` VALUES (1,'a5f33800-27fa-4383-8ae3-41a31c6082dd','sale',NULL,'in',10,1,1,'2026-09-17 06:50:51','2026-09-17 06:50:51'),(2,'0a1b5b50-852d-4a82-bacd-d7884642c1b7','membership',NULL,'in',20,1,1,'2026-09-17 06:50:51','2026-09-17 06:50:51'),(3,'3a5a3c8f-f76b-4103-b431-10d07d20287b','commission',NULL,'out',30,1,1,'2026-09-17 06:50:51','2026-09-17 06:50:51'),(4,'7b6143cc-3d20-48d9-97d5-2b257d74904f','capital',NULL,'in',40,0,1,'2026-09-17 06:50:51','2026-09-17 06:50:51'),(5,'61036945-b021-4b56-ae9e-ba6db8ee4503','donation',NULL,'in',50,0,1,'2026-09-17 06:50:51','2026-09-17 06:50:51'),(6,'49f76dfb-98ea-4a6b-894e-98581a64821e','transfer_in',NULL,'in',60,0,1,'2026-09-17 06:50:51','2026-09-17 06:50:51'),(7,'3afb9f73-184e-4e09-88a0-24294d234a13','other_income',NULL,'in',70,0,1,'2026-09-17 06:50:51','2026-09-17 06:50:51'),(8,'4ef21760-ac2a-4966-9fc4-4975bf11674f','charge',NULL,'out',80,0,1,'2026-09-17 06:50:51','2026-09-17 06:50:51'),(9,'6112f598-28fa-4e4c-abbe-0b455adcecbd','rent',NULL,'out',90,0,1,'2026-09-17 06:50:51','2026-09-17 06:50:51'),(10,'3aad6428-5d9f-4d42-8c4d-1b6a7f5b4e61','salary',NULL,'out',100,0,1,'2026-09-17 06:50:51','2026-09-17 06:50:51'),(11,'80520e71-bb54-47ef-b87b-a3d462037ec8','utilities',NULL,'out',110,0,1,'2026-09-17 06:50:51','2026-09-17 06:50:51'),(12,'001127ae-6c4f-4715-863f-714f5137d714','supplies',NULL,'out',120,0,1,'2026-09-17 06:50:51','2026-09-17 06:50:51'),(13,'c50ac424-6227-4ed7-921c-41d2706b1f44','transport',NULL,'out',130,0,1,'2026-09-17 06:50:51','2026-09-17 06:50:51'),(14,'eea3814b-f44a-4ce3-9b98-ce6cd358d885','transfer_out',NULL,'out',140,0,1,'2026-09-17 06:50:51','2026-09-17 06:50:51'),(15,'d221c184-cec4-4d12-801e-cfb1001524c5','other_expense',NULL,'out',150,0,1,'2026-09-17 06:50:51','2026-09-17 06:50:51'),(16,'cfec6909-7606-44ed-a4a6-5c6afd985901','other',NULL,'both',160,0,1,'2026-09-17 06:50:51','2026-09-17 06:50:51');
/*!40000 ALTER TABLE `cash_operation_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referrer_member_id` bigint unsigned NOT NULL,
  `accumulated_pv` decimal(12,2) NOT NULL DEFAULT '0.00',
  `threshold_alerted_at` timestamp NULL DEFAULT NULL,
  `converted_member_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clients_uuid_unique` (`uuid`),
  KEY `clients_referrer_member_id_foreign` (`referrer_member_id`),
  KEY `clients_converted_member_id_foreign` (`converted_member_id`),
  CONSTRAINT `clients_converted_member_id_foreign` FOREIGN KEY (`converted_member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `clients_referrer_member_id_foreign` FOREIGN KEY (`referrer_member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `commission_ledger`
--

DROP TABLE IF EXISTS `commission_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `commission_ledger` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `member_id` bigint unsigned NOT NULL,
  `type` enum('sponsorship','equilibrium','reward','product_percent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount_usd` decimal(14,2) NOT NULL,
  `related_member_id` bigint unsigned DEFAULT NULL,
  `generation` tinyint unsigned DEFAULT NULL,
  `reward_tier_id` bigint unsigned DEFAULT NULL,
  `sale_id` bigint unsigned DEFAULT NULL,
  `status` enum('pending','confirmed','paid') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `occurred_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `commission_ledger_uuid_unique` (`uuid`),
  KEY `commission_ledger_member_id_foreign` (`member_id`),
  KEY `commission_ledger_related_member_id_foreign` (`related_member_id`),
  KEY `commission_ledger_reward_tier_id_foreign` (`reward_tier_id`),
  KEY `commission_ledger_sale_id_foreign` (`sale_id`),
  CONSTRAINT `commission_ledger_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `commission_ledger_related_member_id_foreign` FOREIGN KEY (`related_member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `commission_ledger_reward_tier_id_foreign` FOREIGN KEY (`reward_tier_id`) REFERENCES `reward_tiers` (`id`),
  CONSTRAINT `commission_ledger_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `commission_ledger`
--

LOCK TABLES `commission_ledger` WRITE;
/*!40000 ALTER TABLE `commission_ledger` DISABLE KEYS */;
/*!40000 ALTER TABLE `commission_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `currencies`
--

DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencies` (
  `code` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currencies`
--

LOCK TABLES `currencies` WRITE;
/*!40000 ALTER TABLE `currencies` DISABLE KEYS */;
INSERT INTO `currencies` VALUES ('CDF','Franc congolais',0,'2026-09-17 06:51:12','2026-09-17 06:51:12'),('USD','US Dollar',1,'2026-09-17 06:51:12','2026-09-17 06:51:12');
/*!40000 ALTER TABLE `currencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equilibrium_rules`
--

DROP TABLE IF EXISTS `equilibrium_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equilibrium_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `scope` enum('generations_1_4','after_generation_4') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `generation` tinyint unsigned DEFAULT NULL,
  `amount_usd` decimal(14,2) DEFAULT NULL,
  `percent` decimal(8,4) DEFAULT NULL,
  `min_leg_pv` decimal(12,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `equilibrium_rules_uuid_unique` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equilibrium_rules`
--

LOCK TABLES `equilibrium_rules` WRITE;
/*!40000 ALTER TABLE `equilibrium_rules` DISABLE KEYS */;
INSERT INTO `equilibrium_rules` VALUES (1,'499f6af8-1cd4-41e7-af79-0a10f1473a54',NULL,1,'2026-09-17 06:51:16','2026-09-17 06:51:16',NULL,'generations_1_4',1,4.00,NULL,NULL,1),(2,'09c5c714-b6a6-4c37-9380-0f98f1bf5a07',NULL,1,'2026-09-17 06:51:16','2026-09-17 06:51:16',NULL,'generations_1_4',2,4.00,NULL,NULL,1),(3,'964b6056-810e-4388-a8fe-4ded86d2cc6b',NULL,1,'2026-09-17 06:51:16','2026-09-17 06:51:16',NULL,'generations_1_4',3,4.00,NULL,NULL,1),(4,'c5d48885-d347-4059-88bd-4b0a71a01345',NULL,1,'2026-09-17 06:51:16','2026-09-17 06:51:16',NULL,'generations_1_4',4,4.00,NULL,NULL,1),(5,'87410015-9554-4291-bc56-5688890ec9ad',NULL,1,'2026-09-17 06:51:16','2026-09-17 06:51:16',NULL,'after_generation_4',NULL,1.00,NULL,NULL,1);
/*!40000 ALTER TABLE `equilibrium_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exchange_rates`
--

DROP TABLE IF EXISTS `exchange_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exchange_rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `currency_code` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate_to_usd` decimal(18,8) NOT NULL,
  `effective_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exchange_rates_uuid_unique` (`uuid`),
  KEY `exchange_rates_currency_code_foreign` (`currency_code`),
  CONSTRAINT `exchange_rates_currency_code_foreign` FOREIGN KEY (`currency_code`) REFERENCES `currencies` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exchange_rates`
--

LOCK TABLES `exchange_rates` WRITE;
/*!40000 ALTER TABLE `exchange_rates` DISABLE KEYS */;
INSERT INTO `exchange_rates` VALUES (1,'ae8964dd-368f-4bb5-b6e8-db593ca0f75f',NULL,1,'2026-09-17 06:51:15','2026-09-17 06:51:15',NULL,'USD',1.00000000,'2019-12-31 23:00:00'),(2,'8306b212-fea5-4446-91d2-5dfbdbf3a350',NULL,1,'2026-09-17 06:51:15','2026-09-17 06:51:15',NULL,'CDF',0.00035700,'2019-12-31 23:00:00');
/*!40000 ALTER TABLE `exchange_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `institutions`
--

DROP TABLE IF EXISTS `institutions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `institutions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `acronym` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slogan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RDC',
  `rccm` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_nat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_footer` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `default_locale` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fr',
  `default_currency_code` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  PRIMARY KEY (`id`),
  UNIQUE KEY `institutions_uuid_unique` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `institutions`
--

LOCK TABLES `institutions` WRITE;
/*!40000 ALTER TABLE `institutions` DISABLE KEYS */;
INSERT INTO `institutions` VALUES (1,'608386bc-3ec8-4f04-96f2-c26fb6d3050c',NULL,3,'2026-09-17 06:51:12','2026-09-17 11:30:26',NULL,'Woly Health','WH',NULL,NULL,NULL,NULL,NULL,NULL,'Butembo','RDC',NULL,NULL,NULL,NULL,'fr','USD');
/*!40000 ALTER TABLE `institutions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `members`
--

DROP TABLE IF EXISTS `members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `member_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_document_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sponsor_id` bigint unsigned DEFAULT NULL,
  `placement_parent_id` bigint unsigned DEFAULT NULL,
  `placement_side` enum('left','right') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_branch_id` bigint unsigned DEFAULT NULL,
  `locale` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fr',
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `joined_at` timestamp NULL DEFAULT NULL,
  `membership_amount_usd` decimal(14,2) DEFAULT NULL,
  `membership_pv` decimal(12,2) DEFAULT NULL,
  `membership_type` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'direct',
  `source_client_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `members_uuid_unique` (`uuid`),
  UNIQUE KEY `members_member_code_unique` (`member_code`),
  UNIQUE KEY `members_username_unique` (`username`),
  KEY `members_sponsor_id_foreign` (`sponsor_id`),
  KEY `members_registration_branch_id_foreign` (`registration_branch_id`),
  KEY `members_placement_parent_id_index` (`placement_parent_id`),
  KEY `members_source_client_id_foreign` (`source_client_id`),
  CONSTRAINT `members_placement_parent_id_foreign` FOREIGN KEY (`placement_parent_id`) REFERENCES `members` (`id`),
  CONSTRAINT `members_registration_branch_id_foreign` FOREIGN KEY (`registration_branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `members_source_client_id_foreign` FOREIGN KEY (`source_client_id`) REFERENCES `clients` (`id`),
  CONSTRAINT `members_sponsor_id_foreign` FOREIGN KEY (`sponsor_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `members`
--

LOCK TABLES `members` WRITE;
/*!40000 ALTER TABLE `members` DISABLE KEYS */;
/*!40000 ALTER TABLE `members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_08_24_100000_create_holy_health_tables',1),(5,'2026_08_29_104800_drop_members_unplaced_unique',1),(6,'2026_08_29_125400_add_membership_fee_to_members',1),(7,'2026_08_29_134100_add_indirect_membership_to_members',1),(8,'2026_08_29_150000_add_image_and_physical_reward_tiers',1),(9,'2026_08_29_160000_add_sale_id_to_commission_ledger',1),(10,'2026_08_29_171000_create_cash_operation_types',1),(11,'2026_09_08_160000_add_product_percent_commission',1),(12,'2026_09_08_171800_add_member_product_prices',1),(13,'2026_09_16_061200_add_benefit_type_to_products',1),(14,'2026_09_16_062200_fix_after_equilibrium_4_default_amount',1),(15,'2026_09_16_064500_create_payout_requests_table',1),(16,'2026_09_16_070500_create_registration_codes_table',1),(17,'2026_09_16_080000_add_manager_role_to_users',1),(18,'2026_09_17_083700_set_equilibrium_amounts_4_then_1',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payout_requests`
--

DROP TABLE IF EXISTS `payout_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payout_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `member_id` bigint unsigned NOT NULL,
  `amount_usd` decimal(14,2) NOT NULL,
  `status` enum('pending','approved','rejected','paid') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `note` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_note` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payout_requests_uuid_unique` (`uuid`),
  KEY `payout_requests_reviewed_by_foreign` (`reviewed_by`),
  KEY `payout_requests_status_created_at_index` (`status`,`created_at`),
  KEY `payout_requests_member_id_status_index` (`member_id`,`status`),
  CONSTRAINT `payout_requests_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `payout_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payout_requests`
--

LOCK TABLES `payout_requests` WRITE;
/*!40000 ALTER TABLE `payout_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `payout_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plan_settings`
--

DROP TABLE IF EXISTS `plan_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plan_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plan_settings_key_unique` (`key`),
  KEY `plan_settings_updated_by_foreign` (`updated_by`),
  CONSTRAINT `plan_settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plan_settings`
--

LOCK TABLES `plan_settings` WRITE;
/*!40000 ALTER TABLE `plan_settings` DISABLE KEYS */;
INSERT INTO `plan_settings` VALUES (1,'sponsorship_amount_usd','15',1,'2026-09-17 06:51:15','2026-09-17 06:55:31'),(2,'membership_amount_usd','60',1,'2026-09-17 06:51:15','2026-09-17 06:55:31'),(3,'membership_pv_threshold','40',1,'2026-09-17 06:51:15','2026-09-17 06:55:31'),(4,'sponsorship_pv','40',1,'2026-09-17 06:51:15','2026-09-17 06:55:32'),(5,'username_suffix_length','4',1,'2026-09-17 06:51:15','2026-09-17 06:55:32'),(6,'default_member_password','ChangeMe123',1,'2026-09-17 06:51:15','2026-09-17 06:55:33'),(7,'member_discount_percent','0',1,'2026-09-17 06:51:15','2026-09-17 06:55:33');
/*!40000 ALTER TABLE `plan_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `benefit_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pv',
  `unit_price_usd` decimal(14,2) NOT NULL,
  `member_unit_price_usd` decimal(14,2) DEFAULT NULL,
  `pv_per_tablet` decimal(12,2) NOT NULL,
  `box_price_usd` decimal(14,2) NOT NULL,
  `member_box_price_usd` decimal(14,2) DEFAULT NULL,
  `box_pv` decimal(12,2) NOT NULL,
  `commission_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_uuid_unique` (`uuid`),
  UNIQUE KEY `products_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pv_ledger`
--

DROP TABLE IF EXISTS `pv_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pv_ledger` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `member_id` bigint unsigned NOT NULL,
  `source_type` enum('own_purchase','referred_client_purchase','sponsorship','membership') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pv_amount` decimal(12,2) NOT NULL,
  `sale_id` bigint unsigned DEFAULT NULL,
  `client_id` bigint unsigned DEFAULT NULL,
  `related_member_id` bigint unsigned DEFAULT NULL,
  `occurred_at` timestamp NOT NULL,
  `sync_status` enum('pending','confirmed','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  UNIQUE KEY `pv_ledger_uuid_unique` (`uuid`),
  KEY `pv_ledger_member_id_foreign` (`member_id`),
  KEY `pv_ledger_sale_id_foreign` (`sale_id`),
  KEY `pv_ledger_client_id_foreign` (`client_id`),
  KEY `pv_ledger_related_member_id_foreign` (`related_member_id`),
  CONSTRAINT `pv_ledger_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  CONSTRAINT `pv_ledger_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `pv_ledger_related_member_id_foreign` FOREIGN KEY (`related_member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `pv_ledger_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pv_ledger`
--

LOCK TABLES `pv_ledger` WRITE;
/*!40000 ALTER TABLE `pv_ledger` DISABLE KEYS */;
/*!40000 ALTER TABLE `pv_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registration_codes`
--

DROP TABLE IF EXISTS `registration_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `registration_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `branch_id` bigint unsigned NOT NULL,
  `code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('available','used','revoked') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `created_by` bigint unsigned DEFAULT NULL,
  `used_by_user_id` bigint unsigned DEFAULT NULL,
  `used_by_member_id` bigint unsigned DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `registration_codes_uuid_unique` (`uuid`),
  UNIQUE KEY `registration_codes_code_unique` (`code`),
  KEY `registration_codes_created_by_foreign` (`created_by`),
  KEY `registration_codes_used_by_user_id_foreign` (`used_by_user_id`),
  KEY `registration_codes_used_by_member_id_foreign` (`used_by_member_id`),
  KEY `registration_codes_branch_id_status_index` (`branch_id`,`status`),
  CONSTRAINT `registration_codes_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `registration_codes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `registration_codes_used_by_member_id_foreign` FOREIGN KEY (`used_by_member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL,
  CONSTRAINT `registration_codes_used_by_user_id_foreign` FOREIGN KEY (`used_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registration_codes`
--

LOCK TABLES `registration_codes` WRITE;
/*!40000 ALTER TABLE `registration_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `registration_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reward_tiers`
--

DROP TABLE IF EXISTS `reward_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reward_tiers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `min_pv` decimal(12,2) NOT NULL,
  `max_pv` decimal(12,2) DEFAULT NULL,
  `amount_usd` decimal(14,2) DEFAULT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `reward_tiers_uuid_unique` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reward_tiers`
--

LOCK TABLES `reward_tiers` WRITE;
/*!40000 ALTER TABLE `reward_tiers` DISABLE KEYS */;
INSERT INTO `reward_tiers` VALUES (1,'874f0952-932e-4f5e-b80c-c1ef75ad15ed',NULL,1,'2026-09-17 06:50:46','2026-09-18 13:05:24','2026-09-18 13:05:24',160.00,NULL,0.00,'Machine à coudre','images/rewards/sewing-machine.png',1,1),(2,'a225d7bb-baad-4943-85e3-ddce51c0e819',NULL,1,'2026-09-17 06:50:46','2026-09-18 13:05:20','2026-09-18 13:05:20',1000.00,NULL,0.00,'Téléphone','images/rewards/phone.png',2,1),(3,'2bc5a049-2746-47eb-9bd8-24de14232644',NULL,1,'2026-09-17 06:50:46','2026-09-18 13:05:17','2026-09-18 13:05:17',10000.00,NULL,0.00,'Moto','images/rewards/moto.png',3,1),(4,'7fdc5379-52b8-42f3-a949-fec49cd3704c',NULL,1,'2026-09-17 06:50:46','2026-09-18 13:05:10','2026-09-18 13:05:10',24000.00,NULL,0.00,'Voiture','images/rewards/car.png',4,1),(5,'52b424dd-3627-4b77-93d2-0c794d2bbfb9','office-local',1,'2026-09-18 13:07:51','2026-09-18 13:07:51',NULL,160.00,NULL,50.00,'Machine','rewards/lYzvgnbwtM22a7VX2u27pInY2EJxdFT3PjIhxSti.png',0,1),(6,'4c30142e-00d3-4844-96d8-205e8a7ee71a','office-local',1,'2026-09-18 13:08:39','2026-09-18 13:08:39',NULL,1000.00,NULL,100.00,'Téléphone','rewards/9lGhDK8gZZc87NcVlrg4SBSs2gecaAkVJ3E7rzpN.png',0,1),(7,'919537c3-3cca-4f58-95f4-762d92329ff6','office-local',1,'2026-09-18 13:09:22','2026-09-18 13:09:22',NULL,10000.00,NULL,1100.00,'Moto','rewards/kblZUyNofHS2DDOGULXG0ULS7nk1wcvJzE4PIZco.png',0,1),(8,'cf8094dd-ab3c-4932-8078-43c68315e6be','office-local',1,'2026-09-18 13:09:58','2026-09-18 13:09:58',NULL,24000.00,NULL,4000.00,'Voiture','rewards/hd5O0UTm0C6r9gTLNQSyhZloHcjIyGQC6JhpGZ8y.png',0,1);
/*!40000 ALTER TABLE `reward_tiers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_items`
--

DROP TABLE IF EXISTS `sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `sale_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `packing` enum('tablet','box') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int unsigned NOT NULL,
  `unit_price_usd` decimal(14,2) NOT NULL,
  `pv` decimal(12,2) NOT NULL,
  `commission_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `commission_usd` decimal(14,2) NOT NULL DEFAULT '0.00',
  `line_total_usd` decimal(14,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sale_items_uuid_unique` (`uuid`),
  KEY `sale_items_sale_id_foreign` (`sale_id`),
  KEY `sale_items_product_id_foreign` (`product_id`),
  CONSTRAINT `sale_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `sale_items_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_items`
--

LOCK TABLES `sale_items` WRITE;
/*!40000 ALTER TABLE `sale_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `sale_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `buyer_type` enum('member','client') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `benefit_mode` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pv',
  `member_id` bigint unsigned DEFAULT NULL,
  `client_id` bigint unsigned DEFAULT NULL,
  `currency_code` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate_to_usd` decimal(18,8) NOT NULL DEFAULT '1.00000000',
  `subtotal_usd` decimal(14,2) NOT NULL,
  `discount_usd` decimal(14,2) NOT NULL DEFAULT '0.00',
  `promo_usd` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_usd` decimal(14,2) NOT NULL,
  `sold_at` timestamp NOT NULL,
  `sync_status` enum('pending','confirmed','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_uuid_unique` (`uuid`),
  UNIQUE KEY `sales_number_unique` (`number`),
  KEY `sales_branch_id_foreign` (`branch_id`),
  KEY `sales_user_id_foreign` (`user_id`),
  KEY `sales_member_id_foreign` (`member_id`),
  KEY `sales_client_id_foreign` (`client_id`),
  KEY `sales_currency_code_foreign` (`currency_code`),
  CONSTRAINT `sales_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `sales_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  CONSTRAINT `sales_currency_code_foreign` FOREIGN KEY (`currency_code`) REFERENCES `currencies` (`code`),
  CONSTRAINT `sales_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `sales_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sync_outbox`
--

DROP TABLE IF EXISTS `sync_outbox`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sync_outbox` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `operation` enum('create','update','delete') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload_json` json DEFAULT NULL,
  `origin_device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `queued_at` timestamp NOT NULL,
  `synced_at` timestamp NULL DEFAULT NULL,
  `last_error` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `sync_outbox_entity_type_entity_uuid_index` (`entity_type`,`entity_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sync_outbox`
--

LOCK TABLES `sync_outbox` WRITE;
/*!40000 ALTER TABLE `sync_outbox` DISABLE KEYS */;
/*!40000 ALTER TABLE `sync_outbox` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','manager','cashier','accountant') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `locale` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fr',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_uuid_unique` (`uuid`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_branch_id_foreign` (`branch_id`),
  CONSTRAINT `users_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'424fe991-0e49-4f61-bbbe-8279b468597c',NULL,1,'2026-09-17 06:51:13','2026-09-17 06:51:13',NULL,'Administrateur','admin',NULL,NULL,'$2y$12$nu8yviHZmuwRMqRl1HgFmuxOYqXnUGGfJp0c66b/qzbjxMRiCc3sW','admin',NULL,'fr',1,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `whatsapp_outbox`
--

DROP TABLE IF EXISTS `whatsapp_outbox`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `whatsapp_outbox` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `to_phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload_json` json DEFAULT NULL,
  `body_rendered` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `driver` enum('wa_me','api') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'wa_me',
  `status` enum('pending','opened','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  UNIQUE KEY `whatsapp_outbox_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_outbox`
--

LOCK TABLES `whatsapp_outbox` WRITE;
/*!40000 ALTER TABLE `whatsapp_outbox` DISABLE KEYS */;
/*!40000 ALTER TABLE `whatsapp_outbox` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `whatsapp_templates`
--

DROP TABLE IF EXISTS `whatsapp_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `whatsapp_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `whatsapp_templates_key_locale_unique` (`key`,`locale`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_templates`
--

LOCK TABLES `whatsapp_templates` WRITE;
/*!40000 ALTER TABLE `whatsapp_templates` DISABLE KEYS */;
INSERT INTO `whatsapp_templates` VALUES (1,'welcome','fr','Bienvenue {{name}}. Identifiant: {{username}} Mot de passe: {{password}}','2026-09-17 06:51:16','2026-09-17 06:51:16'),(2,'welcome','sw','Karibu {{name}}. Jina la mtumiaji: {{username}} Nenosiri: {{password}}','2026-09-17 06:51:17','2026-09-17 06:51:17'),(3,'receipt','fr','Reçu {{number}} total {{total}} {{currency}}','2026-09-17 06:51:17','2026-09-17 06:51:17'),(4,'receipt','sw','Risiti {{number}} jumla {{total}} {{currency}}','2026-09-17 06:51:17','2026-09-17 06:51:17'),(5,'membership-threshold','fr','Le seuil d adhésion est atteint pour {{name}}. PV: {{pv}}','2026-09-17 06:51:17','2026-09-17 06:51:17'),(6,'membership-threshold','sw','Kiwango cha uanachama kimefikiwa kwa {{name}}. PV: {{pv}}','2026-09-17 06:51:17','2026-09-17 06:51:17'),(7,'bonus','fr','Bonus {{type}} de {{amount}} USD crédité.','2026-09-17 06:51:17','2026-09-17 06:51:17'),(8,'bonus','sw','Bonasi {{type}} ya {{amount}} USD imeongezwa.','2026-09-17 06:51:17','2026-09-17 06:51:17');
/*!40000 ALTER TABLE `whatsapp_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'bd_complet_deployement'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-24 15:46:12
