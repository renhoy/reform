-- Base de datos para Servicio Form
-- {"_META_file_path_": "database.sql"}

DROP DATABASE IF EXISTS budget_form_service;
CREATE DATABASE budget_form_service;
USE budget_form_service;

-- Tabla usuarios
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Configuración por defecto
CREATE TABLE default_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    logo_url VARCHAR(500) DEFAULT 'https://jeyca.net/wp-content/uploads/2025/04/logo-tpvguay.svg',
    name VARCHAR(255) DEFAULT 'Jeyca Tecnología y Medio Ambiente, S.L.',
    nif VARCHAR(50) DEFAULT 'B91707703',
    address VARCHAR(500) DEFAULT 'C/ Pimienta, 6 - 41200, Alcalá del Río (Sevilla)',
    contact VARCHAR(255) DEFAULT '955 650 626 - soporte@jeyca.net',
    template VARCHAR(100) DEFAULT '41200-00001',
    primary_color VARCHAR(7) DEFAULT '#e8951c',
    secondary_color VARCHAR(7) DEFAULT '#109c61',
    summary_note TEXT,
    conditions_note TEXT,
    legal_note TEXT
);

-- Tabla tarifas
CREATE TABLE tariffs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255),
    json_data TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Configuración de empresa por tarifa
CREATE TABLE company_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tariff_id INT NOT NULL,
    logo_url VARCHAR(500),
    name VARCHAR(255) NOT NULL,
    nif VARCHAR(50) NOT NULL,
    address TEXT NOT NULL,
    contact VARCHAR(255) NOT NULL,
    template VARCHAR(100),
    primary_color VARCHAR(7) DEFAULT '#e8951c',
    secondary_color VARCHAR(7) DEFAULT '#109c61',
    summary_note TEXT,
    conditions_note TEXT,
    legal_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tariff_id) REFERENCES tariffs(id) ON DELETE CASCADE
);

-- Presupuestos generados
CREATE TABLE budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    tariff_id INT NOT NULL,
    client_data JSON,
    budget_data JSON,
    full_payload JSON,
    status ENUM('draft', 'sent', 'pending', 'completed', 'error') DEFAULT 'draft',
    pdf_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tariff_id) REFERENCES tariffs(id)
);

-- Datos por defecto
INSERT INTO default_config (summary_note, conditions_note, legal_note) VALUES (
    'ACEPTACIÓN Y FORMAS DE PAGO...',
    'CONDICIONES GENERALES DEL PRESUPUESTO...',
    'Al marcar esta casilla acepta nuestra política de privacidad y el tratamiento de sus datos personales.'
);

-- Usuario demo
INSERT INTO users (username, password_hash) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password