CREATE TABLE `w_authtokens` (
  `id` int(11) UNSIGNED NOT NULL,
  `selector` varchar(255) NOT NULL,
  `hashedValidator` varchar(255) NOT NULL,
  `userID` int(11) UNSIGNED NOT NULL,
  `expires` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `w_domaindata` (
  `id` int(11) NOT NULL,
  `domainID` int(11) NOT NULL,
  `domainHash` varchar(255) NOT NULL,
  `domainAdmin` int(11) DEFAULT '0',
  `domainMaxRecords` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `w_logins` (
  `id` int(11) NOT NULL,
  `loginUserID` int(11) NOT NULL,
  `loginDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `loginIP` varbinary(16) NOT NULL,
  `loginAgent` text NOT NULL,
  `masterAccount` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `w_logs` (
  `id` int(11) NOT NULL,
  `domainID` int(11) NOT NULL,
  `domainName` varchar(255) NOT NULL,
  `userID` int(11) NOT NULL,
  `userEmail` varchar(255) NOT NULL,
  `action` varchar(20) NOT NULL,
  `record` varchar(255) DEFAULT NULL,
  `masterID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `w_userlevels` (
  `userLevelID` int(11) NOT NULL,
  `userLevelDesc` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `w_users` (
  `userID` int(11) NOT NULL,
  `userEmail` varchar(255) NOT NULL,
  `userPassword` varchar(255) NOT NULL,
  `userName` varchar(255) NOT NULL,
  `userAdminLevel` int(1) NOT NULL DEFAULT '0',
  `userResetToken` varchar(50) DEFAULT NULL,
  `userResetTokenExpires` datetime DEFAULT NULL,
  `userEnabled` int(1) NOT NULL,
  `userMaxDomains` int(11) NOT NULL DEFAULT '0',
  `userMaxAccounts` int(11) NOT NULL DEFAULT '0',
  `userMasterAccount` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `w_authtokens`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `w_domaindata`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `w_logins`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `w_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

ALTER TABLE `w_userlevels`
  ADD PRIMARY KEY (`userLevelID`);

ALTER TABLE `w_users`
  ADD PRIMARY KEY (`userID`);

ALTER TABLE `w_authtokens`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `w_domaindata`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `w_logins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `w_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `w_userlevels`
  MODIFY `userLevelID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `w_users`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;
