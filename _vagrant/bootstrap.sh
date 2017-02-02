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
yum install php php-pdo php-mysql php-redis -y
yum install nginx -y
yum install php-fpm -y
yum install mysql mysql-server -y
yum install redis -y

#####################
# Misc stuff I like #
#####################
cp /vagrant/_vagrant/configs/bashrc /home/vagrant/.bashrc
cp /vagrant/_vagrant/configs/bashrc /root/.bashrc
yum install nano -y
yum install htop -y
yum install telnet -y
yum install mlocate -y

##############################################
# Setup any configs before starting services #
##############################################
cp /vagrant/_vagrant/configs/nginx.conf /etc/nginx
cp /vagrant/_vagrant/configs/php-fpm-www.conf /etc/php-fpm.d/www.conf
chown -R nginx:root /var/log/php-fpm

#######
# PHP #
#######
# Set timezone
sudo sed -i "s/;date.timezone =/date.timezone = UTC/g" /etc/php.ini
# Short tags
sudo sed -i "s/short_open_tag = Off/short_open_tag = On/g" /etc/php.ini
# Display errors
sudo sed -i "s/display_errors = Off/display_errors = On/g" /etc/php.ini

#############################
# Enable and start services #
#############################
chkconfig nginx on
chkconfig redis on
chkconfig php-fpm on
chkconfig mysqld on
service nginx start
service redis start
service php-fpm start
service mysqld start

#################################
# RUN mysql_secure_installation #
#################################
SECURE_MYSQL=$(expect -c "
set timeout 10
spawn mysql_secure_installation
expect \"Enter current password for root (enter for none):\"
send \"\r\"
expect \"Set root password?\"
send \"y\r\"
expect \"New password:\"
send \"password\r\"
expect \"Re-enter new password:\"
send \"password\r\"
expect \"Remove anonymous users?\"
send \"y\r\"
expect \"Disallow root login remotely?\"
send \"n\r\"
expect \"Remove test database and access to it?\"
send \"y\r\"
expect \"Reload privilege tables now?\"
send \"y\r\"
expect eof
")
echo "$SECURE_MYSQL"
echo "CREATE DATABASE IF NOT EXISTS phpbase;" | mysql --user=root --password=password
echo "GRANT ALL ON phpbase.* to phpbase@'%' IDENTIFIED BY 'phpbase';" | mysql --user=root --password=password
mysql --user=phpbase --password=phpbase --database=phpbase < /vagrant/sql/account.sql

##############################################
# Re-link www dir to /vagrant for web server #
##############################################
if ! [ -L /var/www ]; then
  rm -rf /var/www
  ln -fs /vagrant /var/www
fi

##################
# Final commands #
##################
updatedb
