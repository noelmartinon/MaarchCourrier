
/*PERFS ON VIEW*/
DROP VIEW IF EXISTS view_postindexing;
DROP VIEW IF EXISTS res_view_letterbox;
CREATE OR REPLACE VIEW res_view_letterbox AS 
 SELECT r.tablename,
    r.is_multi_docservers,
    r.res_id,
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
    r.relation,
    r.docserver_id,
    r.folders_system_id,
    f.folder_id,
    f.destination AS folder_destination,
    f.is_frozen AS folder_is_frozen,
    r.path,
    r.filename,
    r.fingerprint,
    r.offset_doc,
    r.filesize,
    r.status,
    r.work_batch,
    r.arbatch_id,
    r.arbox_id,
    r.page_count,
    r.is_paper,
    r.doc_date,
    r.scan_date,
    r.scan_user,
    r.scan_location,
    r.scan_wkstation,
    r.scan_batch,
    r.doc_language,
    r.description,
    r.source,
    r.author,
    r.reference_number,
    r.custom_t1 AS doc_custom_t1,
    r.custom_t2 AS doc_custom_t2,
    r.custom_t3 AS doc_custom_t3,
    r.custom_t4 AS doc_custom_t4,
    r.custom_t5 AS doc_custom_t5,
    r.custom_t6 AS doc_custom_t6,
    r.custom_t7 AS doc_custom_t7,
    r.custom_t8 AS doc_custom_t8,
    r.custom_t9 AS doc_custom_t9,
    r.custom_t10 AS doc_custom_t10,
    r.custom_t11 AS doc_custom_t11,
    r.custom_t12 AS doc_custom_t12,
    r.custom_t13 AS doc_custom_t13,
    r.custom_t14 AS doc_custom_t14,
    r.custom_t15 AS doc_custom_t15,
    r.custom_d1 AS doc_custom_d1,
    r.custom_d2 AS doc_custom_d2,
    r.custom_d3 AS doc_custom_d3,
    r.custom_d4 AS doc_custom_d4,
    r.custom_d5 AS doc_custom_d5,
    r.custom_d6 AS doc_custom_d6,
    r.custom_d7 AS doc_custom_d7,
    r.custom_d8 AS doc_custom_d8,
    r.custom_d9 AS doc_custom_d9,
    r.custom_d10 AS doc_custom_d10,
    r.custom_n1 AS doc_custom_n1,
    r.custom_n2 AS doc_custom_n2,
    r.custom_n3 AS doc_custom_n3,
    r.custom_n4 AS doc_custom_n4,
    r.custom_n5 AS doc_custom_n5,
    r.custom_f1 AS doc_custom_f1,
    r.custom_f2 AS doc_custom_f2,
    r.custom_f3 AS doc_custom_f3,
    r.custom_f4 AS doc_custom_f4,
    r.custom_f5 AS doc_custom_f5,
    f.foldertype_id,
    ft.foldertype_label,
    f.custom_t1 AS fold_custom_t1,
    f.custom_t2 AS fold_custom_t2,
    f.custom_t3 AS fold_custom_t3,
    f.custom_t4 AS fold_custom_t4,
    f.custom_t5 AS fold_custom_t5,
    f.custom_t6 AS fold_custom_t6,
    f.custom_t7 AS fold_custom_t7,
    f.custom_t8 AS fold_custom_t8,
    f.custom_t9 AS fold_custom_t9,
    f.custom_t10 AS fold_custom_t10,
    f.custom_t11 AS fold_custom_t11,
    f.custom_t12 AS fold_custom_t12,
    f.custom_t13 AS fold_custom_t13,
    f.custom_t14 AS fold_custom_t14,
    f.custom_t15 AS fold_custom_t15,
    f.custom_d1 AS fold_custom_d1,
    f.custom_d2 AS fold_custom_d2,
    f.custom_d3 AS fold_custom_d3,
    f.custom_d4 AS fold_custom_d4,
    f.custom_d5 AS fold_custom_d5,
    f.custom_d6 AS fold_custom_d6,
    f.custom_d7 AS fold_custom_d7,
    f.custom_d8 AS fold_custom_d8,
    f.custom_d9 AS fold_custom_d9,
    f.custom_d10 AS fold_custom_d10,
    f.custom_n1 AS fold_custom_n1,
    f.custom_n2 AS fold_custom_n2,
    f.custom_n3 AS fold_custom_n3,
    f.custom_n4 AS fold_custom_n4,
    f.custom_n5 AS fold_custom_n5,
    f.custom_f1 AS fold_custom_f1,
    f.custom_f2 AS fold_custom_f2,
    f.custom_f3 AS fold_custom_f3,
    f.custom_f4 AS fold_custom_f4,
    f.custom_f5 AS fold_custom_f5,
    f.is_complete AS fold_complete,
    f.status AS fold_status,
    f.subject AS fold_subject,
    f.parent_id AS fold_parent_id,
    f.folder_level,
    f.folder_name,
    f.creation_date AS fold_creation_date,
    r.initiator,
    r.destination,
    r.dest_user,
    r.confidentiality,
    mlb.category_id,
    mlb.exp_contact_id,
    mlb.exp_user_id,
    mlb.dest_user_id,
    mlb.dest_contact_id,
    mlb.address_id,
    mlb.nature_id,
    mlb.alt_identifier,
    mlb.admission_date,
    mlb.answer_type_bitmask,
    mlb.other_answer_desc,
    mlb.sve_start_date,
    mlb.sve_identifier,
    mlb.process_limit_date,
    mlb.recommendation_limit_date,
    mlb.closing_date,
    mlb.alarm1_date,
    mlb.alarm2_date,
    mlb.flag_notif,
    mlb.flag_alarm1,
    mlb.flag_alarm2,
    mlb.is_multicontacts,
    r.video_user,
    r.video_time,
    r.video_batch,
    r.subject,
    r.identifier,
    r.title,
    r.priority,
    mlb.process_notes,
    r.locker_user_id,
    r.locker_time,
    ca.case_id,
    ca.case_label,
    ca.case_description,
    en.entity_label,
    en.entity_type AS entitytype,
    cont.contact_id,
    cont.firstname AS contact_firstname,
    cont.lastname AS contact_lastname,
    cont.society AS contact_society,
    u.lastname AS user_lastname,
    u.firstname AS user_firstname,
    r.is_frozen AS res_is_frozen
   FROM doctypes d,
    doctypes_first_level dfl,
    doctypes_second_level dsl,
    res_letterbox r
     LEFT JOIN entities en ON r.destination::text = en.entity_id::text
     LEFT JOIN folders f ON r.folders_system_id = f.folders_system_id
     LEFT JOIN cases_res cr ON r.res_id = cr.res_id
     LEFT JOIN mlb_coll_ext mlb ON mlb.res_id = r.res_id
     LEFT JOIN foldertypes ft ON f.foldertype_id = ft.foldertype_id AND f.status::text <> 'DEL'::text
     LEFT JOIN cases ca ON cr.case_id = ca.case_id
     LEFT JOIN contacts_v2 cont ON mlb.exp_contact_id = cont.contact_id OR mlb.dest_contact_id = cont.contact_id
     LEFT JOIN users u ON mlb.exp_user_id::text = u.user_id::text OR mlb.dest_user_id::text = u.user_id::text
  WHERE r.type_id = d.type_id AND d.doctypes_first_level_id = dfl.doctypes_first_level_id AND d.doctypes_second_level_id = dsl.doctypes_second_level_id;

ALTER TABLE baskets DROP COLUMN IF EXISTS color;
ALTER TABLE baskets ADD color character varying(16);
ALTER TABLE entities DROP COLUMN IF EXISTS entity_full_name;
ALTER TABLE entities ADD entity_full_name text;

DROP VIEW IF EXISTS res_view_attachments;

ALTER TABLE res_attachments DROP COLUMN IF EXISTS in_signature_book;
ALTER TABLE res_attachments ADD in_signature_book boolean default false;
ALTER TABLE res_version_attachments DROP COLUMN IF EXISTS in_signature_book;
ALTER TABLE res_version_attachments ADD in_signature_book boolean default false;
ALTER TABLE res_attachments DROP COLUMN IF EXISTS signatory_user_serial_id;
ALTER TABLE res_attachments ADD signatory_user_serial_id int;
ALTER TABLE res_version_attachments DROP COLUMN IF EXISTS signatory_user_serial_id;
ALTER TABLE res_version_attachments ADD signatory_user_serial_id int;
ALTER TABLE listinstance DROP COLUMN IF EXISTS signatory;
ALTER TABLE listinstance ADD signatory boolean default false;
ALTER TABLE listinstance DROP COLUMN IF EXISTS requested_signature;
ALTER TABLE listinstance ADD requested_signature boolean default false;

CREATE VIEW res_view_attachments AS
  SELECT '0' as res_id, res_id as res_id_version, title, subject, description, publisher, contributor, type_id, format, typist,
    creation_date, fulltext_result, ocr_result, author, author_name, identifier, source,
    doc_language, relation, coverage, doc_date, docserver_id, folders_system_id, arbox_id, path,
    filename, offset_doc, logical_adr, fingerprint, filesize, is_paper, page_count,
    scan_date, scan_user, scan_location, scan_wkstation, scan_batch, burn_batch, scan_postmark,
    envelop_id, status, destination, approver, validation_date, effective_date, work_batch, origin, is_ingoing, priority, initiator, dest_user,
    coll_id, dest_contact_id, dest_address_id, updated_by, is_multicontacts, is_multi_docservers, res_id_master, attachment_type, attachment_id_master, in_signature_book, signatory_user_serial_id
  FROM res_version_attachments
  UNION ALL
  SELECT res_id, '0' as res_id_version, title, subject, description, publisher, contributor, type_id, format, typist,
    creation_date, fulltext_result, ocr_result, author, author_name, identifier, source,
    doc_language, relation, coverage, doc_date, docserver_id, folders_system_id, arbox_id, path,
    filename, offset_doc, logical_adr, fingerprint, filesize, is_paper, page_count,
    scan_date, scan_user, scan_location, scan_wkstation, scan_batch, burn_batch, scan_postmark,
    envelop_id, status, destination, approver, validation_date, effective_date, work_batch, origin, is_ingoing, priority, initiator, dest_user,
    coll_id, dest_contact_id, dest_address_id, updated_by, is_multicontacts, is_multi_docservers, res_id_master, attachment_type, '0', in_signature_book, signatory_user_serial_id
  FROM res_attachments;

DROP TABLE IF EXISTS users_baskets;
CREATE TABLE users_baskets
(
  id serial NOT NULL,
  user_serial_id integer NOT NULL,
  basket_id character varying(32) NOT NULL,
  group_id character varying(32) NOT NULL,
  color character varying(16),
  CONSTRAINT users_baskets_pkey PRIMARY KEY (id)
)
WITH (OIDS=FALSE);

UPDATE res_attachments SET in_signature_book = TRUE;
UPDATE res_version_attachments SET in_signature_book = TRUE;

UPDATE listinstance SET signatory = TRUE, requested_signature = TRUE WHERE item_mode = 'sign' and process_date is not null;
UPDATE listinstance SET signatory = FALSE, requested_signature = TRUE WHERE item_mode = 'sign' and process_date is null;

DO $$ BEGIN
  IF (SELECT count(attname) FROM pg_attribute WHERE attrelid = (SELECT oid FROM pg_class WHERE relname = 'users') AND attname = 'id') = 0 THEN
    ALTER TABLE users ADD COLUMN id serial;
    ALTER TABLE users ADD UNIQUE (id);
  END IF;
END$$;

UPDATE parameters SET param_value_int = '1711', param_value_string = '17.11.0' WHERE id = 'database_version';

ALTER TABLE notif_event_stack ALTER COLUMN record_id TYPE character varying(128);
