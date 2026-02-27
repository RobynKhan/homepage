-- ─── Pomodoro Timer Database Schema ─────────────────────────────────────────
-- Run this file once to initialize the database.
-- mysql -u root -p < setup.sql

CREATE DATABASE IF NOT EXISTS pomodoro_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE pomodoro_db;

-- ─── Sessions table: records each completed pomodoro or break ─────────────
CREATE TABLE IF NOT EXISTS sessions (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_type  ENUM('POMODORO', 'SHORTBREAK', 'LONGBREAK') NOT NULL,
    duration      SMALLINT UNSIGNED NOT NULL COMMENT 'Duration in seconds',
    completed_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Settings table: persists user timer preferences ─────────────────────
CREATE TABLE IF NOT EXISTS settings (
    id                         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pomodoro_duration          TINYINT UNSIGNED NOT NULL DEFAULT 25  COMMENT 'Minutes',
    short_break_duration       TINYINT UNSIGNED NOT NULL DEFAULT 5   COMMENT 'Minutes',
    long_break_duration        TINYINT UNSIGNED NOT NULL DEFAULT 15  COMMENT 'Minutes',
    pomodoros_until_long_break TINYINT UNSIGNED NOT NULL DEFAULT 4,
    background_theme           VARCHAR(50)      NOT NULL DEFAULT 'default',
    updated_at                 TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Seed default settings row ────────────────────────────────────────────
INSERT INTO settings
    (pomodoro_duration, short_break_duration, long_break_duration, pomodoros_until_long_break, background_theme)
VALUES
    (25, 5, 15, 4, 'default')
ON DUPLICATE KEY UPDATE id = id;
