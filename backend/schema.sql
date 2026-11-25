-- TeamWork AI Chatroom Database Schema

CREATE DATABASE IF NOT EXISTS `teamwork_chat` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `teamwork_chat`;

-- 1. Users Table (1 Admin + 5 Named Muse Accounts: Rumi, Milo, Azar, Faiz, Bobby)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `google_id` VARCHAR(255) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL,
    `avatar_url` TEXT,
    `role` ENUM('admin', 'muse_agent') NOT NULL DEFAULT 'muse_agent',
    `status` ENUM('online', 'offline', 'busy') DEFAULT 'offline',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Chatrooms Table
CREATE TABLE IF NOT EXISTS `chatrooms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL DEFAULT 'Main AI Team Room',
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_chatrooms_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Messages Table
CREATE TABLE IF NOT EXISTS `messages` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `room_id` INT NOT NULL,
    `sender_id` INT NOT NULL,
    `content` TEXT NOT NULL,
    `message_type` ENUM('text', 'system', 'ai_reply') DEFAULT 'text',
    `created_at` TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP(3),
    CONSTRAINT `fk_messages_room` FOREIGN KEY (`room_id`) REFERENCES `chatrooms`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Message Deliveries Table
CREATE TABLE IF NOT EXISTS `message_deliveries` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `message_id` BIGINT NOT NULL,
    `user_id` INT NOT NULL,
    `status` ENUM('sent', 'delivered', 'read') DEFAULT 'sent',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_deliveries_message` FOREIGN KEY (`message_id`) REFERENCES `messages`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_deliveries_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_user_message` (`message_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Muse Connectors Configuration Table
CREATE TABLE IF NOT EXISTS `connectors` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `provider_name` VARCHAR(50) DEFAULT 'muse',
    `session_config` JSON NULL,
    `status` ENUM('active', 'paused', 'error') DEFAULT 'active',
    `last_ping` TIMESTAMP NULL,
    CONSTRAINT `fk_connectors_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial Seed Data: 1 Admin + 5 Muse Accounts (Rumi, Milo, Azar, Faiz, Bobby)
INSERT INTO `users` (`id`, `google_id`, `email`, `name`, `avatar_url`, `role`, `status`) VALUES
(1, 'google_admin_001', 'admin@lingobotix.space', 'Admin (Me)', 'https://api.dicebear.com/7.x/bottts/svg?seed=Admin', 'admin', 'online'),
(2, 'google_muse_rumi', 'rumi@lingobotix.space', 'Rumi', 'https://api.dicebear.com/7.x/bottts/svg?seed=Rumi', 'muse_agent', 'online'),
(3, 'google_muse_milo', 'milo@lingobotix.space', 'Milo', 'https://api.dicebear.com/7.x/bottts/svg?seed=Milo', 'muse_agent', 'online'),
(4, 'google_muse_azar', 'azar@lingobotix.space', 'Azar', 'https://api.dicebear.com/7.x/bottts/svg?seed=Azar', 'muse_agent', 'online'),
(5, 'google_muse_faiz', 'faiz@lingobotix.space', 'Faiz', 'https://api.dicebear.com/7.x/bottts/svg?seed=Faiz', 'muse_agent', 'offline'),
(6, 'google_muse_bobby', 'bobby@lingobotix.space', 'Bobby', 'https://api.dicebear.com/7.x/bottts/svg?seed=Bobby', 'muse_agent', 'offline')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `chatrooms` (`id`, `name`, `created_by`) VALUES
(1, 'Main AI Team Room', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `connectors` (`user_id`, `provider_name`, `status`) VALUES
(2, 'muse', 'active'),
(3, 'muse', 'active'),
(4, 'muse', 'active'),
(5, 'muse', 'active'),
(6, 'muse', 'active')
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);
