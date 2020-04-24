#!/bin/sh
path='/var/www/html/maarch_v2'
cd $path
php  './modules/export_seda/batch/CheckAllReply.php' -c /var/www/html/maarch_v2/modules/export_seda/batch/config/config.xml
