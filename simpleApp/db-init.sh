#!/bin/bash
sudo su -
source /usr/local/osmosix/etc/userenv
curl https://raw.githubusercontent.com/yannickcastano/cloudcenter/master/simpleApp/db-init.sql --output /tmp/db-init.sql
echo "[mysqld]" >> /etc/mysql/my.cnf
echo "skip-grant-tables" >> /etc/mysql/my.cnf
service mysql restart
