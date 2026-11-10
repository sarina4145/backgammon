CREATE DATABASE IF NOT EXISTS backgammon
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE backgammon;

CREATE TABLE IF NOT EXISTS games (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_code CHAR(5) NOT NULL UNIQUE,
    host_token CHAR(64) NOT NULL,
    guest_token CHAR(64) NULL,
    state_json LONGTEXT NOT NULL,
    status ENUM('waiting', 'playing', 'finished') NOT NULL DEFAULT 'waiting',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status_updated (status, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
