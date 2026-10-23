
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

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `biblioteca_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `biblioteca_db`;
DROP TABLE IF EXISTS `estudiantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `estudiantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doc` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `telefono_whatsapp` varchar(20) DEFAULT NULL,
  `whatsapp_consentimiento` tinyint(1) NOT NULL DEFAULT 0,
  `grado` varchar(50) DEFAULT NULL,
  `area` varchar(80) DEFAULT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('Estudiante','Profesor','Administrador') DEFAULT 'Estudiante',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `doc` (`doc`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `generos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `generos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `genero_literario` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre_genero` (`genero_literario`),
  UNIQUE KEY `genero_literario` (`genero_literario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `libros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `libros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(200) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `autor` varchar(100) NOT NULL,
  `editorial` varchar(120) DEFAULT NULL,
  `anio_publicacion` smallint(6) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `ubicacion` varchar(120) DEFAULT NULL,
  `sinopsis` text DEFAULT NULL,
  `ejemplares_total` int(11) NOT NULL DEFAULT 1,
  `ejemplares_disponibles` int(11) NOT NULL DEFAULT 1,
  `estado` enum('Disponible','Prestado') DEFAULT 'Disponible',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `materia` varchar(80) DEFAULT NULL,
  `genero_literario` varchar(80) DEFAULT NULL,
  `grado_escolar` varchar(30) DEFAULT NULL,
  `edad_minima` tinyint(3) unsigned DEFAULT NULL,
  `edad_maxima` tinyint(3) unsigned DEFAULT NULL,
  `categoria_prestamo` varchar(50) NOT NULL DEFAULT 'General',
  `imagen` varchar(255) DEFAULT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT 1,
  `imagen_url` varchar(500) DEFAULT NULL,
  `portada_buscada_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notificaciones_prestamo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notificaciones_prestamo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prestamo_id` int(11) NOT NULL,
  `dias_antes` tinyint(3) unsigned NOT NULL,
  `enviado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `un_recordatorio_por_prestamo` (`prestamo_id`,`dias_antes`),
  CONSTRAINT `notificaciones_prestamo_ibfk_1` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notificaciones_whatsapp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notificaciones_whatsapp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prestamo_id` int(11) NOT NULL,
  `dias_antes` tinyint(3) unsigned NOT NULL,
  `enviado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `un_whatsapp_por_prestamo` (`prestamo_id`,`dias_antes`),
  CONSTRAINT `notificaciones_whatsapp_ibfk_1` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `politicas_prestamo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `politicas_prestamo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_prestamo` varchar(50) NOT NULL DEFAULT 'General',
  `rol` enum('Estudiante','Profesor') NOT NULL,
  `dias_prestamo` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_politica_categoria_rol` (`categoria_prestamo`,`rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prestamos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prestamos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) DEFAULT NULL,
  `rol` varchar(20) DEFAULT NULL,
  `curso_area` varchar(80) DEFAULT NULL,
  `estudiante_id` int(11) NOT NULL,
  `libro_id` int(11) NOT NULL,
  `libro_titulo` varchar(200) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `fecha_solicitud` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_aprobacion` timestamp NULL DEFAULT NULL,
  `fecha_prestamo` timestamp NULL DEFAULT NULL,
  `fecha_devolucion_esperada` timestamp NULL DEFAULT NULL,
  `fecha_devolucion` date DEFAULT NULL,
  `fecha_devolucion_real` timestamp NULL DEFAULT NULL,
  `fecha_solicitud_devolucion` timestamp NULL DEFAULT NULL,
  `estado` enum('Pendiente','Aprobado','Activo','Devuelto','Rechazado','Perdido') DEFAULT 'Pendiente',
  `aprobado_por` int(11) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `tipo_reposicion` enum('Mismo tÝtulo y edici¾n','Libro equivalente') DEFAULT NULL,
  `detalle_reposicion` text DEFAULT NULL,
  `fecha_reporte_perdida` timestamp NULL DEFAULT NULL,
  `renovaciones` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `ultima_renovacion` timestamp NULL DEFAULT NULL,
  `multa` decimal(10,2) NOT NULL DEFAULT 0.00,
  `dias_retraso` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `estudiante_id` (`estudiante_id`),
  KEY `libro_id` (`libro_id`),
  KEY `aprobado_por` (`aprobado_por`),
  CONSTRAINT `prestamos_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`),
  CONSTRAINT `prestamos_ibfk_2` FOREIGN KEY (`libro_id`) REFERENCES `libros` (`id`),
  CONSTRAINT `prestamos_ibfk_3` FOREIGN KEY (`aprobado_por`) REFERENCES `estudiantes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `resenas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resenas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libro_id` int(11) NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `prestamo_id` int(11) NOT NULL,
  `calificacion` tinyint(3) unsigned NOT NULL,
  `comentario` varchar(1000) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_resena_prestamo` (`prestamo_id`),
  KEY `libro_id` (`libro_id`),
  KEY `estudiante_id` (`estudiante_id`),
  CONSTRAINT `resenas_ibfk_1` FOREIGN KEY (`libro_id`) REFERENCES `libros` (`id`) ON DELETE CASCADE,
  CONSTRAINT `resenas_ibfk_2` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `resenas_ibfk_3` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `documento` varchar(20) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('Estudiante','Profesor') NOT NULL DEFAULT 'Estudiante',
  `fecha_registro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `documento` (`documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

