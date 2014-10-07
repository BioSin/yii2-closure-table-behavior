DROP TABLE IF EXISTS `category_tree`;
DROP TABLE IF EXISTS `category`;

CREATE TABLE IF NOT EXISTS `category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `category_tree` (
  `parent` int(10) unsigned NOT NULL,
  `child` int(10) unsigned NOT NULL,
  `depth` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`parent`,`child`),
  KEY `fk_category_tree_child_category` (`child`),
  CONSTRAINT `fk_category_tree_child_category` FOREIGN KEY (`child`) REFERENCES `category` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_category_tree_parent_category` FOREIGN KEY (`parent`) REFERENCES `category` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*------------ For tests only ---------------------*/

DROP TABLE IF EXISTS `related`;

CREATE TABLE IF NOT EXISTS `category_related` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_category_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_related_parent_category` FOREIGN KEY (`parent_category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;