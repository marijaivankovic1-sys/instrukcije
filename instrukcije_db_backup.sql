-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: instrukcije_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `dozvole`
--

DROP TABLE IF EXISTS `dozvole`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dozvole` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `naziv` varchar(100) NOT NULL,
  `opis` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `naziv` (`naziv`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dozvole`
--

LOCK TABLES `dozvole` WRITE;
/*!40000 ALTER TABLE `dozvole` DISABLE KEYS */;
INSERT INTO `dozvole` VALUES (1,'upravljanje_korisnicima','Dodavanje, uređivanje, brisanje korisnika i promjena uloge'),(2,'upravljanje_predmetima','Dodavanje, uređivanje i brisanje predmeta'),(3,'upravljanje_terminima','Dodavanje, uređivanje i brisanje termina'),(4,'pregled_svih_rezervacija','Pregled svih rezervacija u sustavu'),(5,'obrada_rezervacija','Prihvaćanje i odbijanje zahtjeva za rezervaciju'),(6,'rezerviranje_termina','Slanje zahtjeva za rezervaciju termina'),(7,'pregled_vlastitih_rezervacija','Pregled vlastitih rezervacija'),(8,'otkazivanje_rezervacije','Otkazivanje vlastite rezervacije'),(9,'pisanje_recenzije','Dodavanje recenzije instruktoru'),(10,'pregled_termina','Pregled dostupnih termina'),(11,'pregled_recenzija','Pregled recenzija'),(12,'upravljanje_uloga_dozvola','Upravljanje ulogama i dozvolama');
/*!40000 ALTER TABLE `dozvole` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `korisnici`
--

DROP TABLE IF EXISTS `korisnici`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `korisnici` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ime` varchar(50) NOT NULL,
  `prezime` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `lozinka` varchar(255) NOT NULL,
  `uloga` enum('Admin','Tutor','Student') NOT NULL DEFAULT 'Student',
  `status` enum('aktivan','neaktivan') NOT NULL DEFAULT 'aktivan',
  `datum_registracije` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `korisnici`
--

LOCK TABLES `korisnici` WRITE;
/*!40000 ALTER TABLE `korisnici` DISABLE KEYS */;
INSERT INTO `korisnici` VALUES (1,'Marko','Matić','marko@test.com','$2y$10$i.SdzVfrIbbOhCNFINVKkO4IjtZAMtUKNfSWc.gjI4cj/wwTweSLG','Student','aktivan','2026-07-28 13:39:57'),(2,'Ivana','Ivić','ivana@test.com','$2y$10$ndsYHWLd.qwDdi4KSpADtOw5B2SMGr.18SunTAf6p3Sh9DjQWG28S','Admin','aktivan','2026-07-28 13:40:51'),(3,'Ana','Anić','ana@test.com','$2y$10$eQ3nM8lrN8j7T1yeTFCEWOS5xqyDqPh999cxCN/Q9yQUJBEJkbwx2','Tutor','aktivan','2026-07-28 18:10:43'),(4,'Ante','Antić','ante@test.com','$2y$10$2Wn8yV43xsl0LG1OuRWtNe1xCxUpK1fydgp3nUDY/S3tLHiCgob8K','Tutor','aktivan','2026-09-08 09:38:16'),(7,'ivan','ivanovic','ivan@test.com','$2y$10$JIohH3A.K5tYdgQiVROID.zW51sFSb1AaQKuDUUSsNvIywzH/UsL.','Student','aktivan','2026-09-12 10:19:32');
/*!40000 ALTER TABLE `korisnici` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `korisnik_uloga`
--

DROP TABLE IF EXISTS `korisnik_uloga`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `korisnik_uloga` (
  `korisnik_id` int(11) NOT NULL,
  `uloga_id` int(11) NOT NULL,
  PRIMARY KEY (`korisnik_id`,`uloga_id`),
  KEY `fk_ku_uloga` (`uloga_id`),
  CONSTRAINT `fk_ku_korisnik` FOREIGN KEY (`korisnik_id`) REFERENCES `korisnici` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ku_uloga` FOREIGN KEY (`uloga_id`) REFERENCES `uloge` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `korisnik_uloga`
--

LOCK TABLES `korisnik_uloga` WRITE;
/*!40000 ALTER TABLE `korisnik_uloga` DISABLE KEYS */;
INSERT INTO `korisnik_uloga` VALUES (1,4),(2,2),(3,3),(4,3);
/*!40000 ALTER TABLE `korisnik_uloga` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kosarica_stavke`
--

DROP TABLE IF EXISTS `kosarica_stavke`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kosarica_stavke` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `korisnik_id` int(11) NOT NULL,
  `zbirka_id` int(11) NOT NULL,
  `kolicina` int(11) NOT NULL DEFAULT 1,
  `datum_dodavanja` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_korisnik_zbirka` (`korisnik_id`,`zbirka_id`),
  KEY `fk_kosarica_zbirka` (`zbirka_id`),
  CONSTRAINT `fk_kosarica_korisnik` FOREIGN KEY (`korisnik_id`) REFERENCES `korisnici` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_kosarica_zbirka` FOREIGN KEY (`zbirka_id`) REFERENCES `zbirke` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kosarica_stavke`
--

LOCK TABLES `kosarica_stavke` WRITE;
/*!40000 ALTER TABLE `kosarica_stavke` DISABLE KEYS */;
/*!40000 ALTER TABLE `kosarica_stavke` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `predmeti`
--

DROP TABLE IF EXISTS `predmeti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `predmeti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `naziv` varchar(100) NOT NULL,
  `opis` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `predmeti`
--

LOCK TABLES `predmeti` WRITE;
/*!40000 ALTER TABLE `predmeti` DISABLE KEYS */;
INSERT INTO `predmeti` VALUES (1,'Matematika','Pripreme za kolokvije, ispite, državnu maturu i srednjoškolsko gradivo iz matematike.'),(2,'Programiranje','Osnove C++, PHP, HTML/CSS i rad s bazama podataka.'),(3,'Fizika','Rješavanje zadataka i priprema za pismeni dio ispita.'),(7,'Gospodarska matematika','Rješavanje zadataka koje prate gradivo predmeta Gospodarska matematika.');
/*!40000 ALTER TABLE `predmeti` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recenzije`
--

DROP TABLE IF EXISTS `recenzije`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recenzije` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tutor_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `ocjena` int(11) NOT NULL CHECK (`ocjena` between 1 and 5),
  `komentar` text DEFAULT NULL,
  `datum_recenzije` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `tutor_id` (`tutor_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `recenzije_ibfk_1` FOREIGN KEY (`tutor_id`) REFERENCES `korisnici` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recenzije_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `korisnici` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recenzije`
--

LOCK TABLES `recenzije` WRITE;
/*!40000 ALTER TABLE `recenzije` DISABLE KEYS */;
INSERT INTO `recenzije` VALUES (1,3,1,5,'Detaljno i precizno objašnjavanje dogovorenog gradiva.','2026-07-28 18:11:42'),(11,3,1,5,'Odlična instrukcija i vrlo jasno objašnjenje.','2026-09-11 21:11:30');
/*!40000 ALTER TABLE `recenzije` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rezervacije`
--

DROP TABLE IF EXISTS `rezervacije`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rezervacije` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `termin_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `napomena` text DEFAULT NULL,
  `privitak_putanja` varchar(255) DEFAULT NULL,
  `status` enum('na čekanju','prihvaćeno','odbijeno') DEFAULT 'na čekanju',
  `datum_rezervacije` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `termin_id` (`termin_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `rezervacije_ibfk_1` FOREIGN KEY (`termin_id`) REFERENCES `termini` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rezervacije_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `korisnici` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rezervacije`
--

LOCK TABLES `rezervacije` WRITE;
/*!40000 ALTER TABLE `rezervacije` DISABLE KEYS */;
INSERT INTO `rezervacije` VALUES (9,9,1,'','uploads/ef7a5d9bfd15d60fb6feae6924d6ae05.pdf','prihvaćeno','2026-09-09 08:42:48'),(11,11,1,'Ispit znanja je 15.10.2026.',NULL,'na čekanju','2026-09-09 14:20:13'),(14,13,1,'',NULL,'prihvaćeno','2026-09-09 17:29:46'),(15,16,1,'',NULL,'na čekanju','2026-09-09 17:42:12'),(17,10,1,'Trebam pomoć s kvadratnim jednadžbama.',NULL,'prihvaćeno','2026-09-12 09:55:30');
/*!40000 ALTER TABLE `rezervacije` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `termini`
--

DROP TABLE IF EXISTS `termini`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `termini` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tutor_id` int(11) NOT NULL,
  `predmet_id` int(11) NOT NULL,
  `datum` date NOT NULL,
  `vrijeme_od` time NOT NULL,
  `vrijeme_do` time NOT NULL,
  `cijena` decimal(8,2) NOT NULL,
  `status` enum('slobodan','rezerviran','otkazan') DEFAULT 'slobodan',
  PRIMARY KEY (`id`),
  KEY `tutor_id` (`tutor_id`),
  KEY `predmet_id` (`predmet_id`),
  CONSTRAINT `termini_ibfk_1` FOREIGN KEY (`tutor_id`) REFERENCES `korisnici` (`id`) ON DELETE CASCADE,
  CONSTRAINT `termini_ibfk_2` FOREIGN KEY (`predmet_id`) REFERENCES `predmeti` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `termini`
--

LOCK TABLES `termini` WRITE;
/*!40000 ALTER TABLE `termini` DISABLE KEYS */;
INSERT INTO `termini` VALUES (7,3,2,'2026-09-11','09:30:00','10:30:00',25.00,''),(8,2,3,'2026-09-25','11:30:00','13:00:00',30.00,''),(9,3,3,'2026-09-28','08:00:00','09:00:00',20.00,'rezerviran'),(10,3,2,'2026-10-14','15:00:00','16:30:00',30.00,'slobodan'),(11,2,3,'2026-10-11','09:00:00','10:00:00',25.00,'slobodan'),(13,3,1,'2026-09-20','09:30:00','10:30:00',20.00,'rezerviran'),(14,3,2,'2026-09-25','15:15:00','16:00:00',20.00,'slobodan'),(16,3,7,'2026-09-28','10:00:00','11:30:00',30.00,'slobodan'),(19,3,1,'2026-09-28','17:00:00','18:00:00',25.00,'slobodan');
/*!40000 ALTER TABLE `termini` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tutor_predmet`
--

DROP TABLE IF EXISTS `tutor_predmet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tutor_predmet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tutor_id` int(11) NOT NULL,
  `predmet_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tutor_id` (`tutor_id`),
  KEY `predmet_id` (`predmet_id`),
  CONSTRAINT `tutor_predmet_ibfk_1` FOREIGN KEY (`tutor_id`) REFERENCES `korisnici` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tutor_predmet_ibfk_2` FOREIGN KEY (`predmet_id`) REFERENCES `predmeti` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tutor_predmet`
--

LOCK TABLES `tutor_predmet` WRITE;
/*!40000 ALTER TABLE `tutor_predmet` DISABLE KEYS */;
INSERT INTO `tutor_predmet` VALUES (1,3,1),(3,4,3);
/*!40000 ALTER TABLE `tutor_predmet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `uloga_dozvola`
--

DROP TABLE IF EXISTS `uloga_dozvola`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uloga_dozvola` (
  `uloga_id` int(11) NOT NULL,
  `dozvola_id` int(11) NOT NULL,
  PRIMARY KEY (`uloga_id`,`dozvola_id`),
  KEY `fk_ud_dozvola` (`dozvola_id`),
  CONSTRAINT `fk_ud_dozvola` FOREIGN KEY (`dozvola_id`) REFERENCES `dozvole` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ud_uloga` FOREIGN KEY (`uloga_id`) REFERENCES `uloge` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uloga_dozvola`
--

LOCK TABLES `uloga_dozvola` WRITE;
/*!40000 ALTER TABLE `uloga_dozvola` DISABLE KEYS */;
INSERT INTO `uloga_dozvola` VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(1,10),(1,11),(1,12),(2,1),(2,2),(2,3),(2,4),(2,5),(2,10),(2,11),(2,12),(3,3),(3,5),(3,10),(3,11),(4,6),(4,7),(4,8),(4,9),(4,10),(4,11),(5,10),(5,11);
/*!40000 ALTER TABLE `uloga_dozvola` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `uloge`
--

DROP TABLE IF EXISTS `uloge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uloge` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `naziv` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `naziv` (`naziv`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uloge`
--

LOCK TABLES `uloge` WRITE;
/*!40000 ALTER TABLE `uloge` DISABLE KEYS */;
INSERT INTO `uloge` VALUES (2,'Administrator'),(5,'Gost'),(4,'Student'),(1,'Super Administrator'),(3,'Tutor');
/*!40000 ALTER TABLE `uloge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zbirke`
--

DROP TABLE IF EXISTS `zbirke`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zbirke` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `predmet_id` int(11) NOT NULL,
  `naziv` varchar(150) NOT NULL,
  `opis` text DEFAULT NULL,
  `cijena` decimal(10,2) NOT NULL DEFAULT 0.00,
  `slika_putanja` varchar(255) DEFAULT NULL,
  `pdf_putanja` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_zbirke_predmet` (`predmet_id`),
  CONSTRAINT `fk_zbirke_predmet` FOREIGN KEY (`predmet_id`) REFERENCES `predmeti` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zbirke`
--

LOCK TABLES `zbirke` WRITE;
/*!40000 ALTER TABLE `zbirke` DISABLE KEYS */;
INSERT INTO `zbirke` VALUES (1,1,'Zbirka riješenih zadataka za prvi razred gimnazije, Matematika 1','Zbirka s riješenim zadacima iz Matematike 1, Dakić, Elezović',60.00,'uploads/zbirke/matematika1.png',NULL),(2,1,'Zbirka zadataka s pismenih ispita za drugi razred gimnazije','Zbirka zadataka s primjerima pismenih ispita za drugi razred općih gimnazija',30.00,'uploads/zbirke/matematika2.png',NULL);
/*!40000 ALTER TABLE `zbirke` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-24 22:05:50
