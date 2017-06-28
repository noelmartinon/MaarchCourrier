#!/bin/sh
path='/var/www/html/maarch_courrier'
cd $path
php  './modules/export_seda/batch/CheckAllReply.php'