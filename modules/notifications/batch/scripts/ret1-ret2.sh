#!/bin/sh
mlbStackPath='/var/www/html/MaarchCourrier/modules/notifications/batch/stack_letterbox_alerts.php'
eventStackPath='/var/www/html/MaarchCourrier/modules/notifications/batch/process_event_stack.php'
cd  /var/www/html/MaarchCourrier/modules/notifications/batch/
php $mlbStackPath   -c /var/www/html/MaarchCourrier/modules/notifications/batch/config/config.xml 
php $eventStackPath -c /var/www/html/MaarchCourrier/modules/notifications/batch/config/config.xml -n RET1
php $eventStackPath -c /var/www/html/MaarchCourrier/modules/notifications/batch/config/config.xml -n RET2
