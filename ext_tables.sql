#
# Table structure for table 'projection_table_version'
#
CREATE TABLE projection_table_version (
	workspace_id int(11) unsigned DEFAULT '0' NOT NULL,
	page_id int(11) unsigned DEFAULT '0' NOT NULL,
	table_name varchar(255) DEFAULT '' NOT NULL,
	version_count int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (workspace_id, page_id, table_name)
);