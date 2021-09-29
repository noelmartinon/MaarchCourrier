-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 20.03.22_TMA4 to 20.03.23_TMA4                  --
--                                                                          --
--                                                                          --
-- *************************************************************************--
--DATABASE_BACKUP|contacts_parameters

ALTER TABLE res_letterbox ALTER COLUMN type_id DROP NOT NULL;

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
FROM res_letterbox r
         LEFT JOIN doctypes d ON r.type_id = d.type_id
         LEFT JOIN doctypes_first_level dfl ON d.doctypes_first_level_id = dfl.doctypes_first_level_id
         LEFT JOIN doctypes_second_level dsl ON d.doctypes_second_level_id = dsl.doctypes_second_level_id
         LEFT JOIN entities en ON r.destination::TEXT = en.entity_id::TEXT
;
UPDATE parameters SET param_value_string = '20.03.23_TMA4' WHERE id = 'database_version';
