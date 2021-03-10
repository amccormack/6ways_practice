FROM debian:jessie
RUN sed -i 's/main/main contrib non-free/g' /etc/apt/sources.list
RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y openssh-server sudo vim nginx mysql-server php5-fpm php5-mysql nano net-tools netcat-traditional curl cron

COPY db.mysql /tmp/web.mysql
RUN /etc/init.d/mysql start & sleep 10 && \
  mysql -h localhost < /tmp/web.mysql && \
  rm /tmp/web.mysql

RUN sed -i 's/127.0.0.1/0.0.0.0/g' /etc/mysql/my.cnf && \
  sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/g' /etc/php5/fpm/php.ini && \
  sed -i 's/#general_log.*$/secure_file_priv=""/g' /etc/mysql/my.cnf

COPY default /etc/nginx/sites-available/default
COPY index.php /var/www/html/index.php
RUN chown root:root /etc/nginx/sites-available/default && chmod 644 /etc/nginx/sites-available/default && \
  mkdir -p /var/www/html/images && chown www-data:www-data /var/www/html/images && \
  chmod a+w /var/www/html/images && \
  chown www-data:www-data /var/www/html/index.php

COPY entrypoint.sh /root/entrypoint.sh


RUN touch /etc/proof.txt && chmod a+w /etc/proof.txt
# Generate your own credentials. Default is 'complete555'
RUN useradd -rm -d /home/turing -s /bin/bash -g root -G sudo -u 1005 turing -p '$6$Zb5NNmKN$7xm.WoHG/EFnRHE665ATeM1eVOrMzUZMNlqpwZ0cBBXUtzG.QHnWW2P8Cktcw/aSTQt7SULqcbt.Du0eMNgaj1'

ENTRYPOINT /root/entrypoint.sh
