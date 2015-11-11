
DROP TABLE IF EXISTS sustc_user;
CREATE TABLE sustc_user (
  uid mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  username char(15) NOT NULL DEFAULT '',
  username_clean char(15) NOT NULL DEFAULT '',
  `password` char(36) NOT NULL DEFAULT '',
  email char(40) NOT NULL DEFAULT '',
  PRIMARY KEY (uid),
  UNIQUE KEY username_clean (username_clean)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS sustc_user_deposit;
CREATE TABLE sustc_user_deposit (
  id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  uid mediumint(8) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  trade_no char(64) NOT NULL DEFAULT '',
  price mediumint(8) unsigned NOT NULL DEFAULT '0',
  createtime int(10) unsigned NOT NULL DEFAULT '0',
  updatetime int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  KEY uid (uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS sustc_user_profile;
CREATE TABLE sustc_user_profile (
  uid mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  avator mediumint(8) NOT NULL DEFAULT '0',
  realname varchar(255) NOT NULL DEFAULT '',
  gender tinyint(1) NOT NULL DEFAULT '0',
  studentid varchar(32) NOT NULL DEFAULT '',
  telephone varchar(255) NOT NULL DEFAULT '',
  bio text NOT NULL,
  PRIMARY KEY (uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS sustc_user_setting;
CREATE TABLE sustc_user_setting (
  uid mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  preferprinter_id mediumint(8) NOT NULL DEFAULT '0',
  PRIMARY KEY (uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS sustc_user_status;
CREATE TABLE sustc_user_status (
  uid mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  group_id smallint(6) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  credit int(10) NOT NULL DEFAULT '0',
  newpm smallint(6) unsigned NOT NULL DEFAULT '0',
  balance mediumint(8) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS sustc_print_node;
CREATE TABLE sustc_print_node (
  id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  name char(15) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `under_repairing` tinyint(1) NOT NULL DEFAULT '0',
  lasttime int(10) unsigned NOT NULL DEFAULT '0',
  page smallint(6) unsigned NOT NULL DEFAULT '0',
  description varchar(255) NOT NULL,
  duplex tinyint(1) NOT NULL DEFAULT '0',
  colorful tinyint(1) NOT NULL DEFAULT '0',
  accesskey char(32) NOT NULL DEFAULT '',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS sustc_attachment;
CREATE TABLE sustc_attachment (
  id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  uid mediumint(8) unsigned NOT NULL DEFAULT '0',
  filename varchar(255) NOT NULL DEFAULT '',
  size int(10) unsigned NOT NULL DEFAULT '0',
  uploadtime int(10) unsigned NOT NULL DEFAULT '0',
  type char(8) NOT NULL,
  ext varchar(8) NOT NULL,
  savepath varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  KEY uid (uid),
  KEY type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS sustc_print_queue;
CREATE TABLE sustc_print_queue (
  id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  uid mediumint(8) unsigned NOT NULL DEFAULT '0',
  node_id mediumint(8) unsigned NOT NULL DEFAULT '0',
  document_id mediumint(8) unsigned NOT NULL DEFAULT '0',
  page smallint(6) unsigned NOT NULL DEFAULT '0',
  duplex tinyint(1) NOT NULL DEFAULT '0',
  colorful tinyint(1) NOT NULL DEFAULT '0',
  copies smallint(6) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  starttime int(10) unsigned NOT NULL DEFAULT '0',
  endtime int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  KEY uid (uid,starttime),
  KEY node_id (node_id,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
