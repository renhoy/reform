-- Base de datos actualizada para Servicio Form
-- {"_META_file_path_": "src/config/database.sql"}

DROP DATABASE IF EXISTS budget_form_service;
CREATE DATABASE budget_form_service;
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

-- Tabla tarifas
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
    legal_note TEXT,
    json_tariff_data TEXT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_uuid (uuid),
    INDEX idx_user_id (user_id)
);

-- Tabla presupuestos
CREATE TABLE budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    json_observations TEXT,
    json_tariff_data TEXT NOT NULL,
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
    json_budget_data TEXT NOT NULL,
    status ENUM('draft', 'sent', 'pending', 'approved', 'rejected', 'completed') DEFAULT 'draft',
    total DECIMAL(10,2) DEFAULT 0.00,
    iva DECIMAL(10,2) DEFAULT 0.00,
    base DECIMAL(10,2) DEFAULT 0.00,
    pdf_url VARCHAR(500),
    start_date DATE,
    end_date DATE,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_uuid (uuid),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_client_email (client_email)
);

-- Datos por defecto
INSERT INTO users (name, email, password_hash, rol) VALUES 
('Administrador', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

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
    '[{"level":"chapter","id":"1","name":"Capítulo 1","amount":"0.00"},{"level":"item","id":"1.1","name":"Partida ejemplo","amount":"0.00","description":"Descripción ejemplo","unit":"ud","quantity":"0.00","iva_percentage":"21.00","pvp":"10.00"}]',
    1
);