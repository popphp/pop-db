
-- There is a comment here

DROP TABLE IF EXISTS "[{prefix}]users";

-- Let's create a table

CREATE TABLE IF NOT EXISTS "[{prefix}]users" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "username" varchar,
  "password" varchar,
  "email" varchar,
  "active" integer,
  "verified" integer,
  UNIQUE ("id")
) ;

-- End of SQL
