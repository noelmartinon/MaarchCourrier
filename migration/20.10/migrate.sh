#!/bin/sh
php ./migrateConfigXml.php # mettre en premier
php ./migrateNotificationsProperties.php
php ./migrateNotificationsConfig.php
php ./migrateRemoteSignatureBookConfig.php
php ./migrateImages.php
php ./migrateCustomXml.php # mettre en dernier
