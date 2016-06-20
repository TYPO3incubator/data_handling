#
# Table structure for table 'sys_event_store'
#
CREATE TABLE sys_event_store (
	uid int(11) unsigned NOT NULL auto_increment,
	event_stream varchar(128) NOT NULL,
	event_id varchar(128) NOT NULL,
	event_name varchar(128) NOT NULL,
	event_date TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
	data MEDIUMTEXT,
	metadata TEXT,
  PRIMARY KEY (uid)
);
