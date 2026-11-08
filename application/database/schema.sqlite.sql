-- Database schema of StuckMVC (SQLite, used for tests and local development).

CREATE TABLE IF NOT EXISTS "user" (
  "id"            INTEGER PRIMARY KEY AUTOINCREMENT,
  "username"      VARCHAR(32)  NOT NULL UNIQUE COLLATE NOCASE,
  "email"         VARCHAR(255) NULL DEFAULT NULL UNIQUE COLLATE NOCASE,
  "passhash"      VARCHAR(255) NOT NULL,
  "role"          VARCHAR(16)  NOT NULL DEFAULT 'user',
  "created_at"    DATETIME     NOT NULL,
  "last_login_at" DATETIME     NULL DEFAULT NULL
);
