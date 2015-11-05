
DROP TABLE IF EXISTS sustc_clear;
CREATE TABLE sustc_clear (
  id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  session char(36) NOT NULL DEFAULT '',
  status tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  UNIQUE KEY session (session)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS sustc_clear_user;
CREATE TABLE sustc_clear_user (
  uid mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  studentid mediumint(8) unsigned NOT NULL,
  realname char(15) NOT NULL DEFAULT '',
  class mediumint(8) NOT NULL DEFAULT '0',
  disabled tinyint(1) unsigned NOT NULL DEFAULT '0',
  famous mediumint(8) NOT NULL DEFAULT '0',
  PRIMARY KEY (uid),
  UNIQUE KEY studentid (studentid),
  KEY realname (realname),
  KEY class (class),
  KEY disabled (disabled),
  KEY famous (famous)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
