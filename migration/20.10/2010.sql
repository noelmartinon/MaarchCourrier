-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 20.03 to 20.10                                  --
--                                                                          --
--                                                                          --
-- *************************************************************************--
UPDATE parameters SET param_value_string = '20.10' WHERE id = 'database_version';

DROP VIEW IF EXISTS res_view_letterbox;

/* REPORTS */
DROP TABLE IF EXISTS usergroups_reports;
DELETE FROM usergroups_services WHERE service_id IN ('reports', 'admin_reports');

/* USERS */
ALTER TABLE users DROP COLUMN IF EXISTS refresh_token;
ALTER TABLE users ADD COLUMN refresh_token jsonb NOT NULL DEFAULT '[]';

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
