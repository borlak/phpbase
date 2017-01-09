#!/usr/bin/env bash

# Needed for nginx
yum install epel-release -y

# Update
yum upgrade -y

# Libraries we need for code/webserver
yum install php -y
yum install nginx -y
yum install mysql -y
yum install mysql-server -y

# Misc stuff I like
yum install htop -y
yum install telnet -y
yum install mlocate -y
updatedb

# Enable and start services
chkconfig nginx on
chkconfig mysqld on
service nginx start
service mysqld start

if ! [ -L /var/www ]; then
  rm -rf /var/www
  ln -fs /vagrant /var/www
fi

