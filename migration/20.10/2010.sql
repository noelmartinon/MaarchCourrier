-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 20.03 to 20.10                                  --
--                                                                          --
--                                                                          --
-- *************************************************************************--
UPDATE parameters SET param_value_string = '20.10' WHERE id = 'database_version';

DROP VIEW IF EXISTS res_view_letterbox;

/* SENDMAIL */
DROP TABLE IF EXISTS sendmail;

/* REPORTS */
DROP TABLE IF EXISTS usergroups_reports;
DELETE FROM usergroups_services WHERE service_id IN ('reports', 'admin_reports');

/*NOTIF_EMAIL_STACK*/
ALTER TABLE notif_email_stack DROP COLUMN IF EXISTS sender;
ALTER TABLE notif_email_stack DROP COLUMN IF EXISTS charset;
ALTER TABLE notif_email_stack DROP COLUMN IF EXISTS text_body;
ALTER TABLE notif_email_stack DROP COLUMN IF EXISTS module;

/* USERS */
ALTER TABLE users DROP COLUMN IF EXISTS cookie_key;
ALTER TABLE users DROP COLUMN IF EXISTS cookie_date;
ALTER TABLE users DROP COLUMN IF EXISTS refresh_token;
ALTER TABLE users ADD COLUMN refresh_token jsonb NOT NULL DEFAULT '[]';

DO $$ BEGIN
    IF (SELECT count(column_name) from information_schema.columns where table_name = 'notif_event_stack' and column_name = 'user_id' and data_type != 'integer') THEN
        ALTER TABLE notif_event_stack ADD COLUMN user_id_tmp INTEGER;
        UPDATE notif_event_stack set user_id_tmp = (select id FROM users where users.user_id = notif_event_stack.user_id);
        DELETE FROM notif_event_stack WHERE user_id_tmp IS NULL;
        ALTER TABLE notif_event_stack ALTER COLUMN user_id_tmp set not null;
        ALTER TABLE notif_event_stack DROP COLUMN IF EXISTS user_id;
        ALTER TABLE notif_event_stack RENAME COLUMN user_id_tmp TO user_id;
    END IF;
END$$;
DO $$ BEGIN
    IF (SELECT count(column_name) from information_schema.columns where table_name = 'users_email_signatures' and column_name = 'user_id' and data_type != 'integer') THEN
        ALTER TABLE users_email_signatures ADD COLUMN user_id_tmp INTEGER;
        UPDATE users_email_signatures set user_id_tmp = (select id FROM users where users.user_id = users_email_signatures.user_id);
        DELETE FROM users_email_signatures WHERE user_id_tmp IS NULL;
        ALTER TABLE users_email_signatures ALTER COLUMN user_id_tmp set not null;
        ALTER TABLE users_email_signatures DROP COLUMN IF EXISTS user_id;
        ALTER TABLE users_email_signatures RENAME COLUMN user_id_tmp TO user_id;
    END IF;
END$$;
DO $$ BEGIN
    IF (SELECT count(column_name) from information_schema.columns where table_name = 'users_entities' and column_name = 'user_id' and data_type != 'integer') THEN
        ALTER TABLE users_entities ADD COLUMN user_id_tmp INTEGER;
        UPDATE users_entities set user_id_tmp = (select id FROM users where users.user_id = users_entities.user_id);
        DELETE FROM users_entities WHERE user_id_tmp IS NULL;
        ALTER TABLE users_entities ALTER COLUMN user_id_tmp set not null;
        ALTER TABLE users_entities DROP COLUMN IF EXISTS user_id;
        ALTER TABLE users_entities RENAME COLUMN user_id_tmp TO user_id;
    END IF;
END$$;
DO $$ BEGIN
    IF (SELECT count(column_name) from information_schema.columns where table_name = 'res_letterbox' and column_name = 'dest_user' and data_type != 'integer') THEN
        ALTER TABLE res_letterbox ADD COLUMN dest_user_tmp INTEGER;
        UPDATE res_letterbox set dest_user_tmp = (select id FROM users where users.user_id = res_letterbox.dest_user);
        ALTER TABLE res_letterbox DROP COLUMN IF EXISTS dest_user;
        ALTER TABLE res_letterbox RENAME COLUMN dest_user_tmp TO dest_user;
        UPDATE baskets SET basket_clause = REGEXP_REPLACE(basket_clause, 'dest_user(\s*)=(\s*)@user', 'dest_user = @user_id', 'gmi');
        UPDATE baskets SET basket_clause = REGEXP_REPLACE(basket_clause, 'dest_user(\s*)=(\s*)''', 'dest_user is null', 'gmi');
        UPDATE baskets SET basket_clause = REGEXP_REPLACE(basket_clause, 'dest_user(\s*)=(\s*)""', 'dest_user is null', 'gmi');
        UPDATE security SET where_clause = REGEXP_REPLACE(where_clause, 'dest_user(\s*)=(\s*)@user', 'dest_user = @user_id', 'gmi');
    END IF;
END$$;
DO $$ BEGIN
    IF (SELECT count(column_name) from information_schema.columns where table_name = 'basket_persistent_mode' and column_name = 'user_id' and data_type != 'integer') THEN
        ALTER TABLE basket_persistent_mode ADD COLUMN user_id_tmp INTEGER;
        UPDATE basket_persistent_mode set user_id_tmp = (select id FROM users where users.user_id = basket_persistent_mode.user_id);
        DELETE FROM basket_persistent_mode WHERE user_id_tmp IS NULL;
        ALTER TABLE basket_persistent_mode ALTER COLUMN user_id_tmp set not null;
        ALTER TABLE basket_persistent_mode DROP COLUMN IF EXISTS user_id;
        ALTER TABLE basket_persistent_mode RENAME COLUMN user_id_tmp TO user_id;
    END IF;
END$$;
DO $$ BEGIN
    IF (SELECT count(column_name) from information_schema.columns where table_name = 'res_mark_as_read' and column_name = 'user_id' and data_type != 'integer') THEN
        ALTER TABLE res_mark_as_read ADD COLUMN user_id_tmp INTEGER;
        UPDATE res_mark_as_read set user_id_tmp = (select id FROM users where users.user_id = res_mark_as_read.user_id);
        DELETE FROM res_mark_as_read WHERE user_id_tmp IS NULL;
        ALTER TABLE res_mark_as_read ALTER COLUMN user_id_tmp set not null;
        ALTER TABLE res_mark_as_read DROP COLUMN IF EXISTS user_id;
        ALTER TABLE res_mark_as_read RENAME COLUMN user_id_tmp TO user_id;
        UPDATE baskets SET basket_clause = REGEXP_REPLACE(basket_clause, 'from res_mark_as_read WHERE user_id(\s*)=(\s*)@user', 'from res_mark_as_read WHERE user_id = @user_id', 'gmi');
    END IF;
END$$;
DO $$ BEGIN
    IF (SELECT count(column_name) from information_schema.columns where table_name = 'res_attachments' and column_name = 'typist' and data_type != 'integer') THEN
        ALTER TABLE res_attachments ADD COLUMN typist_tmp INTEGER;
        UPDATE res_attachments set typist_tmp = (select id FROM users where users.user_id = res_attachments.typist);
        ALTER TABLE res_attachments DROP COLUMN IF EXISTS typist;
        ALTER TABLE res_attachments RENAME COLUMN typist_tmp TO typist;
    END IF;
END$$;
DO $$ BEGIN
    IF (SELECT count(column_name) from information_schema.columns where table_name = 'listinstance_history_details' and column_name = 'item_id' and data_type != 'integer') THEN
        ALTER TABLE listinstance_history_details ADD COLUMN item_id_tmp INTEGER;
        UPDATE listinstance_history_details set item_id_tmp = (select id FROM users where users.user_id = listinstance_history_details.item_id) WHERE item_type = 'user_id';
        UPDATE listinstance_history_details set item_id_tmp = (select id FROM entities where entities.entity_id = listinstance_history_details.item_id) WHERE item_type = 'entity_id';
        ALTER TABLE listinstance_history_details DROP COLUMN IF EXISTS item_id;
        ALTER TABLE listinstance_history_details RENAME COLUMN item_id_tmp TO item_id;
    END IF;
END$$;
DO $$ BEGIN
    IF (SELECT count(column_name) from information_schema.columns where table_name = 'listinstance_history_details' and column_name = 'added_by_user' and data_type != 'integer') THEN
        ALTER TABLE listinstance_history_details ADD COLUMN added_by_user_tmp INTEGER;
        UPDATE listinstance_history_details set added_by_user_tmp = (select id FROM users where users.user_id = listinstance_history_details.added_by_user);
        ALTER TABLE listinstance_history_details DROP COLUMN IF EXISTS added_by_user;
        ALTER TABLE listinstance_history_details RENAME COLUMN added_by_user_tmp TO added_by_user;
    END IF;
END$$;
DO $$ BEGIN
    IF (SELECT count(column_name) from information_schema.columns where table_name = 'listinstance' and column_name = 'item_id' and data_type != 'integer') THEN
        ALTER TABLE listinstance ADD COLUMN item_id_tmp INTEGER;
        UPDATE listinstance set item_id_tmp = (select id FROM users where users.user_id = listinstance.item_id) WHERE item_type = 'user_id';
        UPDATE listinstance set item_id_tmp = (select id FROM entities where entities.entity_id = listinstance.item_id) WHERE item_type = 'entity_id';
        ALTER TABLE listinstance DROP COLUMN IF EXISTS item_id;
        ALTER TABLE listinstance RENAME COLUMN item_id_tmp TO item_id;
        UPDATE baskets SET basket_clause = REGEXP_REPLACE(basket_clause, 'item_id(\s*)=(\s*)@user ', 'item_id = @user_id ', 'gmi');
        UPDATE baskets SET basket_clause = REGEXP_REPLACE(basket_clause, 'item_id(\s*)in(\s*)\(@my_entities\)', 'item_id in (@my_entities_id)', 'gmi');
        UPDATE baskets SET basket_clause = REGEXP_REPLACE(basket_clause, 'item_id(\s*)in(\s*)\(@my_primary_entity\)', 'item_id in (@my_primary_entity_id)', 'gmi');
        UPDATE baskets SET basket_clause = REGEXP_REPLACE(basket_clause, '\(res_id,(\s*)@user\)', '(res_id, @user_id)', 'gmi');
        UPDATE security SET where_clause = REGEXP_REPLACE(where_clause, 'item_id(\s*)=(\s*)@user ', 'item_id = @user_id ', 'gmi');
    END IF;
END$$;
DO $$ BEGIN
    IF (SELECT count(column_name) from information_schema.columns where table_name = 'listinstance' and column_name = 'added_by_user' and data_type != 'integer') THEN
        ALTER TABLE listinstance ADD COLUMN added_by_user_tmp INTEGER;
        UPDATE listinstance set added_by_user_tmp = (select id FROM users where users.user_id = listinstance.added_by_user);
        ALTER TABLE listinstance DROP COLUMN IF EXISTS added_by_user;
        ALTER TABLE listinstance RENAME COLUMN added_by_user_tmp TO added_by_user;
    END IF;
END$$;
DO $$ BEGIN
    IF (SELECT count(column_name) from information_schema.columns where table_name = 'history' and column_name = 'user_id' and data_type != 'integer') THEN
        ALTER TABLE history ADD COLUMN user_id_tmp INTEGER;
        UPDATE history set user_id_tmp = (select id FROM users where users.user_id = history.user_id);
        ALTER TABLE history DROP COLUMN IF EXISTS user_id;
        ALTER TABLE history RENAME COLUMN user_id_tmp TO user_id;
        UPDATE history set record_id = (select id FROM users where users.user_id = history.record_id) WHERE table_name = 'users';
    END IF;
END$$;
DO $$ BEGIN
    IF (SELECT count(column_name) from information_schema.columns where table_name = 'message_exchange' and column_name = 'account_id' and data_type != 'integer') THEN
        ALTER TABLE message_exchange ADD COLUMN account_id_tmp INTEGER;
        UPDATE message_exchange set account_id_tmp = (select id FROM users where users.user_id = message_exchange.account_id);
        ALTER TABLE message_exchange DROP COLUMN IF EXISTS account_id;
        ALTER TABLE message_exchange RENAME COLUMN account_id_tmp TO account_id;
    END IF;
END$$;


/* RE CREATE VIEWS */
CREATE OR REPLACE VIEW res_view_letterbox AS
SELECT r.res_id,
       r.type_id,
       r.policy_id,
       r.cycle_id,
       d.description AS type_label,
       d.doctypes_first_level_id,
       dfl.doctypes_first_level_label,
       dfl.css_style AS doctype_first_level_style,
       d.doctypes_second_level_id,
       dsl.doctypes_second_level_label,
       dsl.css_style AS doctype_second_level_style,
       r.format,
       r.typist,
       r.creation_date,
       r.modification_date,
       r.docserver_id,
       r.path,
       r.filename,
       r.fingerprint,
       r.filesize,
       r.status,
       r.work_batch,
       r.doc_date,
       r.external_id,
       r.departure_date,
       r.opinion_limit_date,
       r.barcode,
       r.initiator,
       r.destination,
       r.dest_user,
       r.confidentiality,
       r.category_id,
       r.alt_identifier,
       r.admission_date,
       r.process_limit_date,
       r.closing_date,
       r.alarm1_date,
       r.alarm2_date,
       r.flag_alarm1,
       r.flag_alarm2,
       r.subject,
       r.priority,
       r.locker_user_id,
       r.locker_time,
       r.custom_fields,
       en.entity_label,
       en.entity_type AS entitytype
FROM doctypes d,
     doctypes_first_level dfl,
     doctypes_second_level dsl,
     res_letterbox r
    LEFT JOIN entities en ON r.destination::text = en.entity_id::text
WHERE r.type_id = d.type_id AND d.doctypes_first_level_id = dfl.doctypes_first_level_id AND d.doctypes_second_level_id = dsl.doctypes_second_level_id;