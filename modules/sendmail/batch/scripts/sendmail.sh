#!/bin/sh cd /var/www/MaarchCourrier/modules/notifications/batch/
emailStackPath='/var/www/MaarchCourrier/modules/notifications/batch/process_email_stack.php'
php $emailStackPath -c /var/www/MaarchCourrier/modules/notifications/batch/config/config.xml

