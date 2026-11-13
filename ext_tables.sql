#
# Table structure for table 'tx_migrations'
#
# Matches the metadata storage of doctrine/migrations (see AbstractDoctrineCommand), so that
# Doctrine finds the table complete and never has to alter it.
#
CREATE TABLE tx_migrations (
	`version` varchar(191) NOT NULL,
	`executed_at` datetime DEFAULT NULL,
	`execution_time` int(11) DEFAULT NULL,

	PRIMARY KEY (`version`)
);
