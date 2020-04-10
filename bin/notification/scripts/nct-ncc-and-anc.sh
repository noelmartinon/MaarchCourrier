#!/bin/sh
eventStackPath='/var/www/html/MaarchCourrier/bin/notification/process_event_stack.php'
php $eventStackPath -c /var/www/html/MaarchCourrier/bin/notification/config/config.xml -n NCC
php $eventStackPath -c /var/www/html/MaarchCourrier/bin/notification/config/config.xml -n ANC
php $eventStackPath -c /var/www/html/MaarchCourrier/bin/notification/config/config.xml -n AND
php $eventStackPath -c /var/www/html/MaarchCourrier/bin/notification/config/config.xml -n RED
