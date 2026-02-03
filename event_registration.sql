-- Event Configuration Table
CREATE TABLE `event_registration_event_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_name` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `event_date` date NOT NULL,
  `registration_start` datetime NOT NULL,
  `registration_end` datetime NOT NULL,
  `created` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `category_date` (`category`,`event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event Registrations Table
CREATE TABLE `event_registration_registrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_config_id` int NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `college_name` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `created` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `email_event` (`email`,`event_config_id`),
  KEY `event_date` (`event_config_id`),
  CONSTRAINT `event_registration_registrations_ibfk_1` FOREIGN KEY (`event_config_id`) REFERENCES `event_registration_event_config` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;