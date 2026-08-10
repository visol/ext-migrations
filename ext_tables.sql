#
# Table structure for table 'tx_migrations'
#
# The version column must not have a default value: doctrine/migrations builds the metadata table
# without one and refuses to run as long as the database differs from what it expects.
#
CREATE TABLE tx_migrations (
	 `version` varchar(1024) NOT NULL,
	 `executed_at` datetime DEFAULT NULL,
	 `execution_time` int(11) DEFAULT NULL,
);
