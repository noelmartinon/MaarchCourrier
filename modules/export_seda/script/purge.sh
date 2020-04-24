#!/bin/sh
path='/var/www/html/maarch_courrier'
cd $path
php  './modules/export_seda/batch/BatchPurge.php' -c /var/www/html/maarch_courrier/modules/export_seda/batch/config/config.xml