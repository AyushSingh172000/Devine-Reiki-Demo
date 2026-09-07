-- =======================================================
-- Migration Script: Add was_blocked Column to chat_messages
-- Database: reiki_website
-- =======================================================

ALTER TABLE `chat_messages` 
ADD COLUMN `was_blocked` TINYINT(1) NOT NULL DEFAULT 0 AFTER `content`,
ADD INDEX `idx_was_blocked` (`was_blocked`);
