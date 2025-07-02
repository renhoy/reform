-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:8889
-- Tiempo de generación: 02-07-2025 a las 16:15:43
-- Versión del servidor: 8.0.40
-- Versión de PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `budget_form_service`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `budgets`
--

CREATE TABLE `budgets` (
  `id` int NOT NULL,
  `uuid` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tariff_id` int NOT NULL,
  `json_observations` json DEFAULT NULL,
  `json_tariff_data` json NOT NULL,
  `client_type` enum('particular','autonomo','empresa') COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_nif_nie` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_web` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_address` text COLLATE utf8mb4_unicode_ci,
  `client_postal_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_locality` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_province` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_acceptance` tinyint(1) DEFAULT '0',
  `json_budget_data` json NOT NULL,
  `status` enum('draft','pending','sent','approved','rejected','expired') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `total` decimal(10,2) DEFAULT '0.00',
  `iva` decimal(10,2) DEFAULT '0.00',
  `base` decimal(10,2) DEFAULT '0.00',
  `pdf_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `validity_days` int DEFAULT '30',
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `budgets`
--

INSERT INTO `budgets` (`id`, `uuid`, `tariff_id`, `json_observations`, `json_tariff_data`, `client_type`, `client_name`, `client_nif_nie`, `client_phone`, `client_email`, `client_web`, `client_address`, `client_postal_code`, `client_locality`, `client_province`, `client_acceptance`, `json_budget_data`, `status`, `total`, `iva`, `base`, `pdf_url`, `start_date`, `end_date`, `validity_days`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 'b02a6e3c-55f6-11f0-bb57-234f23c368be', 1, NULL, '{\"name\": \"Jeyca Tecnología y Medio Ambiente, S.L.\"}', 'empresa', 'Cliente Ejemplo S.L.', 'B12345678', '123456789', 'cliente@ejemplo.com', NULL, 'Calle Ejemplo, 123', NULL, NULL, NULL, 0, '{\"items\": [], \"totals\": {\"iva\": 21.00, \"base\": 100.00, \"final\": 121.00}}', 'pending', 121.00, 21.00, 100.00, NULL, NULL, NULL, 30, 1, '2025-06-30 21:10:45', '2025-06-30 21:10:45');

--
-- Disparadores `budgets`
--
DELIMITER $$
CREATE TRIGGER `budget_dates_trigger` BEFORE INSERT ON `budgets` FOR EACH ROW BEGIN
    IF NEW.start_date IS NULL THEN
        SET NEW.start_date = CURDATE();
    END IF;
    
    IF NEW.end_date IS NULL AND NEW.validity_days IS NOT NULL THEN
        SET NEW.end_date = DATE_ADD(NEW.start_date, INTERVAL NEW.validity_days DAY);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tariffs`
--

CREATE TABLE `tariffs` (
  `id` int NOT NULL,
  `uuid` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `logo_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nif` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `template` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '41200-00001',
  `primary_color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#e8951c',
  `secondary_color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#109c61',
  `summary_note` text COLLATE utf8mb4_unicode_ci,
  `conditions_note` text COLLATE utf8mb4_unicode_ci,
  `access` enum('public','private') COLLATE utf8mb4_unicode_ci DEFAULT 'private',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `legal_note` text COLLATE utf8mb4_unicode_ci,
  `json_tariff_data` json NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tariffs`
--

INSERT INTO `tariffs` (`id`, `uuid`, `title`, `description`, `logo_url`, `name`, `nif`, `address`, `contact`, `template`, `primary_color`, `secondary_color`, `summary_note`, `conditions_note`, `access`, `status`, `legal_note`, `json_tariff_data`, `user_id`, `created_at`) VALUES
(1, 'b028f2c8-55f6-11f0-bb57-234f23c368be', 'Tarifa Ejemplo', 'Tarifa de ejemplo para pruebas', NULL, 'Jeyca Tecnología y Medio Ambiente, S.L.', 'B91707703', 'C/ Pimienta, 6 - 41200, Alcalá del Río (Sevilla)', '955 650 626 - soporte@jeyca.net', '41200-00001', '#e8951c', '#109c61', NULL, NULL, 'private', 'active', 'Al marcar esta casilla acepta nuestra política de privacidad y el tratamiento de sus datos personales.', '[{\"id\": \"1\", \"name\": \"Instalaciones Eléctricas\", \"level\": \"chapter\", \"amount\": \"0.00\"}, {\"id\": \"1.1\", \"name\": \"Cableado Estructurado\", \"level\": \"subchapter\", \"amount\": \"0.00\"}, {\"id\": \"1.1.1\", \"name\": \"Cableado de Baja Tensión\", \"level\": \"section\", \"amount\": \"0.00\"}, {\"id\": \"1.1.1.1\", \"pvp\": \"15.00\", \"name\": \"Instalación de Cable UTP Cat6\", \"unit\": \"m\", \"level\": \"item\", \"amount\": \"0.00\", \"quantity\": \"0.00\", \"description\": \"Instalación de cable UTP categoría 6 para redes de datos incluye conectores y canalización.\", \"iva_percentage\": \"5.00\"}, {\"id\": \"2\", \"name\": \"Fontanería\", \"level\": \"chapter\", \"amount\": \"0.00\"}, {\"id\": \"2.1\", \"name\": \"Tuberías de Agua\", \"level\": \"subchapter\", \"amount\": \"0.00\"}, {\"id\": \"2.1.1\", \"pvp\": \"10.00\", \"name\": \"Instalación de Tubería PEX\", \"unit\": \"m\", \"level\": \"item\", \"amount\": \"0.00\", \"quantity\": \"0.00\", \"description\": \"Instalación de tuberías PEX para suministro de agua potable incluye accesorios y mano de obra.\", \"iva_percentage\": \"10.00\"}, {\"id\": \"3\", \"name\": \"Pintura\", \"level\": \"chapter\", \"amount\": \"0.00\"}, {\"id\": \"3.1\", \"pvp\": \"6.00\", \"name\": \"Pintura de Paredes Interiores\", \"unit\": \"m²\", \"level\": \"item\", \"amount\": \"0.00\", \"quantity\": \"0.00\", \"description\": \"Aplicación de pintura plástica en paredes interiores incluye preparación de superficie.\", \"iva_percentage\": \"21.00\"}]', 1, '2025-06-30 21:10:45'),
(2, 'f7355bfa-d3fd-4c3d-8287-d2a74d35dd4d', 'Prueba', 'Prueba de descripción', '/assets/uploads/logos/1751352654_6863854e62e75.svg', 'Empresa Ejemplo', 'B12345678', 'Calle Ejemplo, 123 - 12345 Ciudad (Provincia)', '900 123 456 - info@empresa.com - www.empresa.com', '41200-00001', '#e8951c', '#109c61', 'Una vez recibida la confirmación del presupuesto procederemos a la facturación del 50% como anticipo y el 50% restante a la finalización de los trabajos.', 'Este presupuesto incluye materiales y mano de obra. No incluye tasas ni licencias municipales. Validez del presupuesto: 30 días.', 'private', 'active', 'Al marcar esta casilla acepta nuestra política de privacidad y el tratamiento de sus datos personales conforme al RGPD.', '[{\"id\": \"1\", \"name\": \"Instalaciones Eléctricas\", \"level\": \"chapter\", \"amount\": \"0.00\"}, {\"id\": \"1.1\", \"name\": \"Cableado Estructurado\", \"level\": \"subchapter\", \"amount\": \"0.00\"}, {\"id\": \"1.1.1\", \"name\": \"Cableado de Baja Tensión\", \"level\": \"section\", \"amount\": \"0.00\"}, {\"id\": \"1.1.1.1\", \"pvp\": \"15.00\", \"name\": \"Instalación de Cable UTP Cat6\", \"unit\": \"m\", \"level\": \"item\", \"amount\": \"0.00\", \"quantity\": \"0.00\", \"description\": \"Instalación de cable UTP categoría 6 para redes de datos incluye conectores y canalización.\", \"iva_percentage\": \"5.00\"}, {\"id\": \"2\", \"name\": \"Fontanería\", \"level\": \"chapter\", \"amount\": \"0.00\"}, {\"id\": \"2.1\", \"name\": \"Tuberías de Agua\", \"level\": \"subchapter\", \"amount\": \"0.00\"}, {\"id\": \"2.1.1\", \"pvp\": \"10.00\", \"name\": \"Instalación de Tubería PEX\", \"unit\": \"m\", \"level\": \"item\", \"amount\": \"0.00\", \"quantity\": \"0.00\", \"description\": \"Instalación de tuberías PEX para suministro de agua potable incluye accesorios y mano de obra.\", \"iva_percentage\": \"10.00\"}, {\"id\": \"3\", \"name\": \"Pintura\", \"level\": \"chapter\", \"amount\": \"0.00\"}, {\"id\": \"3.1\", \"pvp\": \"6.00\", \"name\": \"Pintura de Paredes Interiores\", \"unit\": \"m²\", \"level\": \"item\", \"amount\": \"0.00\", \"quantity\": \"0.00\", \"description\": \"Aplicación de pintura plástica en paredes interiores incluye preparación de superficie.\", \"iva_percentage\": \"21.00\"}]', 1, '2025-07-01 06:51:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `templates`
--

CREATE TABLE `templates` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_system` tinyint(1) DEFAULT '0',
  `template_data` json NOT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `templates`
--

INSERT INTO `templates` (`id`, `name`, `description`, `is_system`, `template_data`, `created_by`, `created_at`) VALUES
(1, 'Estándar del Sistema', 'Plantilla base predefinida no editable', 1, '{\"nif\": \"B12345678\", \"name\": \"Mi Empresa S.L.\", \"title\": \"Nueva Tarifa\", \"access\": \"private\", \"address\": \"Calle Principal, 123 - 12345, Ciudad (Provincia)\", \"contact\": \"123 456 789 - info@miempresa.com - www.miempresa.com\", \"logo_url\": \"\", \"template\": \"41200-00001\", \"legal_note\": \"Al marcar esta casilla acepta nuestra política de privacidad y el tratamiento de sus datos personales.\", \"description\": \"\", \"summary_note\": \"Presupuesto válido por 30 días. Formas de pago: transferencia bancaria o efectivo.\", \"primary_color\": \"#e8951c\", \"conditions_note\": \"Presupuesto sujeto a disponibilidad de material. IVA incluido según normativa vigente.\", \"secondary_color\": \"#109c61\", \"json_tariff_data\": [{\"id\": \"1\", \"name\": \"Capítulo 1\", \"level\": \"chapter\", \"amount\": \"0.00\"}, {\"id\": \"1.1\", \"pvp\": \"10.00\", \"name\": \"Partida ejemplo\", \"unit\": \"ud\", \"level\": \"item\", \"amount\": \"0.00\", \"quantity\": \"0.00\", \"description\": \"Descripción ejemplo\", \"iva_percentage\": \"21.00\"}]}', 1, '2025-06-30 21:10:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('admin','user') COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `last_access` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `rol`, `status`, `last_access`, `created_at`) VALUES
(1, 'Administrador', 'josivela@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', '2025-07-01 13:32:54', '2025-06-30 21:10:45');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD KEY `idx_uuid` (`uuid`),
  ADD KEY `idx_tariff_id` (`tariff_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_client_email` (`client_email`),
  ADD KEY `idx_dates` (`start_date`,`end_date`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indices de la tabla `tariffs`
--
ALTER TABLE `tariffs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD KEY `idx_uuid` (`uuid`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_access` (`access`);

--
-- Indices de la tabla `templates`
--
ALTER TABLE `templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_system` (`is_system`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tariffs`
--
ALTER TABLE `tariffs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `templates`
--
ALTER TABLE `templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `budgets_ibfk_2` FOREIGN KEY (`tariff_id`) REFERENCES `tariffs` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tariffs`
--
ALTER TABLE `tariffs`
  ADD CONSTRAINT `tariffs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `templates`
--
ALTER TABLE `templates`
  ADD CONSTRAINT `templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

DELIMITER $$
--
-- Eventos
--
CREATE DEFINER=`root`@`localhost` EVENT `budget_expiration_check` ON SCHEDULE EVERY 1 DAY STARTS '2025-06-30 23:10:45' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE budgets 
SET status = 'expired' 
WHERE end_date < CURDATE() 
AND status NOT IN ('approved', 'rejected', 'expired')$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
