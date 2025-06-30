-- Base de datos completa para Budget Form Service - Actualizada
-- {"_META_file_path_": "database/budget_form_service_updated.sql"}

DROP DATABASE IF EXISTS budget_form_service;
CREATE DATABASE budget_form_service CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE budget_form_service;

-- Tabla usuarios
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_access TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla plantillas
CREATE TABLE templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    is_system BOOLEAN DEFAULT FALSE,
    template_data JSON NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_system (is_system),
    INDEX idx_created_by (created_by)
);

-- Tabla tarifas (con campos agregados para iconos y botones)
CREATE TABLE tariffs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    logo_url VARCHAR(500),
    name VARCHAR(255) NOT NULL,
    nif VARCHAR(50),
    address TEXT,
    contact VARCHAR(255),
    template VARCHAR(100) DEFAULT '41200-00001',
    primary_color VARCHAR(7) DEFAULT '#e8951c',
    secondary_color VARCHAR(7) DEFAULT '#109c61',
    summary_note TEXT,
    conditions_note TEXT,
    access ENUM('public', 'private') DEFAULT 'private',
    status ENUM('active', 'inactive') DEFAULT 'active',
    legal_note TEXT,
    json_tariff_data JSON NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_uuid (uuid),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_access (access)
);

-- Tabla presupuestos (con tariff_id corregido)
CREATE TABLE budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    tariff_id INT NOT NULL,
    json_observations JSON,
    json_tariff_data JSON NOT NULL,
    client_type ENUM('particular', 'autonomo', 'empresa') NOT NULL,
    client_name VARCHAR(255) NOT NULL,
    client_nif_nie VARCHAR(50),
    client_phone VARCHAR(50),
    client_email VARCHAR(255),
    client_web VARCHAR(255),
    client_address TEXT,
    client_postal_code VARCHAR(10),
    client_locality VARCHAR(100),
    client_province VARCHAR(100),
    client_acceptance BOOLEAN DEFAULT FALSE,
    json_budget_data JSON NOT NULL,
    status ENUM('draft', 'pending', 'sent', 'approved', 'rejected', 'expired') DEFAULT 'draft',
    total DECIMAL(10,2) DEFAULT 0.00,
    iva DECIMAL(10,2) DEFAULT 0.00,
    base DECIMAL(10,2) DEFAULT 0.00,
    pdf_url VARCHAR(500),
    start_date DATE,
    end_date DATE,
    validity_days INT DEFAULT 30,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tariff_id) REFERENCES tariffs(id) ON DELETE CASCADE,
    INDEX idx_uuid (uuid),
    INDEX idx_tariff_id (tariff_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_client_email (client_email),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_created_at (created_at)
);

-- Datos iniciales
INSERT INTO users (name, email, password_hash, rol) VALUES 
('Administrador', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Plantilla estándar del sistema
INSERT INTO templates (name, description, is_system, template_data, created_by) VALUES 
('Estándar del Sistema', 'Plantilla base predefinida no editable', TRUE, JSON_OBJECT(
    'title', 'Nueva Tarifa',
    'description', '',
    'logo_url', '',
    'name', 'Mi Empresa S.L.',
    'nif', 'B12345678',
    'address', 'Calle Principal, 123 - 12345, Ciudad (Provincia)',
    'contact', '123 456 789 - info@miempresa.com - www.miempresa.com',
    'template', '41200-00001',
    'primary_color', '#e8951c',
    'secondary_color', '#109c61',
    'summary_note', 'Presupuesto válido por 30 días. Formas de pago: transferencia bancaria o efectivo.',
    'conditions_note', 'Presupuesto sujeto a disponibilidad de material. IVA incluido según normativa vigente.',
    'access', 'private',
    'legal_note', 'Al marcar esta casilla acepta nuestra política de privacidad y el tratamiento de sus datos personales.',
    'json_tariff_data', JSON_ARRAY(
        JSON_OBJECT('level', 'chapter', 'id', '1', 'name', 'Capítulo 1', 'amount', '0.00'),
        JSON_OBJECT('level', 'item', 'id', '1.1', 'name', 'Partida ejemplo', 'amount', '0.00', 'description', 'Descripción ejemplo', 'unit', 'ud', 'quantity', '0.00', 'iva_percentage', '21.00', 'pvp', '10.00')
    )
), 1);

-- Tarifa de ejemplo
INSERT INTO tariffs (
    uuid, title, description, name, nif, address, contact, 
    legal_note, json_tariff_data, user_id
) VALUES (
    UUID(),
    'Tarifa Ejemplo',
    'Tarifa de ejemplo para pruebas',
    'Jeyca Tecnología y Medio Ambiente, S.L.',
    'B91707703',
    'C/ Pimienta, 6 - 41200, Alcalá del Río (Sevilla)',
    '955 650 626 - soporte@jeyca.net',
    'Al marcar esta casilla acepta nuestra política de privacidad y el tratamiento de sus datos personales.',
    JSON_ARRAY(
        JSON_OBJECT('level', 'chapter', 'id', '1', 'name', 'Instalaciones Eléctricas', 'amount', '0.00'),
        JSON_OBJECT('level', 'subchapter', 'id', '1.1', 'name', 'Cableado Estructurado', 'amount', '0.00'),
        JSON_OBJECT('level', 'section', 'id', '1.1.1', 'name', 'Cableado de Baja Tensión', 'amount', '0.00'),
        JSON_OBJECT('level', 'item', 'id', '1.1.1.1', 'name', 'Instalación de Cable UTP Cat6', 'amount', '0.00', 'description', 'Instalación de cable UTP categoría 6 para redes de datos incluye conectores y canalización.', 'unit', 'm', 'quantity', '0.00', 'iva_percentage', '5.00', 'pvp', '15.00'),
        JSON_OBJECT('level', 'chapter', 'id', '2', 'name', 'Fontanería', 'amount', '0.00'),
        JSON_OBJECT('level', 'subchapter', 'id', '2.1', 'name', 'Tuberías de Agua', 'amount', '0.00'),
        JSON_OBJECT('level', 'item', 'id', '2.1.1', 'name', 'Instalación de Tubería PEX', 'amount', '0.00', 'description', 'Instalación de tuberías PEX para suministro de agua potable incluye accesorios y mano de obra.', 'unit', 'm', 'quantity', '0.00', 'iva_percentage', '10.00', 'pvp', '10.00'),
        JSON_OBJECT('level', 'chapter', 'id', '3', 'name', 'Pintura', 'amount', '0.00'),
        JSON_OBJECT('level', 'item', 'id', '3.1', 'name', 'Pintura de Paredes Interiores', 'amount', '0.00', 'description', 'Aplicación de pintura plástica en paredes interiores incluye preparación de superficie.', 'unit', 'm²', 'quantity', '0.00', 'iva_percentage', '21.00', 'pvp', '6.00')
    ),
    1
);

-- Presupuesto de ejemplo para pruebas del contador
INSERT INTO budgets (
    uuid, tariff_id, json_tariff_data, client_type, client_name, 
    client_nif_nie, client_email, client_phone, client_address,
    json_budget_data, status, total, iva, base, user_id
) VALUES (
    UUID(), 
    1, 
    JSON_OBJECT('name', 'Jeyca Tecnología y Medio Ambiente, S.L.'),
    'empresa', 
    'Cliente Ejemplo S.L.', 
    'B12345678', 
    'cliente@ejemplo.com', 
    '123456789', 
    'Calle Ejemplo, 123',
    JSON_OBJECT('items', JSON_ARRAY(), 'totals', JSON_OBJECT('base', 100.00, 'iva', 21.00, 'final', 121.00)),
    'pending',
    121.00,
    21.00,
    100.00,
    1
);

-- Triggers para fechas automáticas
DELIMITER $$

CREATE TRIGGER budget_dates_trigger
BEFORE INSERT ON budgets
FOR EACH ROW
BEGIN
    IF NEW.start_date IS NULL THEN
        SET NEW.start_date = CURDATE();
    END IF;
    
    IF NEW.end_date IS NULL AND NEW.validity_days IS NOT NULL THEN
        SET NEW.end_date = DATE_ADD(NEW.start_date, INTERVAL NEW.validity_days DAY);
    END IF;
END$$

-- Trigger para expiración automática
CREATE EVENT budget_expiration_check
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
UPDATE budgets 
SET status = 'expired' 
WHERE end_date < CURDATE() 
AND status NOT IN ('approved', 'rejected', 'expired')$$

DELIMITER ;

-- Habilitar eventos (ejecutar por separado si es necesario)
-- SET GLOBAL event_scheduler = ON;