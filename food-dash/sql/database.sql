-- Food Dash : Module 1 - User Management
-- WARNING: this drops an existing food_dash database and its data, then recreates it.
DROP DATABASE IF EXISTS food_dash;
CREATE DATABASE food_dash CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE food_dash;

CREATE TABLE users (
  user_id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name                 VARCHAR(50)  NOT NULL,
  email                VARCHAR(100) NOT NULL UNIQUE,
  phone                CHAR(10)     NOT NULL UNIQUE,
  password_hash        VARCHAR(255) NOT NULL,
  password_changed_at  DATETIME     NOT NULL,
  notify_order_email   TINYINT(1)   NOT NULL DEFAULT 1,
  notify_order_sms     TINYINT(1)   NOT NULL DEFAULT 1,
  notify_account_email TINYINT(1)   NOT NULL DEFAULT 1,
  notify_promo_email   TINYINT(1)   NOT NULL DEFAULT 0,
  created_at           DATETIME     NOT NULL,
  updated_at           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE addresses (
  address_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  label      VARCHAR(30)  NOT NULL DEFAULT 'Home',
  house      VARCHAR(100) NOT NULL,
  locality   VARCHAR(100) NOT NULL,
  city       VARCHAR(50)  NOT NULL,
  state      VARCHAR(50)  NOT NULL,
  pincode    CHAR(6)      NOT NULL,
  is_default TINYINT(1)   NOT NULL DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE password_resets (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  token_hash CHAR(64)     NOT NULL,
  expires_at DATETIME     NOT NULL,
  used       TINYINT(1)   NOT NULL DEFAULT 0,
  INDEX (token_hash),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE remember_tokens (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        INT UNSIGNED NOT NULL,
  selector       CHAR(18)     NOT NULL UNIQUE,
  validator_hash CHAR(64)     NOT NULL,
  expires_at     DATETIME     NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE user_sessions (
  session_id VARCHAR(128) PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  device     VARCHAR(120) NOT NULL,
  ip         VARCHAR(45)  NOT NULL DEFAULT '',
  created_at DATETIME     NOT NULL,
  last_seen  DATETIME     NOT NULL,
  INDEX (user_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;
