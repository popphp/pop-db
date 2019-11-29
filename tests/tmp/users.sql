
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
