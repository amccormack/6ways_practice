#!/bin/bash
ip addr
/etc/init.d/nginx start & 
/etc/init.d/ssh start &
/etc/init.d/mysql start &
/etc/init.d/cron start &
/etc/init.d/php5-fpm start &
sleep $((60*60))
