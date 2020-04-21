#!/bin/bash
#source /usr/local/osmosix/etc/userenv

#-- download database config init file
curl https://raw.githubusercontent.com/yannickcastano/cloudcenter/master/simpleApp/db-init.sql --output /tmp/db-init.sql
curl https://raw.githubusercontent.com/yannickcastano/cloudcenter/master/simpleApp/db-root-access.sql --output /tmp/db-root-access.sql

#-- change MySQL root password 
sudo bash -c 'echo "[mysqld]" >> /etc/mysql/my.cnf'
sudo bash -c 'echo "bind-address = 0.0.0.0" >> /etc/mysql/my.cnf'
sudo bash -c 'echo "skip-grant-tables" >> /etc/mysql/my.cnf'
sudo service mysql restart
mysql -u root < /tmp/db-root-access.sql
sudo sed -i '/skip-grant-tables/d' /etc/mysql/my.cnf
sudo service mysql restart

#-- init database config
mysql --user=root --password=S3cur1ty01 < /tmp/db-init.sql
