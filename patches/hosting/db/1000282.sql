
--
-- Table structure for table `HostingServersGroups`
--

DROP TABLE IF EXISTS `ServersGroups`;
CREATE TABLE `ServersGroups` (
	`ID` int(11) NOT NULL AUTO_INCREMENT,	-- идентификатор группы
	`Name` char(30) NOT NULL,		-- имя группы
	`FunctionID` char(30) default '',	-- принцип определения того кто IsDefault
	`Comment` char(255) default '',		-- комментарий к группе
	`SortID` int(11) default '10',		-- поле для сортировки
	PRIMARY KEY(`ID`)			-- первичный ключ
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- SEPARATOR

--
-- Table structure for table `HostingServers`
--

DROP TABLE IF EXISTS `Servers`;
CREATE TABLE `Servers` (
	`ID` int(11) NOT NULL AUTO_INCREMENT,		-- идентификатор сервера
	`ServiceID` int(11) NULL,			-- ссылка на сервис (или NULL, если сервер не относится к сервису)
	`ServersGroupID` int(11) NOT NULL,		-- группа серверов
	`IsActive` enum('no','yes') default 'yes',	-- активен ли сервер
	`IsDefault` enum('no','yes') default 'no',	-- этот сервер используется "по-умолчанию"
	`Protocol` enum('tcp','ssl') default 'tcp',	-- протокол для связи с сервером
	`Address` char(30) default '',			-- адрес сервера
	`Port` int(5) default '80',			-- порт сервера
	`PrefixAPI` char(127) default '',		-- преффикс используемого API
	`Login` char(60) default '',			-- логин для входа на сервер
	`Password` char(255) default '',		-- пароль для входа на сервер
	`Params` LONGTEXT,				-- набор переменных необходимых для взаимодействия с сервером
	`SortID` int(11) default '10',			-- поле для сортировки
	PRIMARY KEY(`ID`),
	/* внешний ключ на таблицу групп серверов */
	KEY `ServersServersGroupID` (`ServersGroupID`),
	CONSTRAINT `ServersServersGroupID` FOREIGN KEY (`ServersGroupID`) REFERENCES `ServersGroups` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
	/* внешний ключ на таблицу сервисов */
	KEY `ServersServiceID` (`ServiceID`),
	CONSTRAINT `ServersServiceID` FOREIGN KEY (`ServiceID`) REFERENCES `Services` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

