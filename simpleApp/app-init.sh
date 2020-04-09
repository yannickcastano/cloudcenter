#!/bin/bash
source /usr/local/osmosix/etc/userenv
#-- restart apache to get php lib loadded
sudo service apache2 restart
#-- download php app
sudo curl https://raw.githubusercontent.com/yannickcastano/cloudcenter/master/simpleApp/app.php --output /var/www/html/app.php
