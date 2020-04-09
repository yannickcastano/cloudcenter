#!/bin/bash
source /usr/local/osmosix/etc/userenv
curl https://raw.githubusercontent.com/yannickcastano/cloudcenter/master/simpleApp/db-init.sql --output /tmp/db-init.sql
sudo bash -c 'echo "[mysqld]" >> /etc/mysql/my.cnf'
sudo bash -c 'echo "skip-grant-tables" >> /etc/mysql/my.cnf'
sudo service mysql restart
mysql -u root < /tmp/db-init.sql
