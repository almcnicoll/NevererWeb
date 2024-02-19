CREATE TABLE model (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `authmethods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `methodName` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `handler` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `methodName` (`methodName`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE errors (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL,
  `number` mediumint DEFAULT NULL,
  `message` VARCHAR(4000) DEFAULT NULL,
  `file` varchar(400) DEFAULT NULL,
  `line` smallint UNSIGNED DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE faqs (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `question` varchar(200)  COLLATE utf8mb4_unicode_ci NOT NULL,
  `answer`   varchar(4000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rank` int(11) NOT NULL DEFAULT 0,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ListIndex` (`rank`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `authmethod_id` int(10) unsigned NOT NULL,
  `identifier` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_name` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_url` VARCHAR(500) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `LoginLookup` (`authmethod_id`,`identifier`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;