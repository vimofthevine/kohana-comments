CREATE TABLE IF NOT EXISTS `comments` (
	`id` int(11) NOT NULL auto_increment,
	`parent_id` int(11) NOT NULL,
	`state` varchar(8) NOT NULL,
	`date` int(10) NOT NULL,
	`name` varchar(64) NOT NULL,
	`email` varchar(128) NOT NULL,
	`url` varchar(128) NOT NULL,
	`text` text NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

