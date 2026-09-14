CREATE TABLE `ticket_besitzer` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `kaeufer_id` bigint unsigned NOT NULL,
  `person_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_ticket_besitzer_kaeufer_id` (`kaeufer_id`),
  KEY `fk_ticket_besitzer_person_id` (`person_id`),
  CONSTRAINT `fk_ticket_besitzer_kaeufer_id` FOREIGN KEY (`kaeufer_id`) REFERENCES `kaeufer` (`id`),
  CONSTRAINT `fk_ticket_besitzer_person_id` FOREIGN KEY (`person_id`) REFERENCES `person` (`id`),
  CONSTRAINT `ticket_besitzer_ibfk_1` FOREIGN KEY (`kaeufer_id`) REFERENCES `kaeufer` (`id`),
  CONSTRAINT `ticket_besitzer_ibfk_2` FOREIGN KEY (`person_id`) REFERENCES `person` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3;

CREATE TABLE `person` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vorname` varchar(100) NOT NULL,
  `nachname` varchar(100) NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `age` int DEFAULT NULL,
  `school` varchar(100) DEFAULT NULL,
  `send_TicketMail` tinyint(1) DEFAULT '0',
  `dateSendTicketMail` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb3;

CREATE TABLE `kaeufer` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `person_id` bigint unsigned NOT NULL,
  `created` timestamp NOT NULL,
  `submited` timestamp NOT NULL,
  `charges` decimal(10,2) DEFAULT NULL,
  `summe` decimal(10,2) DEFAULT NULL,
  `paid_charges` decimal(10,2) DEFAULT '0.00',
  `open_charges` decimal(10,2) GENERATED ALWAYS AS ((`charges` - `paid_charges`)) STORED,
  `tickets` int DEFAULT '0',
  `send_confMail` tinyint(1) DEFAULT '0',
  `dateSendConfMail` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `person_id` (`person_id`),
  CONSTRAINT `kaeufer_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `person` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;