
DROP TABLE IF EXISTS sustc_survey;
CREATE TABLE sustc_survey (
  id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  uid mediumint(8) unsigned NOT NULL DEFAULT '0',
  disabled tinyint(1) unsigned NOT NULL DEFAULT '0',
  answer_count mediumint(8) unsigned NOT NULL DEFAULT '0',
  title char(128) NOT NULL,
  description char(255) NOT NULL,
  question TEXT NOT NULL,
  PRIMARY KEY (id),
  KEY (uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS sustc_survey_data;
CREATE TABLE sustc_survey_data (
  id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  survey_id mediumint(8) unsigned NOT NULL,
  unique_answer char(255) NOT NULL,
  answer TEXT NOT NULL,
  PRIMARY KEY (id),
  KEY survey_id (survey_id),
  KEY unique_answer (unique_answer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#alter table `sustc_survey` add uid mediumint(8) unsigned NOT NULL DEFAULT '0' after id;
#alter table `sustc_survey` add key(uid);
#alter table `sustc_survey` modify uid mediumint(8) unsigned NOT NULL DEFAULT '0' after id;