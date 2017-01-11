#!/usr/bin/env bash

################
# Linux config #
################
hostname phpbase

####################
# Needed for nginx #
####################
yum install epel-release -y

##########
# Update #
##########
yum upgrade -y

########################################
# Libraries we need for code/webserver #
########################################
yum install php -y
yum install nginx -y
yum install php-fpm -y
yum install mysql -y
yum install mysql-server -y

#####################
# Misc stuff I like #
#####################
cp /vagrant/_vagrant/configs/bashrc /home/vagrant/.bashrc
cp /vagrant/_vagrant/configs/bashrc /root/.bashrc
yum install nano -y
yum install htop -y
yum install telnet -y
yum install mlocate -y
updatedb

##############################################
# Setup any configs before starting services #
##############################################
cp /vagrant/_vagrant/configs/nginx.conf /etc/nginx
cp /vagrant/_vagrant/configs/php-fpm-www.conf /etc/php-fpm.d/www.conf

#######
# PHP #
#######
# Redis is our main cache
yum install php-redis -y
# Set timezone
sudo sed -i "s/;date.timezone =/date.timezone = UTC/g" /etc/php.ini
# Short tags
sudo sed -i "s/short_open_tag = Off/short_open_tag = On/g" /etc/php.ini


#############################
# Enable and start services #
#############################
chkconfig nginx on
chkconfig php-fpm on
chkconfig mysqld on
service nginx start
service php-fpm start
service mysqld start

# Re-link www dir to /vagrant for web server
if ! [ -L /var/www ]; then
  rm -rf /var/www
  ln -fs /vagrant /var/www
fi
