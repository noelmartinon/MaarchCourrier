#!/bin/sh
eventStackPath='/var/www/html/MaarchCourrier/modules/notifications/batch/basket_event_stack.php'
cd /var/www/html/MaarchCourrier/modules/notifications/batch/
php $eventStackPath -c /var/www/html/MaarchCourrier/modules/notifications/batch/config/config.xml -n BASKETS