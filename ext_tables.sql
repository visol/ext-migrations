#
# Table structure for table 'tt_content'
#
CREATE TABLE tx_migrations (
	 `version` varchar(1024) DEFAULT '' NOT NULL,
	 `executed_at` datetime DEFAULT NULL,
	 `execution_time` int(11) DEFAULT NULL,
);
