#!/bin/sh
cd /var/www/MaarchCourrier/modules/sendmail/batch/
emailStackPath='/var/www/MaarchCourrier/modules/sendmail/batch/process_emails.php'
php $emailStackPath -c /var/www/MaarchCourrier/modules/sendmail/batch/config/config.xml

