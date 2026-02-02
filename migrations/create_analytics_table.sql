-- Tabela para Sistema de Analytics - Rastreamento de Visitantes
-- Criada em: 2026-02-02

CREATE TABLE IF NOT EXISTS site_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    country VARCHAR(100) NULL,
    country_code VARCHAR(10) NULL,
    region VARCHAR(100) NULL,
    city VARCHAR(100) NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    isp VARCHAR(200) NULL,
    page_url VARCHAR(500) NOT NULL,
    page_title VARCHAR(200) NULL,
    referrer VARCHAR(500) NULL,
    user_agent TEXT NULL,
    device_type VARCHAR(50) NULL,
    browser VARCHAR(100) NULL,
    os VARCHAR(100) NULL,
    session_id VARCHAR(100) NULL,
    visited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (ip_address),
    INDEX idx_visited (visited_at),
    INDEX idx_page (page_url(100)),
    INDEX idx_country (country_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
