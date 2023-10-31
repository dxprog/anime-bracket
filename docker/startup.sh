#!/bin/bash
service php7.4-fpm start
service mariadb start
service redis-server start
service memcached start
service nginx start

tail -f /var/log/nginx/animebracket.error.log
