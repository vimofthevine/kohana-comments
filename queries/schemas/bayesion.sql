CREATE TABLE IF NOT EXISTS `b8_words` (
	`id` bigint unsigned NOT NULL auto_increment,
	`word` varchar(30) NOT NULL,
	`ham` bigint unsigned NULL,
	`spam` bigint unsigned NULL,
	PRIMARY KEY (`id`),
	INDEX (`word`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `b8_categories` (
	`id` bigint unsigned NOT NULL auto_increment,
	`category` varchar(4) NULL,
	`total` bigint unsigned NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX (`category`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO b8_categories (id, category, total) VALUES (1, 'ham', 0);
INSERT INTO b8_categories (id, category, total) VALUES (2, 'spam', 0);

