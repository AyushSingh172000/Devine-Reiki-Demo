-- =======================================================
-- Migration Script: Add pending_form Column to chat_sessions
-- Database: reiki_website
-- =======================================================

ALTER TABLE `chat_sessions` 
ADD COLUMN `pending_form` TEXT DEFAULT NULL AFTER `ip_address`;
