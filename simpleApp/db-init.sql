CREATE DATABASE IF NOT EXISTS `simpleAppDB`;
GRANT ALL PRIVILEGES ON simpleAppDB.* TO 'admin'@'%' IDENTIFIED BY 'S3cur1ty01' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON simpleAppDB.* TO 'admin'@'localhost' IDENTIFIED BY 'S3cur1ty01' WITH GRANT OPTION;

USE simpleAppDB;

CREATE TABLE IF NOT EXISTS people (
  id INT(4) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(30),
  last_name VARCHAR(30),
  INDEX(last_name)
) engine=InnoDB;

INSERT INTO people (first_name,last_name) VALUES ('francois','couderc'),('julien','couturier'),('marc','picovsky'),('sylvain','larue'),('yannick','castano');

CREATE TABLE IF NOT EXISTS styles (
  id INT(4) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  style VARCHAR(30),
  INDEX(style)
) engine=InnoDB;

CREATE TABLE IF NOT EXISTS restaurants (
  id INT(4) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  type_id INT(4) UNSIGNED NOT NULL,
  name VARCHAR(30),
  address VARCHAR(255),
  city VARCHAR(80),
  telephone VARCHAR(20),
  INDEX(name),
  FOREIGN KEY (type_id) REFERENCES styles(id)
) engine=InnoDB;

CREATE TABLE IF NOT EXISTS visits (
  id INT(4) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  restaurant_id INT(4) UNSIGNED NOT NULL,
  people_id INT(4) UNSIGNED NOT NULL,
  visit_date DATE,
  comment VARCHAR(255),
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id),
  FOREIGN KEY (people_id) REFERENCES people(id)
) engine=InnoDB;

