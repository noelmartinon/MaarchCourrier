#!/bin/sh
eventStackPath='/var/www/MaarchCourrier/modules/notifications/batch/process_event_stack.php'
cd /var/www/MaarchCourrier/modules/notifications/batch/
php $eventStackPath -c /var/www/MaarchCourrier/modules/notifications/batch/config/config.xml -n NCC
php $eventStackPath -c /var/www/MaarchCourrier/modules/notifications/batch/config/config.xml -n ANC
php $eventStackPath -c /var/www/MaarchCourrier/modules/notifications/batch/config/config.xml -n AND
php $eventStackPath -c /var/www/MaarchCourrier/modules/notifications/batch/config/config.xml -n RED
