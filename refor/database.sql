-- Base de datos para Budget Generator
-- {"_META_file_path_": "refor/database.sql"}

DROP DATABASE IF EXISTS budget_generator;
CREATE DATABASE budget_generator CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE budget_generator;

-- Tabla usuarios
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_access TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla tarifas
CREATE TABLE tariffs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    json_data TEXT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);

-- Tabla configuración de empresa
CREATE TABLE company_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tariff_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    nif VARCHAR(50),
    address TEXT,
    contact VARCHAR(255),
    logo_url VARCHAR(500),
    template VARCHAR(100) DEFAULT 'default',
    primary_color VARCHAR(7) DEFAULT '#3b82f6',
    secondary_color VARCHAR(7) DEFAULT '#10b981',
    summary_note TEXT,
    conditions_note TEXT,
    legal_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tariff_id) REFERENCES tariffs(id) ON DELETE CASCADE,
    INDEX idx_tariff_id (tariff_id)
);

-- Tabla presupuestos
CREATE TABLE budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    tariff_id INT NOT NULL,
    client_data TEXT NOT NULL,
    budget_data TEXT NOT NULL,
    status ENUM('draft', 'sent', 'pending', 'approved', 'rejected', 'completed') DEFAULT 'draft',
    total DECIMAL(10,2) DEFAULT 0.00,
    user_id INT NOT NULL,
    pdf_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tariff_id) REFERENCES tariffs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_uuid (uuid),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);

-- Usuario por defecto (admin/admin)
INSERT INTO users (name, email, password_hash, role) VALUES 
('Administrador', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Tarifa de ejemplo
INSERT INTO tariffs (name, description, json_data, user_id) VALUES (
    'Tarifa Ejemplo',
    'Tarifa de prueba para desarrollo',
    '[{"level":"chapter","id":"1","name":"Capítulo 1","amount":"0.00"},{"level":"item","id":"1.1","name":"Partida ejemplo","amount":"0.00","description":"Descripción ejemplo","unit":"ud","quantity":"0.00","iva_percentage":"21.00","pvp":"10.00"}]',
    1
);

INSERT INTO company_config (tariff_id, name, nif, address, contact, legal_note) VALUES (
    1,
    'Empresa Ejemplo S.L.',
    'B12345678',
    'Calle Ejemplo, 123 - 41000 Sevilla',
    '954 123 456 - info@ejemplo.com',
    'Acepto la política de privacidad y el tratamiento de datos personales.'
);