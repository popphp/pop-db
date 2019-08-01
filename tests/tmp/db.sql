PRAGMA encoding = "UTF-8";
PRAGMA foreign_keys = ON;

DROP TABLE IF EXISTS "[{prefix}]users";
CREATE TABLE IF NOT EXISTS "[{prefix}]users" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "username" varchar,
  "password" varchar,
  "email" varchar,
  "active" integer,
  "verified" integer,
  UNIQUE ("id")
) ;

INSERT INTO "sqlite_sequence" ("name", "seq") VALUES ('[{prefix}]users', 1000);
CREATE INDEX "username" ON "[{prefix}]users" ("username");
CREATE INDEX "user_email" ON "[{prefix}]users" ("email");

DROP TABLE IF EXISTS "[{prefix}]encoded_users";
CREATE TABLE IF NOT EXISTS "[{prefix}]encoded_users" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "username" varchar,
  "password" varchar,
  "info" varchar,
  "metadata" varchar,
  "encoded" varchar,
  "ssn" varchar,
  UNIQUE ("id")
) ;

INSERT INTO "sqlite_sequence" ("name", "seq") VALUES ('[{prefix}]encoded_users', 2000);
