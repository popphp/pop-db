/*
 * Comment block
 */

-- There is a comment here

DROP TABLE IF EXISTS `[{prefix}]users`;

-- Let's create a table
CREATE TABLE IF NOT EXISTS `[{prefix}]users` (
  `id` int(16) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, -- some comment
  `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, /* another comment */
  `title` varchar(255),
  `email` varchar(255),
  `active` int(1),
  `verified` int(1),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- End of SQL
