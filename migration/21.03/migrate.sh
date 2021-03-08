#!/bin/sh
php ./migrateAttachmentTypes.php
php ./migrateTemplates.php
php ./migrateCivilities.php
php ./migrateSQL.php # mettre en dernier
