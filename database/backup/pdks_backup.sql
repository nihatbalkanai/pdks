-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: pdks
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
-- Table structure for table `bildirim_kurallari`
--

DROP TABLE IF EXISTS `bildirim_kurallari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bildirim_kurallari` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firma_id` bigint unsigned NOT NULL,
  `kural_tipi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tetikleme_saati` time NOT NULL,
  `alici_telefon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alici_eposta` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `durum` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bildirim_kurallari_uuid_unique` (`uuid`),
  KEY `bildirim_kurallari_firma_id_tetikleme_saati_index` (`firma_id`,`tetikleme_saati`),
  CONSTRAINT `bildirim_kurallari_firma_id_foreign` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bildirim_kurallari`
--

LOCK TABLES `bildirim_kurallari` WRITE;
/*!40000 ALTER TABLE `bildirim_kurallari` DISABLE KEYS */;
/*!40000 ALTER TABLE `bildirim_kurallari` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bildirim_loglari`
--

DROP TABLE IF EXISTS `bildirim_loglari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bildirim_loglari` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firma_id` bigint unsigned DEFAULT NULL,
  `personel_id` bigint unsigned DEFAULT NULL,
  `kanal` enum('sms','email') COLLATE utf8mb4_unicode_ci NOT NULL,
  `alici` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `konu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mesaj` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `durum` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bekliyor',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bildirim_loglari_firma_id_kanal_created_at_index` (`firma_id`,`kanal`,`created_at`),
  CONSTRAINT `bildirim_loglari_firma_id_foreign` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bildirim_loglari`
--

LOCK TABLES `bildirim_loglari` WRITE;
/*!40000 ALTER TABLE `bildirim_loglari` DISABLE KEYS */;
/*!40000 ALTER TABLE `bildirim_loglari` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
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
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
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
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
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
-- Table structure for table `firmalar`
--

DROP TABLE IF EXISTS `firmalar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `firmalar` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `paket_id` bigint unsigned DEFAULT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firma_adi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vergi_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vergi_dairesi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adres` text COLLATE utf8mb4_unicode_ci,
  `durum` tinyint(1) NOT NULL DEFAULT '1',
  `abonelik_bitis_tarihi` date DEFAULT NULL,
  `paket_tipi` enum('Ücretsiz','Standart','Pro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Ücretsiz',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `firmalar_uuid_unique` (`uuid`),
  KEY `firmalar_vergi_no_index` (`vergi_no`),
  KEY `firmalar_paket_id_foreign` (`paket_id`),
  CONSTRAINT `firmalar_paket_id_foreign` FOREIGN KEY (`paket_id`) REFERENCES `paketler` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `firmalar`
--

LOCK TABLES `firmalar` WRITE;
/*!40000 ALTER TABLE `firmalar` DISABLE KEYS */;
INSERT INTO `firmalar` VALUES (1,NULL,'019cb90b-22d1-7294-a295-4caec7187787','Demo Şirketi A.Ş.','1234567890','Merkez','Örnek Mah. Test Sok. No:1',1,NULL,'Ücretsiz',NULL,'2026-03-04 10:30:40','2026-03-04 10:30:40');
/*!40000 ALTER TABLE `firmalar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
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
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
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
-- Table structure for table `kullanicilar`
--

DROP TABLE IF EXISTS `kullanicilar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kullanicilar` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firma_id` bigint unsigned NOT NULL,
  `sube_id` bigint unsigned DEFAULT NULL,
  `ad_soyad` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `eposta` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `sifre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'kullanici',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kullanicilar_uuid_unique` (`uuid`),
  UNIQUE KEY `kullanicilar_eposta_unique` (`eposta`),
  KEY `kullanicilar_firma_id_index` (`firma_id`),
  KEY `kullanicilar_sube_id_foreign` (`sube_id`),
  KEY `kullanicilar_firma_id_sube_id_index` (`firma_id`,`sube_id`),
  CONSTRAINT `kullanicilar_firma_id_foreign` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kullanicilar_sube_id_foreign` FOREIGN KEY (`sube_id`) REFERENCES `subeler` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kullanicilar`
--

LOCK TABLES `kullanicilar` WRITE;
/*!40000 ALTER TABLE `kullanicilar` DISABLE KEYS */;
INSERT INTO `kullanicilar` VALUES (1,'019cb90b-239a-707d-aea7-d205c87aceb8',1,NULL,'Sistem Yöneticisi','admin@admin.com',NULL,'$2y$12$QJ4VnzAR1YefoRoXSHCmjOxIyliyM.czNV7iECKhQyhu4T9J3.bb6','admin',NULL,NULL,'2026-03-04 10:30:40','2026-03-04 11:45:03'),(2,'019cb90b-73bc-73ea-b10b-21b6240b53bf',1,NULL,'Süper Admin','superadmin@admin.com',NULL,'$2y$12$O9BqqIu0/AMoiseyz2sqUeyBFPbg73SkYXu6jLh8hokmWH1qG1KBi','super_admin',NULL,NULL,'2026-03-04 10:31:01','2026-03-04 10:31:01'),(3,'019cb9d1-e906-73a0-bf12-6eb780a23dd8',1,NULL,'Demo Kullanıcı','kullanici@demo.com',NULL,'$2y$12$.bY9W8eNeK.6T6/m1z0yNeEXNAzPE9ly0JU.iu5dCCe6n/tpK74zK','kullanici',NULL,NULL,'2026-03-04 14:07:47','2026-03-04 14:07:47'),(4,'019cb9d1-e90b-71aa-9ff2-5c28fde6cff6',1,NULL,'Demo Muhasebe','muhasebe@demo.com',NULL,'$2y$12$4o6DilnmoRkhP4AZcRIqA.3v97HcaaITNgb2s6fMIA9qIhdl/.iDS','muhasebe',NULL,NULL,'2026-03-04 14:07:47','2026-03-04 14:07:47'),(5,'019cb9d1-e90e-71ae-9758-ecbae56bf547',1,NULL,'Demo İK Uzmanı','ik@demo.com',NULL,'$2y$12$Tqvn./K2SE54L2jOwnJw/uHC2g7Tk.NNJu08KIkLs3CsBf8RAOURS','ik',NULL,NULL,'2026-03-04 14:07:47','2026-03-04 14:07:47'),(6,'019cb9d1-e910-72bb-992c-37e2c5e3d568',1,NULL,'Demo İzleyici','izleyici@demo.com',NULL,'$2y$12$cjPUdyzK4MgGOvcr2x/ui.k6.k6Exwjxa1X5sgn/9Csk8T.Cmhh/K','izleyici',NULL,NULL,'2026-03-04 14:07:47','2026-03-04 14:07:47');
/*!40000 ALTER TABLE `kullanicilar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mesaj_ayarlari`
--

DROP TABLE IF EXISTS `mesaj_ayarlari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mesaj_ayarlari` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firma_id` bigint unsigned NOT NULL,
  `kanal` enum('sms','email') COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_anahtari` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gonderici_basligi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `durum` tinyint(1) NOT NULL DEFAULT '1',
  `smtp_host` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_port` int DEFAULT NULL,
  `smtp_sifreleme` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_kullanici` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_sifre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gonderen_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gonderen_ad` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sms_api_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sms_kullanici` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sms_sifre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sms_baslik` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mesaj_ayarlari_uuid_unique` (`uuid`),
  KEY `mesaj_ayarlari_firma_id_kanal_index` (`firma_id`,`kanal`),
  CONSTRAINT `mesaj_ayarlari_firma_id_foreign` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mesaj_ayarlari`
--

LOCK TABLES `mesaj_ayarlari` WRITE;
/*!40000 ALTER TABLE `mesaj_ayarlari` DISABLE KEYS */;
/*!40000 ALTER TABLE `mesaj_ayarlari` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000001_create_cache_table',1),(2,'0001_01_01_000002_create_jobs_table',1),(3,'2026_03_04_000000_proje_kurulum',1),(4,'2026_03_04_071212_create_personeller_table',1),(5,'2026_03_04_071300_create_pdks_kayitlari_table',1),(6,'2026_03_04_071636_create_personal_access_tokens_table',1),(7,'2026_03_04_073800_create_pdks_gunluk_ozetler_table',1),(8,'2026_03_04_074338_create_notifications_table',1),(9,'2026_03_04_081409_create_sistem_logs_table',1),(10,'2026_03_04_082126_add_abonelik_to_firmalar_table',1),(11,'2026_03_04_083218_create_bildirim_kuralis_table',1),(12,'2026_03_04_083218_create_mesaj_ayaris_table',1),(13,'2026_03_04_095710_update_mesaj_ayarlari_fields',1),(14,'2026_03_04_101320_create_subes_table',1),(15,'2026_03_04_101336_create_servis_table',1),(16,'2026_03_04_101344_create_servis_harekets_table',1),(17,'2026_03_04_101354_add_sube_id_to_tables',1),(18,'2026_03_04_104525_create_pakets_table',1),(19,'2026_03_04_104533_create_super_admin_yetkis_table',1),(20,'2026_03_04_104543_add_paket_id_to_firmalar',1),(21,'2026_03_04_123309_add_details_to_personeller_table',1),(22,'2026_03_04_123333_create_personel_izins_table',1),(23,'2026_03_04_123352_create_personel_avans_kesintis_table',1),(24,'2026_03_04_123414_create_personel_prim_kazancs_table',1),(25,'2026_03_04_124958_add_notlar_to_personeller_table',1),(26,'2026_03_04_125018_create_personel_zimmets_table',1),(27,'2026_03_04_185000_add_email_telefon_to_personeller',2),(28,'2026_03_04_185600_create_bildirim_loglari_table',3),(29,'2026_03_04_190000_create_zamanlanmis_bildirimler',4),(30,'2026_03_04_194700_add_smtp_sms_to_mesaj_ayarlari',5),(31,'2026_03_04_202400_create_tanim_kodlari_table',6);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paketler`
--

DROP TABLE IF EXISTS `paketler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paketler` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `paket_adi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fiyat` decimal(10,2) NOT NULL DEFAULT '0.00',
  `ozellikler` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `paketler_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paketler`
--

LOCK TABLES `paketler` WRITE;
/*!40000 ALTER TABLE `paketler` DISABLE KEYS */;
/*!40000 ALTER TABLE `paketler` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `pdks_cihazlari`
--

DROP TABLE IF EXISTS `pdks_cihazlari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pdks_cihazlari` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firma_id` bigint unsigned NOT NULL,
  `sube_id` bigint unsigned DEFAULT NULL,
  `seri_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cihaz_modeli` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `son_aktivite_tarihi` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdks_cihazlari_uuid_unique` (`uuid`),
  KEY `pdks_cihazlari_firma_id_index` (`firma_id`),
  KEY `pdks_cihazlari_seri_no_index` (`seri_no`),
  KEY `pdks_cihazlari_sube_id_foreign` (`sube_id`),
  KEY `pdks_cihazlari_firma_id_sube_id_index` (`firma_id`,`sube_id`),
  CONSTRAINT `pdks_cihazlari_firma_id_foreign` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pdks_cihazlari_sube_id_foreign` FOREIGN KEY (`sube_id`) REFERENCES `subeler` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pdks_cihazlari`
--

LOCK TABLES `pdks_cihazlari` WRITE;
/*!40000 ALTER TABLE `pdks_cihazlari` DISABLE KEYS */;
INSERT INTO `pdks_cihazlari` VALUES (1,'b4d756cb-19fd-469d-9341-283a4140f45d',1,NULL,'SERI-7345','Model X',NULL,NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50');
/*!40000 ALTER TABLE `pdks_cihazlari` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pdks_gunluk_ozetler`
--

DROP TABLE IF EXISTS `pdks_gunluk_ozetler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pdks_gunluk_ozetler` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firma_id` bigint unsigned NOT NULL,
  `personel_id` bigint unsigned NOT NULL,
  `tarih` date NOT NULL,
  `ilk_giris` datetime DEFAULT NULL,
  `son_cikis` datetime DEFAULT NULL,
  `toplam_calisma_suresi` int NOT NULL DEFAULT '0' COMMENT 'Dakika cinsinden toplam mesai',
  `durum` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'gelmedi' COMMENT 'geldi, geç kaldı, gelmedi',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdks_gunluk_ozetler_uuid_unique` (`uuid`),
  KEY `pdks_gunluk_ozetler_personel_id_foreign` (`personel_id`),
  KEY `idx_firma_tarih` (`firma_id`,`tarih`),
  KEY `idx_firma_personel_tarih` (`firma_id`,`personel_id`,`tarih`),
  KEY `idx_firma_durum` (`firma_id`,`durum`),
  CONSTRAINT `pdks_gunluk_ozetler_firma_id_foreign` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pdks_gunluk_ozetler_personel_id_foreign` FOREIGN KEY (`personel_id`) REFERENCES `personeller` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pdks_gunluk_ozetler`
--

LOCK TABLES `pdks_gunluk_ozetler` WRITE;
/*!40000 ALTER TABLE `pdks_gunluk_ozetler` DISABLE KEYS */;
/*!40000 ALTER TABLE `pdks_gunluk_ozetler` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pdks_kayitlari`
--

DROP TABLE IF EXISTS `pdks_kayitlari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pdks_kayitlari` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firma_id` bigint unsigned NOT NULL,
  `cihaz_id` bigint unsigned NOT NULL,
  `personel_id` bigint unsigned NOT NULL,
  `kayit_tarihi` timestamp NOT NULL,
  `islem_tipi` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'giriş veya çıkış',
  `ham_veri` json DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdks_kayitlari_uuid_unique` (`uuid`),
  KEY `pdks_kayitlari_cihaz_id_index` (`cihaz_id`),
  KEY `pdks_kayitlari_personel_id_index` (`personel_id`),
  KEY `idx_firma_kayit` (`firma_id`,`kayit_tarihi`),
  KEY `pdks_kayitlari_kayit_tarihi_index` (`kayit_tarihi`),
  CONSTRAINT `pdks_kayitlari_cihaz_id_foreign` FOREIGN KEY (`cihaz_id`) REFERENCES `pdks_cihazlari` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pdks_kayitlari_firma_id_foreign` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pdks_kayitlari_personel_id_foreign` FOREIGN KEY (`personel_id`) REFERENCES `personeller` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pdks_kayitlari`
--

LOCK TABLES `pdks_kayitlari` WRITE;
/*!40000 ALTER TABLE `pdks_kayitlari` DISABLE KEYS */;
INSERT INTO `pdks_kayitlari` VALUES (1,'62d4066b-88e9-417e-ba71-4c106ce76512',1,1,1,'2023-10-01 05:30:00','Giriş','{\"raw\": \"GIRIS\"}',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(2,'e6be4f52-8a5a-4e70-b6ea-061787c4368f',1,1,1,'2023-10-01 15:00:00','Çıkış','{\"raw\": \"CIKIS\"}',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(3,'2626c446-7b4e-4516-81f5-2da4ee66727d',1,1,2,'2023-10-01 05:30:00','Giriş','{\"raw\": \"GIRIS\"}',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(4,'86a8aa68-3270-402b-b625-0e39dca81d6a',1,1,2,'2023-10-01 15:00:00','Çıkış','{\"raw\": \"CIKIS\"}',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(5,'ab02e703-9e18-4fbc-b623-10034ae96453',1,1,3,'2023-10-01 05:30:00','Giriş','{\"raw\": \"GIRIS\"}',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(6,'888e1d2e-4e73-41c9-8480-90d2f3365539',1,1,3,'2023-10-01 15:00:00','Çıkış','{\"raw\": \"CIKIS\"}',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(7,'c51dfb1b-e7b5-4814-b392-db743b925eda',1,1,4,'2023-10-01 05:30:00','Giriş','{\"raw\": \"GIRIS\"}',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(8,'18d123c7-fc89-426f-9085-73662cdeaaff',1,1,4,'2023-10-01 15:00:00','Çıkış','{\"raw\": \"CIKIS\"}',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(9,'366c8d2b-a96d-4ec3-bff8-fef08377a6eb',1,1,5,'2023-10-01 05:30:00','Giriş','{\"raw\": \"GIRIS\"}',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(10,'1cab65d8-d703-42af-bf85-03eab40d4d24',1,1,5,'2023-10-01 15:00:00','Çıkış','{\"raw\": \"CIKIS\"}',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(11,'fab17c46-0036-45c4-b307-8601e90525fb',1,1,6,'2023-10-01 05:30:00','Giriş','{\"raw\": \"GIRIS\"}',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(12,'73ca82d3-3964-42fe-bdc5-ce302843888e',1,1,6,'2023-10-01 15:00:00','Çıkış','{\"raw\": \"CIKIS\"}',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(13,'3faccd98-4a7d-4605-9ff9-cf00255af4cc',1,1,7,'2023-10-01 05:30:00','Giriş','{\"raw\": \"GIRIS\"}',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(14,'0759d4fb-6128-4163-bf85-ec9cb490d608',1,1,7,'2023-10-01 15:00:00','Çıkış','{\"raw\": \"CIKIS\"}',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(15,'be892765-30b7-478d-8dd0-30cec09aa1dc',1,1,8,'2023-10-01 05:30:00','Giriş','{\"raw\": \"GIRIS\"}',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(16,'0a707b08-61c9-4b36-925c-ce3ec7a372f7',1,1,8,'2023-10-01 15:00:00','Çıkış','{\"raw\": \"CIKIS\"}',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(17,'71c08347-a2b6-4ea6-b3fc-8868f7a8b3bc',1,1,9,'2023-10-01 05:30:00','Giriş','{\"raw\": \"GIRIS\"}',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(18,'126c7caf-cbe1-4d05-837c-44690cae8c21',1,1,9,'2023-10-01 15:00:00','Çıkış','{\"raw\": \"CIKIS\"}',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(19,'a494cc2f-47b8-40d1-a9cd-94da0a30ca2a',1,1,10,'2023-10-01 05:30:00','Giriş','{\"raw\": \"GIRIS\"}',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(20,'bec17a19-167f-45d5-bdf5-c8fb0b86cdea',1,1,10,'2023-10-01 15:00:00','Çıkış','{\"raw\": \"CIKIS\"}',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50');
/*!40000 ALTER TABLE `pdks_kayitlari` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personel_avans_kesintileri`
--

DROP TABLE IF EXISTS `personel_avans_kesintileri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personel_avans_kesintileri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firma_id` bigint unsigned NOT NULL,
  `personel_id` bigint unsigned NOT NULL,
  `tarih` date NOT NULL,
  `tutar` decimal(15,2) NOT NULL,
  `aciklama` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bordro_alani` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personel_avans_kesintileri_uuid_unique` (`uuid`),
  KEY `personel_avans_kesintileri_personel_id_foreign` (`personel_id`),
  KEY `personel_avans_kesintileri_firma_id_personel_id_tarih_index` (`firma_id`,`personel_id`,`tarih`),
  CONSTRAINT `personel_avans_kesintileri_firma_id_foreign` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `personel_avans_kesintileri_personel_id_foreign` FOREIGN KEY (`personel_id`) REFERENCES `personeller` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personel_avans_kesintileri`
--

LOCK TABLES `personel_avans_kesintileri` WRITE;
/*!40000 ALTER TABLE `personel_avans_kesintileri` DISABLE KEYS */;
INSERT INTO `personel_avans_kesintileri` VALUES (1,'f47161f6-bd1a-4ba8-a26c-72734b469b35',1,1,'2023-12-15',2307.00,'Maaş avansı ödemesi','Avans','2026-03-04 10:30:50','2026-03-04 10:30:50'),(2,'94cfb1da-dcd8-49f0-aa7a-a87ccbffbe7e',1,2,'2023-12-15',1740.00,'Maaş avansı ödemesi','Avans','2026-03-04 10:30:50','2026-03-04 10:30:50'),(3,'b0c60424-82da-41e8-9c2b-71ea2139d5d6',1,3,'2023-12-15',1006.00,'Maaş avansı ödemesi','Avans','2026-03-04 10:30:50','2026-03-04 10:30:50'),(4,'5f98a6df-2b74-4488-8f97-3ad4448a5944',1,4,'2023-12-15',2779.00,'Maaş avansı ödemesi','Avans','2026-03-04 10:30:50','2026-03-04 10:30:50'),(5,'fcda3fbe-4d95-453d-b3fc-e87cb1d2203f',1,5,'2023-12-15',1668.00,'Maaş avansı ödemesi','Avans','2026-03-04 10:30:50','2026-03-04 10:30:50'),(6,'711b3ab5-1c57-46b7-a613-ea39c0d5dec1',1,6,'2023-12-15',2000.00,'Maaş avansı ödemesi','Avans','2026-03-04 10:30:50','2026-03-04 10:30:50'),(7,'e5fc7c1d-5638-444a-acb7-7152b8622b79',1,7,'2023-12-15',1185.00,'Maaş avansı ödemesi','Avans','2026-03-04 10:30:50','2026-03-04 10:30:50'),(8,'faf1f975-3a9f-4388-918a-1f736c8a684d',1,8,'2023-12-15',2413.00,'Maaş avansı ödemesi','Avans','2026-03-04 10:30:50','2026-03-04 10:30:50'),(9,'d939c24a-77c5-4d62-9054-da24835d9e2f',1,9,'2023-12-15',2723.00,'Maaş avansı ödemesi','Avans','2026-03-04 10:30:50','2026-03-04 10:30:50'),(10,'3ee12f1d-6b94-4fc0-be4e-f3a54c7acf31',1,10,'2023-12-15',1903.00,'Maaş avansı ödemesi','Avans','2026-03-04 10:30:50','2026-03-04 10:30:50');
/*!40000 ALTER TABLE `personel_avans_kesintileri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personel_izinler`
--

DROP TABLE IF EXISTS `personel_izinler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personel_izinler` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firma_id` bigint unsigned NOT NULL,
  `personel_id` bigint unsigned NOT NULL,
  `tarih` date NOT NULL,
  `tatil_tipi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `giris_saati` time DEFAULT NULL,
  `cikis_saati` time DEFAULT NULL,
  `izin_tipi` enum('gunluk','saatlik') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'gunluk',
  `aciklama` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personel_izinler_uuid_unique` (`uuid`),
  KEY `personel_izinler_personel_id_foreign` (`personel_id`),
  KEY `personel_izinler_firma_id_personel_id_tarih_index` (`firma_id`,`personel_id`,`tarih`),
  CONSTRAINT `personel_izinler_firma_id_foreign` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `personel_izinler_personel_id_foreign` FOREIGN KEY (`personel_id`) REFERENCES `personeller` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personel_izinler`
--

LOCK TABLES `personel_izinler` WRITE;
/*!40000 ALTER TABLE `personel_izinler` DISABLE KEYS */;
INSERT INTO `personel_izinler` VALUES (1,'b9aaa354-8fec-4934-b6cc-9af12ecaa26f',1,1,'2023-11-05','Yıllık İzin','08:30:00','18:00:00','gunluk','Yıllık izin kullanıldı.','2026-03-04 10:30:50','2026-03-04 10:30:50'),(2,'aa3f0cca-bfe1-48c1-8961-b22f4e8fefcc',1,2,'2023-11-05','Yıllık İzin','08:30:00','18:00:00','gunluk','Yıllık izin kullanıldı.','2026-03-04 10:30:50','2026-03-04 10:30:50'),(3,'3f55f89b-9af7-400a-89f3-5bd03e9c4cf2',1,3,'2023-11-05','Yıllık İzin','08:30:00','18:00:00','gunluk','Yıllık izin kullanıldı.','2026-03-04 10:30:50','2026-03-04 10:30:50'),(4,'eb0a1c22-4d33-4ab0-a949-f6a3205fb35a',1,4,'2023-11-05','Yıllık İzin','08:30:00','18:00:00','gunluk','Yıllık izin kullanıldı.','2026-03-04 10:30:50','2026-03-04 10:30:50'),(5,'70ac7f3b-958b-488b-837f-f27fc908b372',1,5,'2023-11-05','Yıllık İzin','08:30:00','18:00:00','gunluk','Yıllık izin kullanıldı.','2026-03-04 10:30:50','2026-03-04 10:30:50'),(6,'f2e8a4e0-e4d2-429d-bcec-69c5194071e6',1,6,'2023-11-05','Yıllık İzin','08:30:00','18:00:00','gunluk','Yıllık izin kullanıldı.','2026-03-04 10:30:50','2026-03-04 10:30:50'),(7,'754e1e62-ed23-48fa-b423-75392eefc07e',1,7,'2023-11-05','Yıllık İzin','08:30:00','18:00:00','gunluk','Yıllık izin kullanıldı.','2026-03-04 10:30:50','2026-03-04 10:30:50'),(8,'9b704b59-beaf-4a53-9ab5-0f1cc123d0a3',1,8,'2023-11-05','Yıllık İzin','08:30:00','18:00:00','gunluk','Yıllık izin kullanıldı.','2026-03-04 10:30:50','2026-03-04 10:30:50'),(9,'94a93600-561d-48f0-a8ce-e79f6c18c2e8',1,9,'2023-11-05','Yıllık İzin','08:30:00','18:00:00','gunluk','Yıllık izin kullanıldı.','2026-03-04 10:30:50','2026-03-04 10:30:50'),(10,'83ee2db6-a8aa-4c82-b080-89b2160e6a41',1,10,'2023-11-05','Yıllık İzin','08:30:00','18:00:00','gunluk','Yıllık izin kullanıldı.','2026-03-04 10:30:50','2026-03-04 10:30:50');
/*!40000 ALTER TABLE `personel_izinler` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personel_prim_kazanclari`
--

DROP TABLE IF EXISTS `personel_prim_kazanclari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personel_prim_kazanclari` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firma_id` bigint unsigned NOT NULL,
  `personel_id` bigint unsigned NOT NULL,
  `tarih` date NOT NULL,
  `tutar` decimal(15,2) NOT NULL,
  `aciklama` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bordro_alani` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personel_prim_kazanclari_uuid_unique` (`uuid`),
  KEY `personel_prim_kazanclari_personel_id_foreign` (`personel_id`),
  KEY `personel_prim_kazanclari_firma_id_personel_id_tarih_index` (`firma_id`,`personel_id`,`tarih`),
  CONSTRAINT `personel_prim_kazanclari_firma_id_foreign` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `personel_prim_kazanclari_personel_id_foreign` FOREIGN KEY (`personel_id`) REFERENCES `personeller` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personel_prim_kazanclari`
--

LOCK TABLES `personel_prim_kazanclari` WRITE;
/*!40000 ALTER TABLE `personel_prim_kazanclari` DISABLE KEYS */;
INSERT INTO `personel_prim_kazanclari` VALUES (1,'1c1dc520-4f0d-4d31-b779-677412bf833b',1,1,'2024-01-05',3130.00,'Yıl sonu performans primi','Prim','2026-03-04 10:30:50','2026-03-04 10:30:50'),(2,'332d207f-d5b8-4282-9a42-b4572bb1a683',1,2,'2024-01-05',3675.00,'Yıl sonu performans primi','Prim','2026-03-04 10:30:50','2026-03-04 10:30:50'),(3,'151c2a5a-d48e-4258-a94e-981c2c1dd96c',1,3,'2024-01-05',2586.00,'Yıl sonu performans primi','Prim','2026-03-04 10:30:50','2026-03-04 10:30:50'),(4,'73ca746c-a30b-4ef8-bb9e-9edf5cc95303',1,4,'2024-01-05',2482.00,'Yıl sonu performans primi','Prim','2026-03-04 10:30:50','2026-03-04 10:30:50'),(5,'7a75ee6c-5734-46e3-949c-3ed77940f8eb',1,5,'2024-01-05',2151.00,'Yıl sonu performans primi','Prim','2026-03-04 10:30:50','2026-03-04 10:30:50'),(6,'87249697-8529-4e12-b09e-86b84aae463c',1,6,'2024-01-05',2530.00,'Yıl sonu performans primi','Prim','2026-03-04 10:30:50','2026-03-04 10:30:50'),(7,'9ca7ab77-cced-4f19-8f4a-c56123a68cb1',1,7,'2024-01-05',2113.00,'Yıl sonu performans primi','Prim','2026-03-04 10:30:50','2026-03-04 10:30:50'),(8,'bf129745-7a12-4d7e-9aca-0df46afaafb6',1,8,'2024-01-05',2602.00,'Yıl sonu performans primi','Prim','2026-03-04 10:30:50','2026-03-04 10:30:50'),(9,'98bffa1d-9eb9-4294-8016-29e5c2b04f98',1,9,'2024-01-05',3278.00,'Yıl sonu performans primi','Prim','2026-03-04 10:30:50','2026-03-04 10:30:50'),(10,'4898743e-9e0c-4c7e-adee-ee6553cd3067',1,10,'2024-01-05',4280.00,'Yıl sonu performans primi','Prim','2026-03-04 10:30:50','2026-03-04 10:30:50');
/*!40000 ALTER TABLE `personel_prim_kazanclari` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personel_zimmetler`
--

DROP TABLE IF EXISTS `personel_zimmetler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personel_zimmetler` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firma_id` bigint unsigned NOT NULL,
  `personel_id` bigint unsigned NOT NULL,
  `kategori` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bolum_adi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aciklama` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verilis_tarihi` date DEFAULT NULL,
  `iade_tarihi` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personel_zimmetler_uuid_unique` (`uuid`),
  KEY `personel_zimmetler_personel_id_foreign` (`personel_id`),
  KEY `personel_zimmetler_firma_id_personel_id_index` (`firma_id`,`personel_id`),
  CONSTRAINT `personel_zimmetler_firma_id_foreign` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `personel_zimmetler_personel_id_foreign` FOREIGN KEY (`personel_id`) REFERENCES `personeller` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personel_zimmetler`
--

LOCK TABLES `personel_zimmetler` WRITE;
/*!40000 ALTER TABLE `personel_zimmetler` DISABLE KEYS */;
INSERT INTO `personel_zimmetler` VALUES (1,'6a1bf134-d693-4971-bcc5-270564b861fc',1,1,'Bilgisayar','Bilgi İşlem','MacBook Pro M1 16GB','2023-02-01',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(2,'f4cc6162-64e7-413f-ad6c-7649b2045be4',1,1,'Telefon','İletişim','iPhone 13 128GB','2023-02-15',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(3,'b9894e84-a1a6-47fa-986f-b1869009644c',1,2,'Bilgisayar','Bilgi İşlem','MacBook Pro M1 16GB','2023-02-01',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(4,'b14e98ae-98fd-4720-bf3b-712be8787224',1,2,'Telefon','İletişim','iPhone 13 128GB','2023-02-15',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(5,'d4861544-7744-4fbf-be9a-648e4e4dae4e',1,3,'Bilgisayar','Bilgi İşlem','MacBook Pro M1 16GB','2023-02-01',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(6,'0110c8e1-888e-435a-a980-0e734be30265',1,3,'Telefon','İletişim','iPhone 13 128GB','2023-02-15',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(7,'59c14725-2978-4989-a95c-f1a8f35b26c9',1,4,'Bilgisayar','Bilgi İşlem','MacBook Pro M1 16GB','2023-02-01',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(8,'c8e9e690-ac6b-4a2a-a69f-96e7d3eeb8b8',1,4,'Telefon','İletişim','iPhone 13 128GB','2023-02-15',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(9,'1be9381d-c4f5-491d-a25e-e22b3ca175b2',1,5,'Bilgisayar','Bilgi İşlem','MacBook Pro M1 16GB','2023-02-01',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(10,'d8a2d714-a218-4844-aa8d-e5949f70ec10',1,5,'Telefon','İletişim','iPhone 13 128GB','2023-02-15',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(11,'511d79ac-6def-48b9-9938-6b00d12dcfe4',1,6,'Bilgisayar','Bilgi İşlem','MacBook Pro M1 16GB','2023-02-01',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(12,'91a6d189-c082-423f-83b7-7ca32c809149',1,6,'Telefon','İletişim','iPhone 13 128GB','2023-02-15',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(13,'bd0008f2-7151-4303-b25b-41a362bccd65',1,7,'Bilgisayar','Bilgi İşlem','MacBook Pro M1 16GB','2023-02-01',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(14,'2f269f29-13ae-44d6-8501-d398883e62ec',1,7,'Telefon','İletişim','iPhone 13 128GB','2023-02-15',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(15,'d26d3e68-6c39-41e4-b704-c692380216ce',1,8,'Bilgisayar','Bilgi İşlem','MacBook Pro M1 16GB','2023-02-01',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(16,'ff9827fa-da4e-4885-ac69-c9012caeb0a2',1,8,'Telefon','İletişim','iPhone 13 128GB','2023-02-15',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(17,'9ae316eb-5a90-43dc-8eec-a878a2eaa33c',1,9,'Bilgisayar','Bilgi İşlem','MacBook Pro M1 16GB','2023-02-01',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(18,'9d474c1b-2797-4efa-b8e3-b5c03fabdb8c',1,9,'Telefon','İletişim','iPhone 13 128GB','2023-02-15',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(19,'ccb91d2e-dd3c-4cd9-a739-c0d9eeaa70f7',1,10,'Bilgisayar','Bilgi İşlem','MacBook Pro M1 16GB','2023-02-01',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50'),(20,'9c1202b6-5602-4154-a035-23b497689cc2',1,10,'Telefon','İletişim','iPhone 13 128GB','2023-02-15',NULL,'2026-03-04 10:30:50','2026-03-04 10:30:50');
/*!40000 ALTER TABLE `personel_zimmetler` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personeller`
--

DROP TABLE IF EXISTS `personeller`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personeller` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kart_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firma_id` bigint unsigned NOT NULL,
  `ad` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `soyad` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sube_id` bigint unsigned DEFAULT NULL,
  `servis_id` bigint unsigned DEFAULT NULL,
  `ad_soyad` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sicil_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ssk_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unvan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sirket` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bolum` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ozel_kod` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departman` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `servis_kodu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hesap_gurubu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aylik_ucret` decimal(15,2) DEFAULT NULL,
  `gunluk_ucret` decimal(15,2) DEFAULT NULL,
  `saat_1` decimal(15,5) DEFAULT NULL,
  `saat_2` decimal(15,5) DEFAULT NULL,
  `saat_3` decimal(15,5) DEFAULT NULL,
  `giris_tarihi` date DEFAULT NULL,
  `cikis_tarihi` date DEFAULT NULL,
  `resim_yolu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `durum` tinyint(1) NOT NULL DEFAULT '1',
  `notlar` text COLLATE utf8mb4_unicode_ci,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gec_kalma_bildirimi` tinyint(1) NOT NULL DEFAULT '0',
  `dogum_tarihi` date DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personeller_uuid_unique` (`uuid`),
  KEY `personeller_firma_id_index` (`firma_id`),
  KEY `personeller_sicil_no_index` (`sicil_no`),
  KEY `personeller_sube_id_foreign` (`sube_id`),
  KEY `personeller_servis_id_foreign` (`servis_id`),
  KEY `personeller_firma_id_sube_id_index` (`firma_id`,`sube_id`),
  KEY `personeller_firma_id_servis_id_index` (`firma_id`,`servis_id`),
  CONSTRAINT `personeller_firma_id_foreign` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `personeller_servis_id_foreign` FOREIGN KEY (`servis_id`) REFERENCES `servisler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `personeller_sube_id_foreign` FOREIGN KEY (`sube_id`) REFERENCES `subeler` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personeller`
--

LOCK TABLES `personeller` WRITE;
/*!40000 ALTER TABLE `personeller` DISABLE KEYS */;
INSERT INTO `personeller` VALUES (1,'803e275c-7c92-4420-a938-4e429d69b625','00010',1,'Ahmet','Yılmaz',NULL,NULL,'Ahmet Yılmaz','SICIL-1000','3492468481','Uzman','Merkez','Yazılım','OZT','AR-GE','S-01','Beyaz Yaka','Uygulanıyor',75991.63,2522.85,225.00000,300.00000,450.00000,'2023-01-21',NULL,NULL,1,'SSK BRÜT: 85.363,00\nSSK NET: 67.520,00\nBu personel sistem tarafindan otomatik test amacli uretilmistir.',NULL,NULL,0,NULL,NULL,'2026-03-04 10:30:50','2026-03-04 12:32:17'),(2,'d7b48eff-ced4-4bf5-9b42-03f688bf21ad','00011',1,'Ayşe','Kaya',NULL,NULL,'Ayşe Kaya','SICIL-1001','3420555011','Uzman','Merkez','Yazılım','OZT','AR-GE','S-01','Beyaz Yaka','Uygulanıyor',73808.79,3026.21,225.00000,300.00000,450.00000,'2023-01-12',NULL,NULL,1,'SSK BRÜT: 64.550,00\nSSK NET: 50.616,00\nBu personel sistem tarafindan otomatik test amacli uretilmistir.',NULL,NULL,0,NULL,NULL,'2026-03-04 10:30:50','2026-03-04 12:32:17'),(3,'7fcf10c8-2db1-4ee2-a26f-686f10d53acd','00012',1,'Mehmet','Demir',NULL,NULL,'Mehmet Demir','SICIL-1002','3456140823','Uzman','Merkez','Yazılım','OZT','AR-GE','S-01','Beyaz Yaka','Uygulanıyor',92060.43,2286.90,225.00000,300.00000,450.00000,'2023-01-23',NULL,NULL,1,'SSK BRÜT: 64.852,00\nSSK NET: 65.114,00\nBu personel sistem tarafindan otomatik test amacli uretilmistir.',NULL,NULL,0,NULL,NULL,'2026-03-04 10:30:50','2026-03-04 12:32:17'),(4,'d21025dd-9d02-4848-9067-dd14e9e195a1','00013',1,'Fatma','Çelik',NULL,NULL,'Fatma Çelik','SICIL-1003','3469925942','Uzman','Merkez','Yazılım','OZT','AR-GE','S-01','Beyaz Yaka','Uygulanıyor',96665.69,1979.56,225.00000,300.00000,450.00000,'2023-01-05',NULL,NULL,1,'SSK BRÜT: 67.299,00\nSSK NET: 54.708,00\nBu personel sistem tarafindan otomatik test amacli uretilmistir.',NULL,NULL,0,NULL,NULL,'2026-03-04 10:30:50','2026-03-04 12:32:17'),(5,'48245c59-c70a-4770-b473-2c1bd2c59c3a','00014',1,'Ali','Şahin',NULL,NULL,'Ali Şahin','SICIL-1004','3450258396','Uzman','Merkez','Yazılım','OZT','AR-GE','S-01','Beyaz Yaka','Uygulanıyor',60161.20,3155.68,225.00000,300.00000,450.00000,'2023-01-22',NULL,NULL,1,'SSK BRÜT: 66.172,00\nSSK NET: 52.279,00\nBu personel sistem tarafindan otomatik test amacli uretilmistir.',NULL,NULL,0,NULL,NULL,'2026-03-04 10:30:50','2026-03-04 12:32:17'),(6,'23499b0f-030b-4ac5-a1fa-5494e6789e31','00015',1,'Zeynep','Öztürk',NULL,NULL,'Zeynep Öztürk','SICIL-1005','3441624201','Uzman','Merkez','Yazılım','OZT','AR-GE','S-01','Beyaz Yaka','Uygulanıyor',54861.40,2963.29,225.00000,300.00000,450.00000,'2023-01-12',NULL,NULL,1,'SSK BRÜT: 86.812,00\nSSK NET: 64.944,00\nBu personel sistem tarafindan otomatik test amacli uretilmistir.',NULL,NULL,0,NULL,NULL,'2026-03-04 10:30:50','2026-03-04 12:32:17'),(7,'01d98f41-46fc-4439-bb8c-6b6a1e69b4da','00016',1,'Can','Yıldız',NULL,NULL,'Can Yıldız','SICIL-1006','3491689765','Uzman','Merkez','Yazılım','OZT','AR-GE','S-01','Beyaz Yaka','Uygulanıyor',92796.11,2163.48,225.00000,300.00000,450.00000,'2023-01-20',NULL,NULL,1,'SSK BRÜT: 78.180,00\nSSK NET: 63.725,00\nBu personel sistem tarafindan otomatik test amacli uretilmistir.',NULL,NULL,0,NULL,NULL,'2026-03-04 10:30:50','2026-03-04 12:32:17'),(8,'1a351f95-38d5-4ba3-a157-b29e81a22c11','00017',1,'Elif','Aydın',NULL,NULL,'Elif Aydın','SICIL-1007','3482012521','Uzman','Merkez','Yazılım','OZT','AR-GE','S-01','Beyaz Yaka','Uygulanıyor',97982.17,3338.39,225.00000,300.00000,450.00000,'2023-01-02',NULL,NULL,1,'SSK BRÜT: 62.514,00\nSSK NET: 51.220,00\nBu personel sistem tarafindan otomatik test amacli uretilmistir.',NULL,NULL,0,NULL,NULL,'2026-03-04 10:30:50','2026-03-04 12:32:17'),(9,'536686be-46a9-4edb-a789-b4f58abc88cd','00018',1,'Burak','Doğan',NULL,NULL,'Burak Doğan','SICIL-1008','3415419943','Uzman','Merkez','Yazılım','OZT','AR-GE','S-01','Beyaz Yaka','Uygulanıyor',89469.82,2344.98,225.00000,300.00000,450.00000,'2023-01-08',NULL,NULL,1,'SSK BRÜT: 77.532,00\nSSK NET: 51.462,00\nBu personel sistem tarafindan otomatik test amacli uretilmistir.',NULL,NULL,0,NULL,NULL,'2026-03-04 10:30:50','2026-03-04 12:32:17'),(10,'90e5d1ee-e7a3-4f13-9c5f-25cd02705deb','00019',1,'Ceren','Arslan',NULL,NULL,'Ceren Arslan','SICIL-1009','3463288460','Uzman','Merkez','Yazılım','OZT','AR-GE','S-01','Beyaz Yaka','Uygulanıyor',63264.85,3182.30,225.00000,300.00000,450.00000,'2023-01-08',NULL,NULL,1,'SSK BRÜT: 70.210,00\nSSK NET: 66.330,00\nBu personel sistem tarafindan otomatik test amacli uretilmistir.',NULL,NULL,0,NULL,NULL,'2026-03-04 10:30:50','2026-03-04 12:32:17');
/*!40000 ALTER TABLE `personeller` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `servis_hareketleri`
--

DROP TABLE IF EXISTS `servis_hareketleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `servis_hareketleri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firma_id` bigint unsigned NOT NULL,
  `servis_id` bigint unsigned NOT NULL,
  `personel_id` bigint unsigned NOT NULL,
  `binis_zamani` datetime NOT NULL,
  `hareket_tipi` enum('sabah_binis','aksam_binis') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `servis_hareketleri_uuid_unique` (`uuid`),
  KEY `servis_hareketleri_servis_id_foreign` (`servis_id`),
  KEY `servis_hareketleri_personel_id_foreign` (`personel_id`),
  KEY `servis_hareketleri_firma_id_servis_id_index` (`firma_id`,`servis_id`),
  KEY `servis_hareketleri_firma_id_personel_id_binis_zamani_index` (`firma_id`,`personel_id`,`binis_zamani`),
  CONSTRAINT `servis_hareketleri_firma_id_foreign` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `servis_hareketleri_personel_id_foreign` FOREIGN KEY (`personel_id`) REFERENCES `personeller` (`id`) ON DELETE CASCADE,
  CONSTRAINT `servis_hareketleri_servis_id_foreign` FOREIGN KEY (`servis_id`) REFERENCES `servisler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `servis_hareketleri`
--

LOCK TABLES `servis_hareketleri` WRITE;
/*!40000 ALTER TABLE `servis_hareketleri` DISABLE KEYS */;
/*!40000 ALTER TABLE `servis_hareketleri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `servisler`
--

DROP TABLE IF EXISTS `servisler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `servisler` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firma_id` bigint unsigned NOT NULL,
  `plaka` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sofor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guzergah` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `durum` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `servisler_uuid_unique` (`uuid`),
  KEY `servisler_firma_id_index` (`firma_id`),
  CONSTRAINT `servisler_firma_id_foreign` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `servisler`
--

LOCK TABLES `servisler` WRITE;
/*!40000 ALTER TABLE `servisler` DISABLE KEYS */;
/*!40000 ALTER TABLE `servisler` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
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
INSERT INTO `sessions` VALUES ('todU4CcmAUrA7cktYd3fja98d0fttjr0SwENSYRH',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiQVNIV1E1QkhGWk45SnNSSUZvaTVpTVBxeGNUN1YzYkZRV2lkTmhZNSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czozNToiaHR0cDovL3Bka3MudGVzdC90YW5pbS9rdWxsYW5pY2lsYXIiO3M6NToicm91dGUiO3M6MTg6InRhbmltLmt1bGxhbmljaWxhciI7fX0=',1772645597);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sistem_loglari`
--

DROP TABLE IF EXISTS `sistem_loglari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sistem_loglari` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firma_id` bigint unsigned NOT NULL,
  `kullanici_id` bigint unsigned DEFAULT NULL,
  `islem` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `detay` text COLLATE utf8mb4_unicode_ci,
  `ip_adresi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tarih` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sistem_loglari_uuid_unique` (`uuid`),
  KEY `sistem_loglari_kullanici_id_foreign` (`kullanici_id`),
  KEY `sistem_loglari_firma_id_tarih_index` (`firma_id`,`tarih`),
  KEY `sistem_loglari_islem_index` (`islem`),
  CONSTRAINT `sistem_loglari_firma_id_foreign` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sistem_loglari_kullanici_id_foreign` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=269 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sistem_loglari`
--

LOCK TABLES `sistem_loglari` WRITE;
/*!40000 ALTER TABLE `sistem_loglari` DISABLE KEYS */;
INSERT INTO `sistem_loglari` VALUES (1,'789ace86-e6ee-45a8-8847-4bd60f26d299',1,NULL,'yavas_sorgu','Süre: 2.61ms | SQL: insert into `personeller` (`firma_id`, `kart_no`, `ad`, `soyad`, `ad_soyad`, `sicil_no`, `ssk_no`, `unvan`, `sirket`, `bolum`, `ozel_kod`, `departman`, `servis_kodu`, `hesap_gurubu`, `agi`, `aylik_ucret`, `gunluk_ucret`, `saat_1`, `saat_2`, `saat_3`, `giris_tarihi`, `durum`, `notlar`, `uuid`, `updated_at`, `created_at`) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',NULL,'2026-03-04 10:30:50',NULL,NULL),(2,'6503044b-896d-497d-b811-53171095a49c',1,NULL,'yavas_sorgu','Süre: 11.54ms | SQL: insert into `kullanicilar` (`ad_soyad`, `eposta`, `sifre`, `rol`, `firma_id`, `uuid`, `updated_at`, `created_at`) values (?, ?, ?, ?, ?, ?, ?, ?)',NULL,'2026-03-04 10:31:01',NULL,NULL),(3,'a4ec06a5-94e0-4850-97b0-e14999e9ac7b',1,NULL,'yavas_sorgu','Süre: 4.3ms | SQL: insert into `sessions` (`payload`, `last_activity`, `user_id`, `ip_address`, `user_agent`, `id`) values (?, ?, ?, ?, ?, ?)',NULL,'2026-03-04 10:31:23',NULL,NULL),(4,'4467126e-fa5c-4556-853a-388deacc2eda',1,NULL,'yavas_sorgu','Süre: 12.51ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 10:31:27',NULL,NULL),(5,'7e23c3ff-8d55-4551-9028-ebae24b5bde5',1,NULL,'yavas_sorgu','Süre: 11.96ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 10:31:28',NULL,NULL),(6,'bacdd413-a40e-4969-bd3e-9e148c8401e3',1,NULL,'yavas_sorgu','Süre: 1.06ms | SQL: select * from `kullanicilar` where `eposta` = ? and `kullanicilar`.`deleted_at` is null limit 1',NULL,'2026-03-04 10:31:51',NULL,NULL),(7,'8bb59ff5-ac47-4b72-b7c1-d9b9a22a2094',1,NULL,'yavas_sorgu','Süre: 1.04ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 10:31:52',NULL,NULL),(8,'08c1a729-cb3d-4e73-b4a9-071e63a0f27c',1,NULL,'yavas_sorgu','Süre: 18.62ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 10:31:53',NULL,NULL),(9,'f7cda66a-9a58-4665-883b-d72044c26e9d',1,NULL,'yavas_sorgu','Süre: 1.74ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 10:31:58',NULL,NULL),(10,'7c476ca2-d981-4b01-b220-473537e4cdd0',1,NULL,'yavas_sorgu','Süre: 1.63ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 10:31:59',NULL,NULL),(11,'e1469363-fd7f-4408-80a1-866350a83118',1,NULL,'yavas_sorgu','Süre: 4.53ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 10:31:59',NULL,NULL),(12,'e8d26527-6744-4d22-95a7-f64a40caa78a',1,NULL,'yavas_sorgu','Süre: 1.87ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 10:32:19',NULL,NULL),(13,'3cd5f342-f7e1-4409-825e-2ca213c554f1',1,NULL,'yavas_sorgu','Süre: 17.39ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 10:32:20',NULL,NULL),(14,'6437b98d-6cfd-47ee-b2f1-6d79ca4292bf',1,NULL,'yavas_sorgu','Süre: 17.65ms | SQL: select count(*) as aggregate from `personeller` where `personeller`.`deleted_at` is null',NULL,'2026-03-04 10:32:25',NULL,NULL),(15,'1112bdc1-58ff-456a-9550-9d572311841d',1,NULL,'yavas_sorgu','Süre: 29.46ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 10:32:57',NULL,NULL),(16,'627184e1-d617-40b3-b8f3-69df6dfbf8a9',1,NULL,'yavas_sorgu','Süre: 13.53ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 10:32:59',NULL,NULL),(17,'be04ed1f-4b26-42ac-a680-a110d85115b2',1,NULL,'yavas_sorgu','Süre: 11.68ms | SQL: delete from `sessions` where `id` = ?',NULL,'2026-03-04 10:33:03',NULL,NULL),(18,'3b3642d1-6e40-4646-b9f5-e13b01ce2f46',1,NULL,'yavas_sorgu','Süre: 1.42ms | SQL: select count(*) as aggregate from `pdks_gunluk_ozetler` where `firma_id` = ? and `tarih` = ? and `ilk_giris` is not null and `son_cikis` is null and `pdks_gunluk_ozetler`.`deleted_at` is null',NULL,'2026-03-04 10:33:03',NULL,NULL),(19,'d16b95ac-3c17-444d-a6bd-d4ab6309a9f0',1,NULL,'yavas_sorgu','Süre: 1.53ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 10:33:04',NULL,NULL),(20,'85526ffe-79eb-44e8-8eb8-08b9ae49be74',1,NULL,'yavas_sorgu','Süre: 4.45ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 10:33:08',NULL,NULL),(21,'06f10157-913f-4933-a5c4-cc9a35080eaa',1,NULL,'yavas_sorgu','Süre: 1.1ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 10:33:09',NULL,NULL),(22,'c2603b2f-b2b9-40e7-a1a8-b83d0472d380',1,NULL,'yavas_sorgu','Süre: 4.53ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 10:33:10',NULL,NULL),(23,'77acee90-aeae-4ffb-a0a9-8cf09e66dab1',1,NULL,'yavas_sorgu','Süre: 4.65ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 10:33:11',NULL,NULL),(24,'a7392643-251f-43ce-b9c9-3da9df1c1f29',1,NULL,'yavas_sorgu','Süre: 1.24ms | SQL: select * from `personeller` where `personeller`.`deleted_at` is null order by `created_at` desc limit 15 offset 0',NULL,'2026-03-04 10:33:17',NULL,NULL),(25,'3347dd8a-1b71-43f4-a289-8fe19747b0ea',1,NULL,'yavas_sorgu','Süre: 1.44ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 10:33:17',NULL,NULL),(26,'75c8190b-797d-4e76-a7e8-b9bc40ce7f61',1,NULL,'yavas_sorgu','Süre: 27.61ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 10:33:44',NULL,NULL),(27,'f2347128-4c97-4701-a8d8-879a2f0c5565',1,NULL,'yavas_sorgu','Süre: 16.85ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 10:33:44',NULL,NULL),(28,'5a69d4e1-ccbc-4e9b-b76f-1e8d84c4114f',1,NULL,'yavas_sorgu','Süre: 1.56ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 10:33:46',NULL,NULL),(29,'a36749dc-5fe0-4e3b-9429-afe47a87e09a',1,NULL,'yavas_sorgu','Süre: 17.21ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 10:33:47',NULL,NULL),(30,'57259896-ad6f-49a0-b20e-96e9968c6519',1,NULL,'yavas_sorgu','Süre: 8.64ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 10:33:48',NULL,NULL),(31,'a4579378-cbd6-4d1e-8987-42c3ae10f3a3',1,NULL,'yavas_sorgu','Süre: 20.67ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 10:33:50',NULL,NULL),(32,'5fe70c0e-7aa3-4464-bb75-1335eb074f90',1,NULL,'yavas_sorgu','Süre: 21.57ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 10:33:59',NULL,NULL),(33,'2be1a973-1599-44bf-acb5-5e39e43df7d8',1,NULL,'yavas_sorgu','Süre: 2.25ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 10:34:07',NULL,NULL),(34,'ea393ab6-26ad-445c-82f6-cec837ba982c',1,NULL,'yavas_sorgu','Süre: 1.19ms | SQL: select * from `personel_avans_kesintileri` where `personel_avans_kesintileri`.`personel_id` in (0)',NULL,'2026-03-04 10:34:09',NULL,NULL),(35,'73c00bd2-e5d7-4042-95e1-61b0899cf38e',1,NULL,'yavas_sorgu','Süre: 1.32ms | SQL: select * from `personel_izinler` where `personel_izinler`.`personel_id` in (0)',NULL,'2026-03-04 10:34:09',NULL,NULL),(36,'8ef0664c-aae7-45eb-95d4-7cf50e880721',1,NULL,'yavas_sorgu','Süre: 16.75ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 10:34:12',NULL,NULL),(37,'7ea22f48-b16d-4858-be59-ecfa6d2d75ba',1,NULL,'yavas_sorgu','Süre: 0.98ms | SQL: select * from `personel_prim_kazanclari` where `personel_prim_kazanclari`.`personel_id` in (0)',NULL,'2026-03-04 10:34:14',NULL,NULL),(38,'6cfd134e-dc10-4aaf-947d-ecd3afb7b9b8',1,NULL,'yavas_sorgu','Süre: 1.33ms | SQL: select * from `personel_avans_kesintileri` where `personel_avans_kesintileri`.`personel_id` in (0)',NULL,'2026-03-04 10:34:16',NULL,NULL),(39,'dec4b516-64bc-4cd4-964f-9a53544f0d29',1,NULL,'yavas_sorgu','Süre: 27.56ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 10:34:18',NULL,NULL),(40,'f0cc0dc2-6184-4b69-a1a6-5b4cd30ba74d',1,NULL,'yavas_sorgu','Süre: 4.36ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 10:34:35',NULL,NULL),(41,'ec9ba84a-f02a-409f-baeb-56c1739d51dd',1,NULL,'yavas_sorgu','Süre: 12.86ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 10:35:44',NULL,NULL),(42,'1a6b369c-bf04-456d-93e9-ad38872d94ee',1,NULL,'yavas_sorgu','Süre: 12.58ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 10:35:45',NULL,NULL),(43,'88eca53e-572c-41c4-9864-cfe935264fdd',1,NULL,'yavas_sorgu','Süre: 0.96ms | SQL: select * from `personel_avans_kesintileri` where `personel_avans_kesintileri`.`personel_id` in (0)',NULL,'2026-03-04 10:35:48',NULL,NULL),(44,'47ec8c4b-279d-4604-9a85-a9f4fbe4aad1',1,NULL,'yavas_sorgu','Süre: 21.74ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 10:35:53',NULL,NULL),(45,'00426f93-1f2e-4121-91b1-7f29f32d56a4',1,NULL,'yavas_sorgu','Süre: 2.32ms | SQL: select * from `kullanicilar` where `id` = ? and `kullanicilar`.`deleted_at` is null limit 1',NULL,'2026-03-04 10:47:28',NULL,NULL),(46,'fa554c2d-08ea-46ca-80b5-e4bfc348bf41',1,NULL,'yavas_sorgu','Süre: 1.77ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 10:47:31',NULL,NULL),(47,'c144bdd2-434c-4595-b292-94c019c4394b',1,NULL,'yavas_sorgu','Süre: 24.36ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 10:47:34',NULL,NULL),(48,'916ca2e4-13d7-48fe-b1fc-360a49b66082',1,NULL,'yavas_sorgu','Süre: 0.76ms | SQL: select * from `personel_prim_kazanclari` where `personel_prim_kazanclari`.`personel_id` in (0)',NULL,'2026-03-04 10:48:20',NULL,NULL),(49,'fcabf300-49e2-43eb-bd37-510028d41b2b',1,NULL,'yavas_sorgu','Süre: 10.94ms | SQL: select `id`, `rol`, `firma_id` from `kullanicilar` where `eposta` = ? and `kullanicilar`.`deleted_at` is null limit 1',NULL,'2026-03-04 10:54:27',NULL,NULL),(50,'00e881b4-dab2-48c0-996c-5f0af033087c',1,NULL,'yavas_sorgu','Süre: 16.94ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 10:56:00',NULL,NULL),(51,'b6348ca6-555d-428e-9474-b8365e1bff06',1,NULL,'yavas_sorgu','Süre: 13.92ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 10:56:01',NULL,NULL),(52,'11138007-4433-448a-8a57-ec8015fe68c1',1,NULL,'yavas_sorgu','Süre: 1.02ms | SQL: select * from `personel_avans_kesintileri` where `personel_avans_kesintileri`.`personel_id` in (0)',NULL,'2026-03-04 10:56:03',NULL,NULL),(53,'1e1b1eb7-5d6e-4820-ae80-be0a3b05918d',1,NULL,'yavas_sorgu','Süre: 1.04ms | SQL: select * from `personel_zimmetler` where `personel_zimmetler`.`personel_id` in (0)',NULL,'2026-03-04 10:56:06',NULL,NULL),(54,'c4cb24c4-44ea-45ed-b166-5e00bf8c6061',1,NULL,'yavas_sorgu','Süre: 1ms | SQL: select * from (select *, row_number() over (partition by `pdks_kayitlari`.`personel_id` order by `created_at` desc) as `laravel_row` from `pdks_kayitlari` where `pdks_kayitlari`.`personel_id` in (0)) as `laravel_table` where `laravel_row` <= 50 order by `laravel_row`',NULL,'2026-03-04 10:56:07',NULL,NULL),(55,'e52a0480-4200-46f2-8d92-85b5efb8aabd',1,NULL,'yavas_sorgu','Süre: 0.79ms | SQL: select * from `personel_zimmetler` where `personel_zimmetler`.`personel_id` in (0)',NULL,'2026-03-04 10:56:07',NULL,NULL),(56,'124d7014-ed9d-43b7-b20f-133a51297067',1,NULL,'yavas_sorgu','Süre: 1.08ms | SQL: select * from `personel_avans_kesintileri` where `personel_avans_kesintileri`.`personel_id` in (0)',NULL,'2026-03-04 10:56:08',NULL,NULL),(57,'6c2537a7-7501-497d-96dd-19e09112f357',1,NULL,'yavas_sorgu','Süre: 0.87ms | SQL: select * from `personel_avans_kesintileri` where `personel_avans_kesintileri`.`personel_id` in (0)',NULL,'2026-03-04 10:56:09',NULL,NULL),(58,'98e1f321-d551-4792-b18b-0d5819053bf9',1,NULL,'yavas_sorgu','Süre: 1.02ms | SQL: select count(*) as aggregate from `personeller` where `personeller`.`deleted_at` is null',NULL,'2026-03-04 10:59:25',NULL,NULL),(59,'aad0d8d4-4441-45e9-941c-2f8a3175350d',1,NULL,'yavas_sorgu','Süre: 20.26ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 10:59:26',NULL,NULL),(60,'3416379b-1939-4b05-b454-51a9e51764b3',1,NULL,'yavas_sorgu','Süre: 1.56ms | SQL: select * from `personeller` where `personeller`.`id` = ? limit 1',NULL,'2026-03-04 10:59:28',NULL,NULL),(61,'b0147438-fb39-4d1c-8907-b37eb824cd3a',1,NULL,'yavas_sorgu','Süre: 21.83ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 10:59:30',NULL,NULL),(62,'8ca1bb8b-10e0-4db1-b135-3806577e16c2',1,NULL,'yavas_sorgu','Süre: 1.28ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 10:59:40',NULL,NULL),(63,'2efb0acf-da8e-41c9-b4b1-6e6d26de6468',1,NULL,'yavas_sorgu','Süre: 25.43ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 10:59:52',NULL,NULL),(64,'d4be68a9-5383-44c3-9189-e28fc3c1b736',1,NULL,'yavas_sorgu','Süre: 14.65ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 10:59:52',NULL,NULL),(65,'a8087255-10ea-4e2c-9fb1-6f2cc3de3270',1,NULL,'yavas_sorgu','Süre: 1.67ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 10:59:52',NULL,NULL),(66,'90227223-a744-45b2-8ea2-040c682dc474',1,NULL,'yavas_sorgu','Süre: 28.08ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:06:34',NULL,NULL),(67,'88e1dc5b-9318-451a-bda4-e6c2261c33c1',1,NULL,'yavas_sorgu','Süre: 1.99ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 11:06:35',NULL,NULL),(68,'fb6a98af-9592-43dd-aeb4-e6757323e13f',1,NULL,'yavas_sorgu','Süre: 4.74ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:06:35',NULL,NULL),(69,'999cc693-cd98-437a-9e99-86ae0cb50bf9',1,NULL,'yavas_sorgu','Süre: 25.42ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:06:43',NULL,NULL),(70,'d97c4893-a096-40dd-b0f9-b2d775f9c8e1',1,NULL,'yavas_sorgu','Süre: 28.91ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:06:44',NULL,NULL),(71,'083338c5-2e82-4a2b-9dfd-2ea5a058c300',1,NULL,'yavas_sorgu','Süre: 18.4ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:06:51',NULL,NULL),(72,'a94e726f-a406-4172-b6de-f1f612577564',1,NULL,'yavas_sorgu','Süre: 11.57ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:06:52',NULL,NULL),(73,'72cb7e61-77ae-4695-88c7-1278eab93893',1,NULL,'yavas_sorgu','Süre: 1.67ms | SQL: select * from `personeller` where `personeller`.`deleted_at` is null order by `created_at` desc limit 15 offset 0',NULL,'2026-03-04 11:07:01',NULL,NULL),(74,'8e2b77f9-22c7-4ae0-be41-decf7242bdb6',1,NULL,'yavas_sorgu','Süre: 28.53ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:07:03',NULL,NULL),(75,'9251c669-69f0-4fe2-9ed7-828c8149845f',1,NULL,'yavas_sorgu','Süre: 18.59ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:07:04',NULL,NULL),(76,'8e70c58c-53b0-432b-bd35-8bc6b42e59d1',1,NULL,'yavas_sorgu','Süre: 10.4ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:07:04',NULL,NULL),(77,'da235afc-429e-4573-bc6b-43a5374351e7',1,NULL,'yavas_sorgu','Süre: 28.29ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:07:05',NULL,NULL),(78,'f3e4aeed-2af8-4f64-8c86-f87803756ba0',1,NULL,'yavas_sorgu','Süre: 15.63ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:07:06',NULL,NULL),(79,'f41ebfe9-9305-44f4-ba21-44b21958c529',1,NULL,'yavas_sorgu','Süre: 1.13ms | SQL: select * from `personeller` where `personeller`.`deleted_at` is null order by `created_at` desc limit 15 offset 0',NULL,'2026-03-04 11:07:11',NULL,NULL),(80,'0aa11d8c-b6cb-4b02-b45b-45108253b584',1,NULL,'yavas_sorgu','Süre: 32.56ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:07:11',NULL,NULL),(81,'9567ec61-da3d-438e-8205-c92e9837243c',1,NULL,'yavas_sorgu','Süre: 0.87ms | SQL: select distinct `bolum` from `personeller` where `bolum` is not null and `personeller`.`deleted_at` is null',NULL,'2026-03-04 11:07:35',NULL,NULL),(82,'e8353f99-ce4c-4909-858f-8fb314c9b917',1,NULL,'yavas_sorgu','Süre: 4.61ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:07:39',NULL,NULL),(83,'567e4f86-422d-4aff-a166-cf39d079194e',1,NULL,'yavas_sorgu','Süre: 32.02ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:07:39',NULL,NULL),(84,'30558514-0aa5-4c8f-9935-a724830b28c3',1,NULL,'yavas_sorgu','Süre: 26.89ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:07:42',NULL,NULL),(85,'bc6d4dde-8e14-4e70-85fe-a8dadf51a89e',1,NULL,'yavas_sorgu','Süre: 5.31ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:07:43',NULL,NULL),(86,'ab39e702-c79a-4576-8413-8e55e2e8e57b',1,NULL,'yavas_sorgu','Süre: 14.49ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:07:51',NULL,NULL),(87,'9edac40a-64bd-4522-9c0b-c2fa055dc98b',1,NULL,'yavas_sorgu','Süre: 1.63ms | SQL: select count(*) as aggregate from `servisler` where `servisler`.`deleted_at` is null',NULL,'2026-03-04 11:09:39',NULL,NULL),(88,'3ae90465-0829-49bf-b6e2-8b934ee4500f',1,NULL,'yavas_sorgu','Süre: 3.48ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:09:40',NULL,NULL),(89,'65bf6399-0d2d-437a-a013-884398ce4f5b',1,NULL,'yavas_sorgu','Süre: 0.97ms | SQL: select * from `pdks_kayitlari` where `firma_id` = ? and `pdks_kayitlari`.`deleted_at` is null order by `created_at` desc limit 5',NULL,'2026-03-04 11:09:48',NULL,NULL),(90,'e774da36-f3de-4eed-ad54-b61f499d3b80',1,NULL,'yavas_sorgu','Süre: 25.8ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:09:49',NULL,NULL),(91,'a8d24a0c-cedc-460b-aba5-af9df5817168',1,NULL,'yavas_sorgu','Süre: 0.98ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:09:49',NULL,NULL),(92,'452c4d43-224f-4328-ac38-94df0807c8f1',1,NULL,'yavas_sorgu','Süre: 1.81ms | SQL: select count(*) as aggregate from `subeler` where `subeler`.`deleted_at` is null',NULL,'2026-03-04 11:10:03',NULL,NULL),(93,'504b7748-d749-43ea-9bb9-2744de62ca16',1,NULL,'yavas_sorgu','Süre: 31.71ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:10:04',NULL,NULL),(94,'2030b45a-02df-4e81-bb97-fa4b791dcb89',1,NULL,'yavas_sorgu','Süre: 15.65ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:10:07',NULL,NULL),(95,'34e57f5f-3402-4ace-8801-1847bec4554e',1,NULL,'yavas_sorgu','Süre: 1.6ms | SQL: select * from `kullanicilar` where `id` = ? and `kullanicilar`.`deleted_at` is null limit 1',NULL,'2026-03-04 11:10:08',NULL,NULL),(96,'61304bae-f50c-448f-8d6a-09c5a0033a73',1,NULL,'yavas_sorgu','Süre: 3.48ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:10:16',NULL,NULL),(97,'7bf79f53-d5ac-419e-8bcb-813d9832288f',1,NULL,'yavas_sorgu','Süre: 0.68ms | SQL: select * from `pdks_kayitlari` where `firma_id` = ? and `pdks_kayitlari`.`deleted_at` is null order by `created_at` desc limit 5',NULL,'2026-03-04 11:10:21',NULL,NULL),(98,'8427612f-f417-4e85-b047-8c21d8653346',1,NULL,'yavas_sorgu','Süre: 12.37ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:10:22',NULL,NULL),(99,'d362090e-69e4-46af-a06f-539f3bcb5677',1,NULL,'yavas_sorgu','Süre: 1.01ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:10:22',NULL,NULL),(100,'394ac8e9-27f3-4250-ac8e-a0de9d6eb3bd',1,NULL,'yavas_sorgu','Süre: 1.5ms | SQL: select count(*) as aggregate from `personeller` where `personeller`.`deleted_at` is null',NULL,'2026-03-04 11:14:34',NULL,NULL),(101,'9d86b4b0-3b83-44b0-b8ba-095942b56724',1,NULL,'yavas_sorgu','Süre: 13.37ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:14:35',NULL,NULL),(102,'eed236d1-f50b-4b0d-b4ea-164a31bc618f',1,NULL,'yavas_sorgu','Süre: 22.91ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:14:39',NULL,NULL),(103,'c4d129a6-3134-4d5d-9fcc-2a453a5f5520',1,NULL,'yavas_sorgu','Süre: 12.49ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:15:15',NULL,NULL),(104,'cfebe2e0-f9bd-41d0-9a69-0c9ceb94a9c8',1,NULL,'yavas_sorgu','Süre: 12.82ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:15:16',NULL,NULL),(105,'01ee49a3-77de-4786-b03e-0840650b534c',1,NULL,'yavas_sorgu','Süre: 22.15ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:15:27',NULL,NULL),(106,'90eff025-cfb1-440c-8b13-35bfaa73a470',1,NULL,'yavas_sorgu','Süre: 12.81ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:15:28',NULL,NULL),(107,'3f3c7252-8978-4ad7-8625-835a0f5d464b',1,NULL,'yavas_sorgu','Süre: 13.02ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:16:51',NULL,NULL),(108,'de6dc4bc-6de6-4eb5-814a-072f585f4c48',1,NULL,'yavas_sorgu','Süre: 2.55ms | SQL: select count(*) as aggregate from `personeller` where `personeller`.`deleted_at` is null',NULL,'2026-03-04 11:31:54',NULL,NULL),(109,'ed709d2d-9fc3-458d-8d4d-b7658e6ec55a',1,NULL,'yavas_sorgu','Süre: 12.58ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:31:55',NULL,NULL),(110,'6b94f74d-9a45-4949-8579-7d4e7bfcba8d',1,NULL,'yavas_sorgu','Süre: 0.91ms | SQL: select * from `personel_avans_kesintileri` where `personel_avans_kesintileri`.`personel_id` in (1)',NULL,'2026-03-04 11:32:03',NULL,NULL),(111,'4bd6fb4b-e57e-4f42-b8c5-ff07035bb802',1,NULL,'yavas_sorgu','Süre: 1.28ms | SQL: select count(*) as aggregate from `personeller` where `personeller`.`deleted_at` is null',NULL,'2026-03-04 11:33:40',NULL,NULL),(112,'8ca4014c-836d-4904-8efd-62a8f8d54e8e',1,NULL,'yavas_sorgu','Süre: 1.3ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 11:33:42',NULL,NULL),(113,'8d5c4498-2516-4923-95ca-c4c57b31dac8',1,NULL,'yavas_sorgu','Süre: 1.45ms | SQL: select * from `personeller` where `personeller`.`id` in (1, 2, 3, 4, 5, 6, 7, 8, 9, 10) and `personeller`.`deleted_at` is null',NULL,'2026-03-04 11:33:51',NULL,NULL),(114,'3d5b921c-ec36-4f7d-bf26-642a73dcb81f',1,NULL,'yavas_sorgu','Süre: 25.85ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:33:52',NULL,NULL),(115,'d6818bfa-68f2-4e5e-bd60-7cd826dccc4e',1,NULL,'yavas_sorgu','Süre: 0.79ms | SQL: select distinct `bordro_alani` from `personel_avans_kesintileri`',NULL,'2026-03-04 11:33:57',NULL,NULL),(116,'cbbccf90-777e-403d-b034-3220fbee27d8',1,NULL,'yavas_sorgu','Süre: 11.24ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:33:58',NULL,NULL),(117,'681c407c-161f-47b4-aac2-9a22f4da82ac',1,NULL,'yavas_sorgu','Süre: 0.9ms | SQL: select * from `pdks_kayitlari` where `personel_id` is null order by `created_at` desc limit 100',NULL,'2026-03-04 11:34:00',NULL,NULL),(118,'a99a077f-8acc-4424-a69f-1e7cf53db923',1,NULL,'yavas_sorgu','Süre: 1.27ms | SQL: select * from `personeller` where `personeller`.`deleted_at` is null order by `created_at` desc limit 15 offset 0',NULL,'2026-03-04 11:34:04',NULL,NULL),(119,'121596cd-ec86-46e9-9c68-3a62c77c1897',1,NULL,'yavas_sorgu','Süre: 17.29ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:34:05',NULL,NULL),(120,'49fa8960-80d1-479e-bac0-61818906852e',1,NULL,'yavas_sorgu','Süre: 15.84ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:34:18',NULL,NULL),(121,'201e6476-ba9c-43e5-a40e-51c992732f76',1,NULL,'yavas_sorgu','Süre: 1.32ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 11:34:19',NULL,NULL),(122,'edb21c20-1a98-4026-828b-8b4c878ae0a0',1,NULL,'yavas_sorgu','Süre: 1.24ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 11:34:32',NULL,NULL),(123,'58422260-95bb-4dfb-b521-0899ef1c1fd1',1,NULL,'yavas_sorgu','Süre: 1.03ms | SQL: select * from `personel_izinler` where `personel_izinler`.`personel_id` in (1)',NULL,'2026-03-04 11:34:34',NULL,NULL),(124,'0ae5de78-cfd4-4515-99f4-6792ae905afb',1,NULL,'yavas_sorgu','Süre: 0.77ms | SQL: select * from `personel_prim_kazanclari` where `personel_prim_kazanclari`.`personel_id` in (2)',NULL,'2026-03-04 11:34:37',NULL,NULL),(125,'64968adf-b051-4def-abbd-5485f6392247',1,NULL,'yavas_sorgu','Süre: 0.83ms | SQL: select * from `personel_avans_kesintileri` where `personel_avans_kesintileri`.`personel_id` in (2)',NULL,'2026-03-04 11:34:38',NULL,NULL),(126,'79bc6589-bfec-4ba7-9ba3-4162061ef83d',1,NULL,'yavas_sorgu','Süre: 1.53ms | SQL: select * from `personeller` where `personeller`.`id` in (1, 2, 3, 4, 5, 6, 7, 8, 9, 10) and `personeller`.`deleted_at` is null',NULL,'2026-03-04 11:34:42',NULL,NULL),(127,'6e2face6-abe0-4089-82ef-05ec5389dd0d',1,NULL,'yavas_sorgu','Süre: 19.37ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:34:49',NULL,NULL),(128,'7826bfdf-0069-439c-bcd2-8d6d3790d9bb',1,NULL,'yavas_sorgu','Süre: 12.68ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:34:50',NULL,NULL),(129,'3a4e18f0-edb2-48da-b047-b74bdc12c973',1,NULL,'yavas_sorgu','Süre: 25.27ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:34:51',NULL,NULL),(130,'053c997d-6955-46a0-a9af-9d22b6b5fab4',1,NULL,'yavas_sorgu','Süre: 3.13ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:34:52',NULL,NULL),(131,'974f9268-523c-43f9-beb9-9b61bde725ca',1,NULL,'yavas_sorgu','Süre: 12.82ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:35:28',NULL,NULL),(132,'45343d50-71e8-4c99-a1bf-5d2631df949a',1,NULL,'yavas_sorgu','Süre: 12.45ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:35:29',NULL,NULL),(133,'2f58d44b-629a-470f-a95d-4cb28b469f77',1,NULL,'yavas_sorgu','Süre: 0.8ms | SQL: select distinct `bordro_alani` from `personel_prim_kazanclari`',NULL,'2026-03-04 11:35:30',NULL,NULL),(134,'665acd05-5cef-4120-877b-bacf1c6a56fd',1,NULL,'yavas_sorgu','Süre: 30.74ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:40:44',NULL,NULL),(135,'a6e189aa-2c45-409f-9b3c-0d7269f472ef',1,NULL,'yavas_sorgu','Süre: 26.47ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:40:46',NULL,NULL),(136,'43c070ef-8611-4d2b-bf5a-8bbbf90e8c07',1,NULL,'yavas_sorgu','Süre: 24.27ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:40:48',NULL,NULL),(137,'96d32547-c0d7-49e7-ba0e-8d6e2e11a1f0',1,NULL,'yavas_sorgu','Süre: 17.2ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:40:49',NULL,NULL),(138,'ce4cee81-36af-4455-b680-10cd593deaa9',1,NULL,'yavas_sorgu','Süre: 1.18ms | SQL: select * from `personeller` where `personeller`.`id` = ? limit 1',NULL,'2026-03-04 11:40:52',NULL,NULL),(139,'3a2b8f43-ca71-45a5-b257-bb06d371c96a',1,NULL,'yavas_sorgu','Süre: 0.96ms | SQL: select * from `personel_avans_kesintileri` where `personel_avans_kesintileri`.`personel_id` in (2)',NULL,'2026-03-04 11:41:06',NULL,NULL),(140,'9a10b463-dd8c-4e94-933e-fba86b71dfed',1,NULL,'yavas_sorgu','Süre: 0.88ms | SQL: select * from `personel_izinler` where `personel_izinler`.`personel_id` in (3)',NULL,'2026-03-04 11:41:08',NULL,NULL),(141,'a15e590c-ff45-4ed2-9bac-1f336622cf89',1,NULL,'yavas_sorgu','Süre: 0.75ms | SQL: select * from `personel_avans_kesintileri` where `personel_avans_kesintileri`.`personel_id` in (1)',NULL,'2026-03-04 11:41:12',NULL,NULL),(142,'278c0f02-2183-4d64-9816-c4ab1375b7f6',1,NULL,'yavas_sorgu','Süre: 25.51ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:41:30',NULL,NULL),(143,'12e2823a-327c-41a7-a6a7-250eb3e3c7d4',1,NULL,'yavas_sorgu','Süre: 0.54ms | SQL: select * from `personeller` where `personeller`.`id` = ? limit 1',NULL,'2026-03-04 11:41:31',NULL,NULL),(144,'ddfbbdb4-63fb-4123-a6fe-a25c39931c69',1,NULL,'yavas_sorgu','Süre: 1ms | SQL: select * from `personel_avans_kesintileri` where `personel_avans_kesintileri`.`personel_id` in (4)',NULL,'2026-03-04 11:41:33',NULL,NULL),(145,'939cbc4f-714e-4188-b3f4-caf5e04f6b5f',1,NULL,'yavas_sorgu','Süre: 0.82ms | SQL: select distinct `bordro_alani` from `personel_prim_kazanclari`',NULL,'2026-03-04 11:41:45',NULL,NULL),(146,'5c9c665e-3384-45a0-9427-9216c87c7078',1,NULL,'yavas_sorgu','Süre: 19.49ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:41:45',NULL,NULL),(147,'8b829a74-32e1-403c-a657-30a34b8764c3',1,NULL,'yavas_sorgu','Süre: 13.22ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:41:48',NULL,NULL),(148,'68a17644-a460-42c5-9eff-426f7d4a3a2e',1,NULL,'yavas_sorgu','Süre: 26.36ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:41:48',NULL,NULL),(149,'6fe4dbb6-86ab-4a9f-88cf-563c2832a2ab',1,NULL,'yavas_sorgu','Süre: 1.42ms | SQL: select * from `personeller` where `personeller`.`id` in (1, 2, 3, 4, 5, 6, 7, 8, 9, 10) and `personeller`.`deleted_at` is null',NULL,'2026-03-04 11:41:51',NULL,NULL),(150,'7e2b1c26-532c-45ac-a487-fa59ab80728d',1,NULL,'yavas_sorgu','Süre: 12.81ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:41:52',NULL,NULL),(151,'e0e43fa5-57fb-41d6-87c0-4442a1cb7fff',1,NULL,'yavas_sorgu','Süre: 1.24ms | SQL: select * from `personeller` where `personeller`.`id` in (1, 2, 3, 4, 5, 6, 7, 8, 9, 10) and `personeller`.`deleted_at` is null',NULL,'2026-03-04 11:41:52',NULL,NULL),(152,'c798d33a-8d39-4f96-adfb-3ece94cdc5fd',1,NULL,'yavas_sorgu','Süre: 4.26ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:41:53',NULL,NULL),(153,'321e9a5d-6012-4d63-beb1-2fa645ee73e7',1,NULL,'yavas_sorgu','Süre: 18.36ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:41:56',NULL,NULL),(154,'794dd14e-978e-4a8b-86c7-da2b9cc2fa6c',1,NULL,'yavas_sorgu','Süre: 1.43ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 11:42:44',NULL,NULL),(155,'60e0142a-39e6-4473-a648-d6f9af04d5d6',1,NULL,'yavas_sorgu','Süre: 24.77ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:42:45',NULL,NULL),(156,'8f7100d1-b13f-4697-a1b2-c1ce45ccf16a',1,NULL,'yavas_sorgu','Süre: 24.09ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:42:47',NULL,NULL),(157,'8c58d74f-3b4d-4d70-9d46-9be49576592a',1,NULL,'yavas_sorgu','Süre: 0.87ms | SQL: select * from `personel_izinler` where `personel_izinler`.`personel_id` in (1)',NULL,'2026-03-04 11:43:23',NULL,NULL),(158,'aad6e182-9484-455b-ae43-dcc59dcc853b',1,NULL,'yavas_sorgu','Süre: 28.94ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:43:28',NULL,NULL),(159,'4b95467c-0d7f-4e44-bc27-d291d4489859',1,NULL,'yavas_sorgu','Süre: 18.24ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:44:35',NULL,NULL),(160,'abced2c0-678f-4b89-a72a-980f367ad244',1,NULL,'yavas_sorgu','Süre: 15.31ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:44:36',NULL,NULL),(161,'79d9a95b-4658-456c-b6f4-1e0b0b0e786e',1,NULL,'yavas_sorgu','Süre: 1.62ms | SQL: select * from `personeller` where `personeller`.`id` in (1, 2, 3, 4, 5, 6, 7, 8, 9, 10) and `personeller`.`deleted_at` is null',NULL,'2026-03-04 11:44:37',NULL,NULL),(162,'72a10fff-f8df-4f5a-8229-480a7a03471e',1,NULL,'yavas_sorgu','Süre: 1.49ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 11:44:38',NULL,NULL),(163,'b1f8da31-6083-40a1-a5d8-dab05b86bed3',1,NULL,'yavas_sorgu','Süre: 0.78ms | SQL: select `id`, `kart_no`, `ad`, `soyad` from `personeller`',NULL,'2026-03-04 11:44:39',NULL,NULL),(164,'63ee6c43-2848-4494-bfbf-154e7e9c0a0d',1,NULL,'yavas_sorgu','Süre: 0.84ms | SQL: select count(*) as aggregate from `pdks_kayitlari` where `personel_id` is null',NULL,'2026-03-04 11:44:40',NULL,NULL),(165,'2a29f1a9-22f1-4029-b130-42938c5312ff',1,NULL,'yavas_sorgu','Süre: 21.57ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 11:44:41',NULL,NULL),(166,'cd64bc4c-a3c8-4689-8c4f-acdc01575759',1,NULL,'yavas_sorgu','Süre: 1.09ms | SQL: select * from `personeller` where `personeller`.`deleted_at` is null order by `created_at` desc limit 15 offset 0',NULL,'2026-03-04 11:44:42',NULL,NULL),(167,'7050b03e-6036-4889-9fe3-02fad885e7b0',1,NULL,'yavas_sorgu','Süre: 11.69ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 11:44:43',NULL,NULL),(168,'31a94986-201b-470f-ae77-840e85bad39d',1,NULL,'yavas_sorgu','Süre: 1.52ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 11:44:47',NULL,NULL),(169,'1375898f-711b-49f8-bc7e-478dc2897175',1,NULL,'yavas_sorgu','Süre: 50.28ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:10:23',NULL,NULL),(170,'73b906a1-5679-452b-9fdb-3c1543472379',1,NULL,'yavas_sorgu','Süre: 22.17ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:10:26',NULL,NULL),(171,'f152f295-68af-4510-bf9c-49cc228aa6d4',1,NULL,'yavas_sorgu','Süre: 18.93ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:10:33',NULL,NULL),(172,'cf49fdfa-d8bf-4c83-8841-3a019af3c07d',1,NULL,'yavas_sorgu','Süre: 2.61ms | SQL: select * from `personel_izinler` where `personel_izinler`.`personel_id` in (1)',NULL,'2026-03-04 12:10:40',NULL,NULL),(173,'426580d2-1cef-46fb-9c9b-c1895d042e14',1,NULL,'yavas_sorgu','Süre: 13.75ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:11:16',NULL,NULL),(174,'2bc42c06-ce5c-4c6a-aa88-25dc8d3c6a88',1,NULL,'yavas_sorgu','Süre: 23.52ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:11:17',NULL,NULL),(175,'5f21a44f-9f7f-432d-9629-a4635461bcd3',1,NULL,'yavas_sorgu','Süre: 22.02ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:11:22',NULL,NULL),(176,'b5988295-b817-41cd-a5ef-ed70492a940b',1,NULL,'yavas_sorgu','Süre: 28.16ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:11:34',NULL,NULL),(177,'0c0ad584-d854-4c07-a698-fa511501fcf2',1,NULL,'yavas_sorgu','Süre: 12.82ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:11:36',NULL,NULL),(178,'718e207b-ed75-4aa0-ba4b-6d0fec670092',1,NULL,'yavas_sorgu','Süre: 0.85ms | SQL: select * from (select *, row_number() over (partition by `pdks_kayitlari`.`personel_id` order by `created_at` desc) as `laravel_row` from `pdks_kayitlari` where `pdks_kayitlari`.`personel_id` in (1)) as `laravel_table` where `laravel_row` <= 50 order by `laravel_row`',NULL,'2026-03-04 12:11:40',NULL,NULL),(179,'e30eea6e-e62e-4384-a1ce-02ec36fd8807',1,NULL,'yavas_sorgu','Süre: 14.38ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:11:44',NULL,NULL),(180,'89e8c488-8698-46f7-be99-86b4f78854c5',1,NULL,'yavas_sorgu','Süre: 13.96ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:12:18',NULL,NULL),(181,'2848bf09-8825-4379-9776-08d63eb95522',1,NULL,'yavas_sorgu','Süre: 1.43ms | SQL: select * from `personel_prim_kazanclari` order by `tarih` desc',NULL,'2026-03-04 12:25:59',NULL,NULL),(182,'2ff996f4-c3ea-4710-bd01-7e056332be88',1,NULL,'yavas_sorgu','Süre: 18.48ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:26:01',NULL,NULL),(183,'660ff213-2cde-497c-ae6d-9e9fcc2142d6',1,NULL,'yavas_sorgu','Süre: 1.43ms | SQL: select count(*) as aggregate from `personeller` where `personeller`.`deleted_at` is null',NULL,'2026-03-04 12:26:12',NULL,NULL),(184,'2dceaa76-646a-409f-8b11-a8739bd8dc3c',1,NULL,'yavas_sorgu','Süre: 1.21ms | SQL: select * from `personel_izinler` where `personel_izinler`.`personel_id` in (1)',NULL,'2026-03-04 12:26:16',NULL,NULL),(185,'9e870f79-7c0b-4cfe-ad6f-4a002cc558e9',1,NULL,'yavas_sorgu','Süre: 1.58ms | SQL: select * from `personeller` where `personeller`.`deleted_at` is null order by `created_at` desc limit 15 offset 0',NULL,'2026-03-04 12:26:35',NULL,NULL),(186,'2fd89fcd-80a3-41fe-b1b9-827a563da5a1',1,NULL,'yavas_sorgu','Süre: 13.88ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 12:27:35',NULL,NULL),(187,'8783bb33-4b75-414b-9d87-a36979649e72',1,NULL,'yavas_sorgu','Süre: 1.51ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 12:27:39',NULL,NULL),(188,'5cb4fa7a-fb8b-44d5-9517-2826bd13e6a0',1,NULL,'yavas_sorgu','Süre: 11.2ms | SQL: update `personeller` set `aylik_ucret` = ?, `gunluk_ucret` = ?, `personeller`.`updated_at` = ? where `id` = ?',NULL,'2026-03-04 12:27:55',NULL,NULL),(189,'f3b7da1c-a2fc-4e1d-83b8-524ceaa83217',1,NULL,'yavas_sorgu','Süre: 11.18ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 12:27:56',NULL,NULL),(190,'6c553973-9d1e-4a6e-8a01-025b2bdffa1e',1,NULL,'yavas_sorgu','Süre: 12.68ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 12:28:04',NULL,NULL),(191,'a0ad162a-5c4c-402b-880a-4715713598f2',1,NULL,'yavas_sorgu','Süre: 0.78ms | SQL: select `id`, `kart_no`, `ad`, `soyad`, `aylik_ucret`, `gunluk_ucret` from `personeller`',NULL,'2026-03-04 12:28:36',NULL,NULL),(192,'7e642ba2-6abe-4f7b-8006-9ef7c02e315f',1,NULL,'yavas_sorgu','Süre: 1.14ms | SQL: select count(*) as aggregate from `personeller` where `personeller`.`deleted_at` is null',NULL,'2026-03-04 12:28:38',NULL,NULL),(193,'aa94ed58-2b22-4cfe-9862-fd4099b943f3',1,NULL,'yavas_sorgu','Süre: 1.35ms | SQL: select * from (select *, row_number() over (partition by `pdks_kayitlari`.`personel_id` order by `created_at` desc) as `laravel_row` from `pdks_kayitlari` where `pdks_kayitlari`.`personel_id` in (1)) as `laravel_table` where `laravel_row` <= 50 order by `laravel_row`',NULL,'2026-03-04 12:28:41',NULL,NULL),(194,'d9b01da7-6bd6-44c0-af88-5f30677f2b19',1,NULL,'yavas_sorgu','Süre: 4.48ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 12:29:06',NULL,NULL),(195,'b62ac392-a75f-45f2-ba78-bd09ec743c23',1,NULL,'yavas_sorgu','Süre: 4.07ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 12:29:31',NULL,NULL),(196,'a6eec6df-363b-4750-bc74-e721cbdaf7bd',1,NULL,'yavas_sorgu','Süre: 26.66ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:29:33',NULL,NULL),(197,'7e2b248d-d1c8-4cbf-a9eb-0561de961d1b',1,NULL,'yavas_sorgu','Süre: 1.12ms | SQL: select * from (select *, row_number() over (partition by `pdks_kayitlari`.`personel_id` order by `created_at` desc) as `laravel_row` from `pdks_kayitlari` where `pdks_kayitlari`.`personel_id` in (1)) as `laravel_table` where `laravel_row` <= 50 order by `laravel_row`',NULL,'2026-03-04 12:29:43',NULL,NULL),(198,'a31c3738-9f2e-4b10-b89a-c7cebc871903',1,NULL,'yavas_sorgu','Süre: 21.7ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:29:51',NULL,NULL),(199,'d02acb08-c71c-4ec0-807e-287339ede2a6',1,NULL,'yavas_sorgu','Süre: 12.19ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 12:30:48',NULL,NULL),(200,'bfef0cb6-3f5f-4d69-9ea7-28e30b7f358c',1,NULL,'yavas_sorgu','Süre: 21.28ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:31:43',NULL,NULL),(201,'f0cff885-cf59-4b3e-b47d-37b4c920afe1',1,NULL,'yavas_sorgu','Süre: 14.63ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:31:45',NULL,NULL),(202,'380cc677-a182-42fa-a97f-e9e686449a5e',1,NULL,'yavas_sorgu','Süre: 12.04ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 12:32:04',NULL,NULL),(203,'eb509226-2b5f-4e2c-a7e1-bb47f95906de',1,NULL,'yavas_sorgu','Süre: 13.37ms | SQL: update `personeller` set `aylik_ucret` = ?, `gunluk_ucret` = ?, `personeller`.`updated_at` = ? where `id` = ?',NULL,'2026-03-04 12:32:17',NULL,NULL),(204,'25c7b463-2d8e-4548-a71c-d65b974caa03',1,NULL,'yavas_sorgu','Süre: 18.3ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:32:18',NULL,NULL),(205,'a63059b8-a9e1-48f6-ae6f-003e94d19efb',1,NULL,'yavas_sorgu','Süre: 2.74ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 12:32:21',NULL,NULL),(206,'b424add8-be99-4cf1-ac7a-4fdd9ac997e4',1,NULL,'yavas_sorgu','Süre: 10.07ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 12:32:23',NULL,NULL),(207,'2ffbb276-aa9f-428e-a56d-f0e2616341d2',1,NULL,'yavas_sorgu','Süre: 22.32ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:38:08',NULL,NULL),(208,'c2d6d86c-f346-4d66-907c-a2052d160481',1,NULL,'yavas_sorgu','Süre: 27.05ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:38:10',NULL,NULL),(209,'bce6e8c8-0c6a-46a6-83c8-8bc35e7d25b8',1,NULL,'yavas_sorgu','Süre: 1.16ms | SQL: select * from `personel_avans_kesintileri` order by `tarih` desc',NULL,'2026-03-04 12:38:11',NULL,NULL),(210,'2408664d-5284-4ff9-acb4-1d37f10ee3fd',1,NULL,'yavas_sorgu','Süre: 25.16ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:38:13',NULL,NULL),(211,'b563ec12-870e-4970-8a51-1f1f693a351a',1,NULL,'yavas_sorgu','Süre: 12.45ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 12:38:20',NULL,NULL),(212,'8dd82ba0-7cd2-452a-91bb-8ff3fe332a74',1,NULL,'yavas_sorgu','Süre: 22.79ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:38:23',NULL,NULL),(213,'27630302-838e-4708-ae28-40c6662e7055',1,NULL,'yavas_sorgu','Süre: 1.26ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 12:42:35',NULL,NULL),(214,'109e576e-4d9a-44bb-8d97-f307b68f257d',1,NULL,'yavas_sorgu','Süre: 11.94ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 12:42:38',NULL,NULL),(215,'76ace465-d0b0-4150-8090-a7f09e4a0360',1,NULL,'yavas_sorgu','Süre: 4.34ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 12:42:41',NULL,NULL),(216,'bce59d08-a5aa-427d-8d93-c22bb166f5c3',1,NULL,'yavas_sorgu','Süre: 1.28ms | SQL: select * from `pdks_kayitlari` where date(`kayit_tarihi`) = ? and `personel_id` is not null order by `personel_id` asc, `kayit_tarihi` asc',NULL,'2026-03-04 12:42:45',NULL,NULL),(217,'8be90bf7-50fe-4a25-94c2-bd31d2388250',1,NULL,'yavas_sorgu','Süre: 17.63ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:43:18',NULL,NULL),(218,'91d20e70-0585-4d2d-be3e-591541413142',1,NULL,'yavas_sorgu','Süre: 12.08ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 12:43:23',NULL,NULL),(219,'2235d82a-452e-4584-9b44-84eda95e5625',1,NULL,'yavas_sorgu','Süre: 16.13ms | SQL: select exists (select 1 from information_schema.tables where table_schema = schema() and table_name = \'migrations\' and table_type in (\'BASE TABLE\', \'SYSTEM VERSIONED\')) as `exists`',NULL,'2026-03-04 12:51:29',NULL,NULL),(220,'b4fc50f6-3659-414f-9602-428f6a66ac4e',1,NULL,'yavas_sorgu','Süre: 24.68ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:54:21',NULL,NULL),(221,'aa2e184a-fd12-408c-bcca-7b7438f0f735',1,NULL,'yavas_sorgu','Süre: 1.22ms | SQL: select `id`, `kart_no`, `ad`, `soyad`, `aylik_ucret`, `gunluk_ucret` from `personeller`',NULL,'2026-03-04 12:54:44',NULL,NULL),(222,'c610736c-2cd3-4f36-81f7-d5b87496957f',1,NULL,'yavas_sorgu','Süre: 14.93ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 12:57:06',NULL,NULL),(223,'3ca7b86c-c040-42f3-a505-7e0866b0358f',1,NULL,'yavas_sorgu','Süre: 1.38ms | SQL: select * from `personel_avans_kesintileri` where `personel_avans_kesintileri`.`personel_id` in (1)',NULL,'2026-03-04 12:57:16',NULL,NULL),(224,'43a8d099-9a1f-4b06-8a28-efc1769f4c59',1,NULL,'yavas_sorgu','Süre: 27.93ms | SQL: create table `bildirim_loglari` (`id` bigint unsigned not null auto_increment primary key, `firma_id` bigint unsigned null, `personel_id` bigint unsigned null, `kanal` enum(\'sms\', \'email\') not null, `alici` varchar(255) not null, `konu` varchar(255) null, `mesaj` text not null, `durum` varchar(255) not null default \'bekliyor\', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate \'utf8mb4_unicode_ci\'',NULL,'2026-03-04 12:57:29',NULL,NULL),(225,'00421ab7-6cf5-4054-8ee1-2acb94b15ba1',1,NULL,'yavas_sorgu','Süre: 0.7ms | SQL: select distinct `bordro_alani` from `personel_prim_kazanclari`',NULL,'2026-03-04 12:57:41',NULL,NULL),(226,'547107bb-b595-4cb0-aa52-9ef38bb6d210',1,NULL,'yavas_sorgu','Süre: 35.61ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 13:00:11',NULL,NULL),(227,'42c140d7-17ba-44d9-8c80-fc8474def61a',1,NULL,'yavas_sorgu','Süre: 26.24ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 13:00:14',NULL,NULL),(228,'99ac059e-04d8-4772-8e7c-903a89fd5f89',1,NULL,'yavas_sorgu','Süre: 18.09ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 13:00:21',NULL,NULL),(229,'9cc1793b-e519-4ad1-922b-151948834563',1,NULL,'yavas_sorgu','Süre: 0.7ms | SQL: select max(`batch`) as aggregate from `migrations`',NULL,'2026-03-04 13:06:42',NULL,NULL),(230,'ff118444-1c8e-49db-ac71-01235270d09b',1,NULL,'yavas_sorgu','Süre: 2.13ms | SQL: select * from `kullanicilar` where `id` = ? and `kullanicilar`.`deleted_at` is null limit 1',NULL,'2026-03-04 13:08:40',NULL,NULL),(231,'0a46d0d6-64af-4120-bfe6-ad3b4214785e',1,NULL,'yavas_sorgu','Süre: 36.64ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 13:08:46',NULL,NULL),(232,'5d505756-b648-4d32-b3cb-61e621647e31',1,NULL,'yavas_sorgu','Süre: 26.18ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 13:46:36',NULL,NULL),(233,'8782df4a-60a5-46bf-ad4b-e0715670e59c',1,NULL,'yavas_sorgu','Süre: 13.62ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 13:46:38',NULL,NULL),(234,'57727e16-5049-4e3a-9a72-551038670ccc',1,NULL,'yavas_sorgu','Süre: 11.39ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 13:46:44',NULL,NULL),(235,'f6d02e8c-7474-4f6f-9477-b64caa4bbc22',1,NULL,'yavas_sorgu','Süre: 1.11ms | SQL: select `id`, `kart_no`, `ad`, `soyad`, `aylik_ucret`, `gunluk_ucret` from `personeller`',NULL,'2026-03-04 13:46:47',NULL,NULL),(236,'eed3c606-c984-4668-8b38-96c38f29a547',1,NULL,'yavas_sorgu','Süre: 30.93ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 13:46:49',NULL,NULL),(237,'d2b2846c-dd3f-4470-a806-533e1017127d',1,NULL,'yavas_sorgu','Süre: 11.81ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 13:46:53',NULL,NULL),(238,'18f9db51-8572-4834-8fa6-e4fc4397e06b',1,NULL,'yavas_sorgu','Süre: 2.12ms | SQL: select * from `pdks_kayitlari` where date(`kayit_tarihi`) = ? and `personel_id` is not null order by `personel_id` asc, `kayit_tarihi` asc',NULL,'2026-03-04 13:46:56',NULL,NULL),(239,'0fd2e7b0-92f1-43de-b3bb-580d9d98e400',1,NULL,'yavas_sorgu','Süre: 11.08ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 13:47:06',NULL,NULL),(240,'2d3830a7-3f9b-4dec-9ad8-5abd627380a6',1,NULL,'yavas_sorgu','Süre: 14.6ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 13:47:10',NULL,NULL),(241,'eb79aa8d-1304-4d58-af46-6c3631970806',1,NULL,'yavas_sorgu','Süre: 2.84ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 13:47:13',NULL,NULL),(242,'d56528f8-0212-4e1e-a250-7d98fb209cab',1,NULL,'yavas_sorgu','Süre: 31.38ms | SQL: alter table `mesaj_ayarlari` add `smtp_host` varchar(255) null after `durum`',NULL,'2026-03-04 13:52:38',NULL,NULL),(243,'a9e82dd9-c244-4a39-b960-eae7edc01de1',1,NULL,'yavas_sorgu','Süre: 3.91ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 13:53:57',NULL,NULL),(244,'cc99ea28-9719-4fed-89c0-f7ae0ad79dc1',1,NULL,'yavas_sorgu','Süre: 1.42ms | SQL: select * from `mesaj_ayarlari` where `firma_id` = ? and `kanal` = ? and `mesaj_ayarlari`.`deleted_at` is null limit 1',NULL,'2026-03-04 13:54:05',NULL,NULL),(245,'2fbb2825-abb2-4f6b-8ae7-71a34da2e1f1',1,NULL,'yavas_sorgu','Süre: 12.71ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 13:54:12',NULL,NULL),(246,'aada765b-4db0-438b-bcea-22660c4f13b7',1,NULL,'yavas_sorgu','Süre: 23.74ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 13:54:29',NULL,NULL),(247,'11b889b8-70f3-46e0-96ac-153a68f2c4e8',1,NULL,'yavas_sorgu','Süre: 13.86ms | SQL: select column_name as `name`, data_type as `type_name`, column_type as `type`, collation_name as `collation`, is_nullable as `nullable`, column_default as `default`, column_comment as `comment`, generation_expression as `expression`, extra as `extra` from information_schema.columns where table_schema = schema() and table_name = \'kullanicilar\' order by ordinal_position asc',NULL,'2026-03-04 13:57:42',NULL,NULL),(248,'44153311-38e4-4f0d-a8fa-fcaf6973e913',1,NULL,'yavas_sorgu','Süre: 10.21ms | SQL: select `id`, `uuid`, `ad_soyad`, `eposta`, `rol`, `created_at`, `updated_at` from `kullanicilar` where `firma_id` = ? order by `rol` asc, `ad_soyad` asc',NULL,'2026-03-04 14:04:58',NULL,NULL),(249,'8788f460-8673-40d3-a4ec-8f731df246b0',1,NULL,'yavas_sorgu','Süre: 9.5ms | SQL: select * from `kullanicilar` where (`eposta` = ?) and `kullanicilar`.`deleted_at` is null limit 1',NULL,'2026-03-04 14:07:47',NULL,NULL),(250,'baef1ad6-5292-4168-9dde-d2b14271f10e',1,NULL,'yavas_sorgu','Süre: 12.6ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 14:08:37',NULL,NULL),(251,'126e7550-8ed7-4cb9-bb72-d06c9108b7ae',1,NULL,'yavas_sorgu','Süre: 4.47ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 14:08:59',NULL,NULL),(252,'0dd29d3b-329a-4578-a3b8-95e2fd061ffe',1,NULL,'yavas_sorgu','Süre: 1.26ms | SQL: select * from `notifications` where `notifications`.`notifiable_type` = ? and `notifications`.`notifiable_id` = ? and `notifications`.`notifiable_id` is not null and `read_at` is null order by `created_at` desc, `created_at` desc limit 5',NULL,'2026-03-04 14:12:39',NULL,NULL),(253,'c76171d1-3a4e-46cf-83d1-8f1fa8245d33',1,NULL,'yavas_sorgu','Süre: 1.96ms | SQL: select * from `mesaj_ayarlari` where `firma_id` = ? and `kanal` = ? and `mesaj_ayarlari`.`deleted_at` is null limit 1',NULL,'2026-03-04 14:14:39',NULL,NULL),(254,'6b9afa3e-3144-44ae-a722-aa6361d351ce',1,NULL,'yavas_sorgu','Süre: 25.57ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 14:14:41',NULL,NULL),(255,'053a646e-3d91-4bcb-805e-25f8ff50f158',1,NULL,'yavas_sorgu','Süre: 32.41ms | SQL: create table `tanim_kodlari` (`id` bigint unsigned not null auto_increment primary key, `firma_id` bigint unsigned not null, `tip` varchar(255) not null, `kod` varchar(50) not null, `aciklama` varchar(255) not null, `durum` tinyint(1) not null default \'1\', `deleted_at` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate \'utf8mb4_unicode_ci\'',NULL,'2026-03-04 14:28:00',NULL,NULL),(256,'48bfdf86-b1d8-427e-9824-7bd6e0788f69',1,NULL,'yavas_sorgu','Süre: 25.59ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 14:29:50',NULL,NULL),(257,'bd286a73-43b8-466d-83d0-cec2587add7a',1,NULL,'yavas_sorgu','Süre: 28.23ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 14:29:55',NULL,NULL),(258,'c2ff8851-8c20-449c-bf87-02140e634f34',1,NULL,'yavas_sorgu','Süre: 1.37ms | SQL: select * from `tanim_kodlari` where `firma_id` = ? and `tip` = ? and `tanim_kodlari`.`deleted_at` is null order by `kod` asc',NULL,'2026-03-04 14:30:04',NULL,NULL),(259,'278ec7aa-a6dc-4126-913a-adeb7355401d',1,NULL,'yavas_sorgu','Süre: 17.94ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 14:30:16',NULL,NULL),(260,'985b2f92-7d4b-4ef4-a2df-3828fd149d68',1,NULL,'yavas_sorgu','Süre: 26.76ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 14:30:22',NULL,NULL),(261,'62036547-829f-42ee-a281-d7ea693b5708',1,NULL,'yavas_sorgu','Süre: 15.26ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 14:30:26',NULL,NULL),(262,'fd62e504-3a67-4879-a240-846196535d26',1,NULL,'yavas_sorgu','Süre: 11.91ms | SQL: update `sessions` set `payload` = ?, `last_activity` = ?, `user_id` = ?, `ip_address` = ?, `user_agent` = ? where `id` = ?',NULL,'2026-03-04 14:30:30',NULL,NULL),(263,'3db62366-f5c3-4e68-8436-358a72b8e142',1,NULL,'yavas_sorgu','Süre: 20.63ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 14:30:32',NULL,NULL),(264,'90864db0-4458-46c6-93f9-3caf89ac44a9',1,NULL,'yavas_sorgu','Süre: 29.52ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 14:30:36',NULL,NULL),(265,'f575f19e-39c9-4a42-a4be-95ac70749fa3',1,NULL,'yavas_sorgu','Süre: 1.48ms | SQL: select * from `tanim_kodlari` where `firma_id` = ? and `tip` = ? and `tanim_kodlari`.`deleted_at` is null order by `kod` asc',NULL,'2026-03-04 14:30:39',NULL,NULL),(266,'dd5c059d-7cd0-4eed-84bb-0f3ed1a6f0e9',1,NULL,'yavas_sorgu','Süre: 23.94ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 14:33:12',NULL,NULL),(267,'fca681f4-7d9c-4095-92ef-2ea02382b431',1,NULL,'yavas_sorgu','Süre: 29.26ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 14:33:15',NULL,NULL),(268,'8ef98778-9878-4218-9701-b26d7fd10987',1,NULL,'yavas_sorgu','Süre: 18.11ms | SQL: select * from `sessions` where `id` = ? limit 1',NULL,'2026-03-04 14:33:17',NULL,NULL);
/*!40000 ALTER TABLE `sistem_loglari` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subeler`
--

DROP TABLE IF EXISTS `subeler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subeler` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firma_id` bigint unsigned NOT NULL,
  `sube_adi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lokasyon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `durum` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subeler_uuid_unique` (`uuid`),
  KEY `subeler_firma_id_index` (`firma_id`),
  CONSTRAINT `subeler_firma_id_foreign` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subeler`
--

LOCK TABLES `subeler` WRITE;
/*!40000 ALTER TABLE `subeler` DISABLE KEYS */;
/*!40000 ALTER TABLE `subeler` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `super_admin_yetkileri`
--

DROP TABLE IF EXISTS `super_admin_yetkileri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `super_admin_yetkileri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kullanici_id` bigint unsigned NOT NULL,
  `yetkiler` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `super_admin_yetkileri_kullanici_id_index` (`kullanici_id`),
  CONSTRAINT `super_admin_yetkileri_kullanici_id_foreign` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `super_admin_yetkileri`
--

LOCK TABLES `super_admin_yetkileri` WRITE;
/*!40000 ALTER TABLE `super_admin_yetkileri` DISABLE KEYS */;
/*!40000 ALTER TABLE `super_admin_yetkileri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tanim_kodlari`
--

DROP TABLE IF EXISTS `tanim_kodlari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tanim_kodlari` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firma_id` bigint unsigned NOT NULL,
  `tip` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kod` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `aciklama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `durum` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tanim_kodlari_firma_id_tip_kod_unique` (`firma_id`,`tip`,`kod`),
  KEY `tanim_kodlari_firma_id_tip_index` (`firma_id`,`tip`),
  CONSTRAINT `tanim_kodlari_firma_id_foreign` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tanim_kodlari`
--

LOCK TABLES `tanim_kodlari` WRITE;
/*!40000 ALTER TABLE `tanim_kodlari` DISABLE KEYS */;
INSERT INTO `tanim_kodlari` VALUES (1,1,'sirket','0','ERBAG ELEKTRONİK',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08'),(2,1,'departman','1','GENEL MÜDÜRLÜK',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08'),(3,1,'departman','2','İNSAN KAYNAKLARI',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08'),(4,1,'departman','3','MUHASEBE',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08'),(5,1,'departman','4','ÜRETİM',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08'),(6,1,'departman','5','SATIŞ VE PAZARLAMA',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08'),(7,1,'bolum','1','MUHASEBE',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08'),(8,1,'bolum','2','SATIŞ VE PAZARLAMA',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08'),(9,1,'bolum','3','SEKRETERYA',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08'),(10,1,'bolum','4','GRAFİK TASARIM',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08'),(11,1,'bolum','5','USTA BAŞI',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08'),(12,1,'bolum','6','BASKI ELEMANI',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08'),(13,1,'bolum','7','MONTAJ ELEMANI',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08'),(14,1,'bolum','8','KESİM ELEMANI',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08'),(15,1,'bolum','9','ÇAY-TEMİZLİK',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08'),(16,1,'bolum','10','ŞUBE ELEMANI',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08'),(17,1,'bolum','11','DEPO',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08'),(18,1,'odeme','1','TEB',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08'),(19,1,'odeme','2','HALKBANK',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08'),(20,1,'odeme','3','ELDEN',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08'),(21,1,'servis','1','MERKEZ SERVİS',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08'),(22,1,'servis','2','BÖLGE SERVİS',1,NULL,'2026-03-04 14:28:08','2026-03-04 14:28:08');
/*!40000 ALTER TABLE `tanim_kodlari` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zamanlanmis_bildirimler`
--

DROP TABLE IF EXISTS `zamanlanmis_bildirimler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `zamanlanmis_bildirimler` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firma_id` bigint unsigned NOT NULL,
  `ad` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tip` enum('maas_gunu','dogum_gunu','bayram','ozel_tarih','genel') COLLATE utf8mb4_unicode_ci NOT NULL,
  `kanal` enum('sms','email','her_ikisi') COLLATE utf8mb4_unicode_ci NOT NULL,
  `konu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mesaj_sablonu` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `cron_ifadesi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gun` tinyint DEFAULT NULL,
  `saat` time NOT NULL DEFAULT '09:00:00',
  `ozel_tarih` date DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `son_calisma` timestamp NULL DEFAULT NULL,
  `toplam_gonderim` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `zamanlanmis_bildirimler_firma_id_tip_aktif_index` (`firma_id`,`tip`,`aktif`),
  CONSTRAINT `zamanlanmis_bildirimler_firma_id_foreign` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zamanlanmis_bildirimler`
--

LOCK TABLES `zamanlanmis_bildirimler` WRITE;
/*!40000 ALTER TABLE `zamanlanmis_bildirimler` DISABLE KEYS */;
/*!40000 ALTER TABLE `zamanlanmis_bildirimler` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'pdks'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-04 20:38:55
