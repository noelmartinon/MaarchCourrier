#!/bin/sh
mlbStackPath='/var/www/html/MaarchCourrier/bin/notification/stack_letterbox_alerts.php'
eventStackPath='/var/www/html/MaarchCourrier/bin/notification/process_event_stack.php'
php $mlbStackPath   -c /var/www/html/MaarchCourrier/bin/notification/config/config.xml 
php $eventStackPath -c /var/www/html/MaarchCourrier/bin/notification/config/config.xml -n RET1
php $eventStackPath -c /var/www/html/MaarchCourrier/bin/notification/config/config.xml -n RET2
