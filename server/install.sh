#!/bin/bash
echo "IntISP Server Installation Utility"
echo "Adaclare Technologies"
if [ -f /etc/redhat-release ]; then
  OS_TYPE=yum
  echo "YUM BASED SYSTEM DETECTED!"
  yum -y install epel-release
  yum -y install yum-utils
  yum-config-manager --enable remi-php72
  wget http://repo.mysql.com/mysql-community-release-el7-5.noarch.rpm
  rpm -ivh mysql-community-release-el7-5.noarch.rpm
  rpm -Uvh https://mirror.webtatic.com/yum/el7/webtatic-release.rpm
  rm -rf mysql-community-release-el7-5.noarch.rpm
  yum -y update
  yum -y remove php* mod_php*
  yum -y install mysql-server httpd php72w php72w-common php72w-opcache php72w-cli php72w-gd php72w-curl php72w-mysql python-certbot-apache wget curl
  echo "Depends Installed"
  systemctl enable mysql
  systemctl enable httpd
  systemctl start mysql
  systemctl start httpd
  apachectl restart
  mysql_secure_installation
  wget https://getcomposer.org/installer
  php installer --install-dir=/usr/local/bin --filename=composer
  rm -rf installer
  cd ../ && /usr/local/bin/composer install
  echo "Services Configured"
  echo "Copying Files"
  rm -rf /var/www/html/index.html
  mkdir /var/www/html/cache
  touch /var/www/html/config.php
  DIRFIX=$(pwd)
  cp -rf $DIRFIX/server/httpd.conf /etc/httpd/conf/httpd.conf
  cp -rf $DIRFIX/includes /var/www/html/includes
  cp -rf $DIRFIX/install /var/www/html/install
  cp -rf $DIRFIX/plugins /var/www/html/plugins
  cp -rf $DIRFIX/templates /var/www/html/templates
  cp -rf $DIRFIX/thirdparty /var/www/html/thirdparty
  cp -rf $DIRFIX/vendor /var/www/html/vendor
  cp -rf $DIRFIX/.htaccess /var/www/html/.htaccess
  cp -rf $DIRFIX/action.php /var/www/html/action.php
  cp -rf $DIRFIX/index.php /var/www/html/index.php
  cp -rf $DIRFIX/server/vhost_creator/centos /usr/bin/vhost_creator
  mkdir /var/intisp
  echo "Done! Copying Files, now fixing permissions..."
  chmod +x /usr/bin/vhost_creator
  chmod -R 777 /var/www/html/
  apachectl restart
  echo "The installation is complete. Please open a browser and navigate to http://localhost"
  echo "You will need your MySQL Password"
fi

if [ -f /etc/lsb-release ]; then

  OS_TYPE=aptget
  echo "APT BASED SYSTEM DETECTED!"
  echo "Installing Depends"
  apt-get update
  apt-get install software-properties-common python-software-properties -y
  add-apt-repository ppa:ondrej/php -y
  add-apt-repository ppa:certbot/certbot -y
  apt-get update
  apt-get install php mysql-server apache2 php-mail php-curl php-mysql php-gd python-certbot-apache wget curl -y
  echo "Depends Installed"
  echo "Configure & Start Services"
  a2enmod rewrite
  systemctl enable mysql
  systemctl enable apache2
  systemctl start mysql
  systemctl start apache2
  mysql_secure_installation
  bash eng_ioncube.sh
  wget https://getcomposer.org/installer
  php installer --install-dir=/usr/local/bin --filename=composer
  rm -rf installer
  cd ../ && /usr/local/bin/composer install
  echo "Services Configured"
  echo "Copying Files"
  rm -rf /var/www/html/index.html
  mkdir /var/www/html/cache
  touch /var/www/html/config.php
  DIRFIX=$(pwd)
  cp -rf $DIRFIX/includes /var/www/html/includes
  cp -rf $DIRFIX/install /var/www/html/install
  cp -rf $DIRFIX/plugins /var/www/html/plugins
  cp -rf $DIRFIX/templates /var/www/html/templates
  cp -rf $DIRFIX/vendor /var/www/html/vendor
  cp -rf $DIRFIX/thirdparty /var/www/html/thirdparty
  cp -rf $DIRFIX/.htaccess /var/www/html/.htaccess
  cp -rf $DIRFIX/action.php /var/www/html/action.php
  cp -rf $DIRFIX/index.php /var/www/html/index.php
  cp -rf $DIRFIX/server/vhost_creator/ubuntu /usr/bin/vhost_creator
  mkdir /var/intisp
  echo "Done! Copying Files, now fixing permissions..."
  chmod +x /usr/bin/vhost_creator
  chmod -R 777 /var/www/html/
  echo "The installation is complete. Please open a browser and navigate to http://localhost"
  echo "You will need your MySQL Password"
  fi
