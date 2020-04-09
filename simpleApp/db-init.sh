#!/bin/bash
source /usr/local/osmosix/etc/userenv

#-- download database config init file
curl https://raw.githubusercontent.com/yannickcastano/cloudcenter/master/simpleApp/db-init.sql --output /tmp/db-init.sql

#-- change MySQL root password 
sudo bash -c 'echo "[mysqld]" >> /etc/mysql/my.cnf'
sudo bash -c 'echo "skip-grant-tables" >> /etc/mysql/my.cnf'
sudo service mysql restart
mysql -u root
USE mysql;
UPDATE user set authentication_string=PASSWORD("S3cur1ty01") where User='root';
UPDATE user set plugin="mysql_native_password" where User='root';
FLUSH privileges;
QUIT
sudo sed -i '/skip-grant-tables/d' /etc/mysql/my.cnf
sudo service mysql restart

#-- init database config
mysql --user=root --password=S3cur1ty01 < /tmp/db-init.sql
