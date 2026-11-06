-- Database schema of StuckMVC (MySQL/MariaDB).
-- Applied automatically by 'php bin/console db:install'.

CREATE TABLE IF NOT EXISTS `user` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(32)  NOT NULL,
  `email`         VARCHAR(255) NULL DEFAULT NULL,
  `passhash`      VARCHAR(255) NOT NULL,
  `role`          VARCHAR(16)  NOT NULL DEFAULT 'user',
  `created_at`    DATETIME     NOT NULL,
  `last_login_at` DATETIME     NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_username` (`username`),
  UNIQUE KEY `uq_user_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
