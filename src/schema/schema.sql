DROP TABLE IF EXISTS `category_tree`;
DROP TABLE IF EXISTS `category`;
CREATE TABLE IF NOT EXISTS `category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `category_tree` (
  `parent` int(11) unsigned NOT NULL,
  `child` int(11) unsigned NOT NULL,
  `depth` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`parent`,`child`),
  KEY `fk_category_tree_child_category` (`child`),
  CONSTRAINT `fk_category_tree_child_category` FOREIGN KEY (`child`) REFERENCES `category` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_category_tree_parent_category` FOREIGN KEY (`parent`) REFERENCES `category` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
