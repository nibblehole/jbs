

ALTER TABLE `EdesksMessages` ADD `IP` CHAR(40) NOT NULL DEFAULT '127.0.0.127';

-- SEPARATOR

ALTER TABLE `EdesksMessages` ADD `UA` TEXT NOT NULL;

