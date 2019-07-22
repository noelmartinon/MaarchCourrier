-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 1.6 to 17.06          --
--                                                                          --
--                                                                          --
-- *************************************************************************--

--perfs on res_view_letterbox
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

CREATE OR REPLACE FUNCTION order_alphanum(text) RETURNS text AS $$
  SELECT regexp_replace(regexp_replace(regexp_replace(regexp_replace($1,
    E'(^|\\D)(\\d{1,3}($|\\D))', E'\\1000\\2', 'g'),
      E'(^|\\D)(\\d{4,6}($|\\D))', E'\\1000\\2', 'g'),
        E'(^|\\D)(\\d{7}($|\\D))', E'\\100\\2', 'g'),
          E'(^|\\D)(\\d{8}($|\\D))', E'\\10\\2', 'g');
$$ LANGUAGE SQL;




/* MIGRATION NOUVEL STRUCT MOTS CLES*/
DROP SEQUENCE IF EXISTS tag_id_seq CASCADE;
CREATE SEQUENCE tag_id_seq
  INCREMENT 1
  MINVALUE 1
  MAXVALUE 9223372036854775807
  START 7
  CACHE 1;

ALTER TABLE tags DROP COLUMN IF EXISTS tag_id;
ALTER TABLE tags ADD tag_id bigint NOT NULL DEFAULT nextval('tag_id_seq'::regclass);

ALTER TABLE tags DROP COLUMN IF EXISTS entity_id_owner;
ALTER TABLE tags ADD entity_id_owner character varying(32);

DROP SEQUENCE IF EXISTS tmp_tag_id_seq;
CREATE SEQUENCE tmp_tag_id_seq
  INCREMENT 1
  MINVALUE 1
  MAXVALUE 9223372036854775807
  START 7
  CACHE 1;

DROP TABLE IF EXISTS tmp_tags;
CREATE TABLE tmp_tags
(
  tag_id bigint NOT NULL DEFAULT nextval('tmp_tag_id_seq'::regclass),
  tag_label character varying(255) NOT NULL
)
WITH (
  OIDS=FALSE
);

INSERT INTO tmp_tags (tag_label)
SELECT distinct(lower(tag_label)) from tags;

DROP TABLE IF EXISTS tag_res;
CREATE TABLE tag_res
(
  res_id bigint NOT NULL,
  tag_id bigint NOT NULL,
  CONSTRAINT tag_res_pkey PRIMARY KEY (res_id,tag_id)
)
WITH (
  OIDS=FALSE
);

DO $$ 
    BEGIN
        BEGIN
            ALTER TABLE tags ADD res_id bigint;
        EXCEPTION
            WHEN duplicate_column THEN RAISE NOTICE 'column res_id already exists in tags. skipping...';
        END;
    END;
$$;
INSERT INTO tag_res (res_id,tag_id)
SELECT tags.res_id, tmp_tags.tag_id FROM tags, tmp_tags WHERE tmp_tags.tag_label = lower(tags.tag_label) AND tags.res_id IS NOT NULL;

TRUNCATE TABLE tags;

ALTER TABLE tags DROP CONSTRAINT IF EXISTS tagsjoin_pkey;
ALTER TABLE tags DROP COLUMN IF EXISTS res_id;

INSERT INTO tags (tag_label, coll_id, tag_id)
SELECT tag_label, 'letterbox_coll', tag_id FROM tmp_tags;


DROP TABLE IF EXISTS tmp_tags;
DROP SEQUENCE IF EXISTS tmp_tag_id_seq;

DROP TABLE IF EXISTS tags_entities;
CREATE TABLE tags_entities
(
  tag_id bigint,
  entity_id character varying(32),
  CONSTRAINT tags_entities_pkey PRIMARY KEY (tag_id,entity_id)
)
WITH (
  OIDS=FALSE
);

-- ALTER TABLE res_letterbox DROP COLUMN IF EXISTS date_current_use;
-- ALTER TABLE res_letterbox ADD COLUMN date_current_use timestamp without time zone;

ALTER TABLE doctypes DROP COLUMN IF EXISTS retention_final_disposition;
ALTER TABLE doctypes ADD COLUMN retention_final_disposition character varying(255) NOT NULL DEFAULT 'destruction';

ALTER TABLE doctypes DROP COLUMN IF EXISTS retention_rule;
ALTER TABLE doctypes ADD COLUMN retention_rule character varying(15) NOT NULL DEFAULT 'compta_3_03';

ALTER TABLE doctypes DROP COLUMN IF EXISTS duration_current_use;
ALTER TABLE doctypes ADD COLUMN duration_current_use integer DEFAULT '12';

ALTER TABLE entities DROP COLUMN IF EXISTS archival_agency;
ALTER TABLE entities ADD COLUMN archival_agency character varying(255) DEFAULT 'org_123456789_Archives';

ALTER TABLE entities DROP COLUMN IF EXISTS archival_agreement;
ALTER TABLE entities ADD COLUMN archival_agreement character varying(255) DEFAULT 'MAARCH_LES_BAINS_ACTES';

UPDATE entities SET business_id = 'org_987654321_Versant';

DELETE FROM docservers where docserver_id = 'FASTHD_ATTACH';
INSERT INTO docservers (docserver_id, docserver_type_id, device_label, is_readonly, enabled, size_limit_number, actual_size_number, path_template, ext_docserver_info, chain_before, chain_after, creation_date, closing_date, coll_id, priority_number, docserver_location_id, adr_priority_number) 
VALUES ('FASTHD_ATTACH', 'FASTHD', 'Fast internal disc bay for attachments', 'N', 'Y', 50000000000, 1, '/opt/maarch/docservers/manual_attachments/', NULL, NULL, NULL, '2011-01-13 14:47:49.197164', NULL, 'attachments_coll', 2, 'NANTERRE', 3);

ALTER TABLE basket_persistent_mode ALTER COLUMN user_id TYPE character varying(128);
ALTER TABLE res_mark_as_read ALTER COLUMN user_id TYPE character varying(128);

-- ************************************************************************* --
--                   CHANGE COLUMNS TYPE FOR CONTACTS_V2                   --
-- ************************************************************************* --
DROP VIEW IF EXISTS view_contacts;

ALTER TABLE contacts_v2 ALTER COLUMN other_data TYPE text;

CREATE VIEW view_contacts AS 
 SELECT c.contact_id, c.contact_type, c.is_corporate_person, c.society, c.society_short, c.firstname AS contact_firstname
, c.lastname AS contact_lastname, c.title AS contact_title, c.function AS contact_function, c.other_data AS contact_other_data
, c.user_id AS contact_user_id, c.entity_id AS contact_entity_id, c.creation_date, c.update_date, c.enabled AS contact_enabled, ca.id AS ca_id
, ca.contact_purpose_id, ca.departement, ca.firstname, ca.lastname, ca.title, ca.function, ca.occupancy
, ca.address_num, ca.address_street, ca.address_complement, ca.address_town, ca.address_postal_code, ca.address_country
, ca.phone, ca.email, ca.website, ca.salutation_header, ca.salutation_footer, ca.other_data, ca.user_id, ca.entity_id, ca.is_private, ca.enabled
, cp.label as contact_purpose_label, ct.label as contact_type_label
   FROM contacts_v2 c
   RIGHT JOIN contact_addresses ca ON c.contact_id = ca.contact_id
   LEFT JOIN contact_purposes cp ON ca.contact_purpose_id = cp.id
   LEFT JOIN contact_types ct ON c.contact_type = ct.id;


--  r.date_current_use
DROP VIEW IF EXISTS view_postindexing;
-- view for letterbox
DROP VIEW IF EXISTS res_view_letterbox;
CREATE VIEW res_view_letterbox AS
  SELECT r.tablename, r.is_multi_docservers, r.res_id, r.type_id, r.policy_id, r.cycle_id,
    d.description AS type_label, d.doctypes_first_level_id,
    dfl.doctypes_first_level_label, dfl.css_style as doctype_first_level_style,
    d.doctypes_second_level_id, dsl.doctypes_second_level_label,
    dsl.css_style as doctype_second_level_style, r.format, r.typist,
    r.creation_date, r.modification_date, r.relation, r.docserver_id, r.folders_system_id,
    f.folder_id, f.destination as folder_destination, f.is_frozen as folder_is_frozen, r.path, r.filename, r.fingerprint, r.offset_doc, r.filesize,
    r.status, r.work_batch, r.arbatch_id, r.arbox_id, r.page_count, r.is_paper,
    r.doc_date, r.scan_date, r.scan_user, r.scan_location, r.scan_wkstation,
    r.scan_batch, r.doc_language, r.description, r.source, r.author, r.reference_number,
    r.custom_t1 AS doc_custom_t1, r.custom_t2 AS doc_custom_t2,
    r.custom_t3 AS doc_custom_t3, r.custom_t4 AS doc_custom_t4,
    r.custom_t5 AS doc_custom_t5, r.custom_t6 AS doc_custom_t6,
    r.custom_t7 AS doc_custom_t7, r.custom_t8 AS doc_custom_t8,
    r.custom_t9 AS doc_custom_t9, r.custom_t10 AS doc_custom_t10,
    r.custom_t11 AS doc_custom_t11, r.custom_t12 AS doc_custom_t12,
    r.custom_t13 AS doc_custom_t13, r.custom_t14 AS doc_custom_t14,
    r.custom_t15 AS doc_custom_t15, r.custom_d1 AS doc_custom_d1,
    r.custom_d2 AS doc_custom_d2, r.custom_d3 AS doc_custom_d3,
    r.custom_d4 AS doc_custom_d4, r.custom_d5 AS doc_custom_d5,
    r.custom_d6 AS doc_custom_d6, r.custom_d7 AS doc_custom_d7,
    r.custom_d8 AS doc_custom_d8, r.custom_d9 AS doc_custom_d9,
    r.custom_d10 AS doc_custom_d10, r.custom_n1 AS doc_custom_n1,
    r.custom_n2 AS doc_custom_n2, r.custom_n3 AS doc_custom_n3,
    r.custom_n4 AS doc_custom_n4, r.custom_n5 AS doc_custom_n5,
    r.custom_f1 AS doc_custom_f1, r.custom_f2 AS doc_custom_f2,
    r.custom_f3 AS doc_custom_f3, r.custom_f4 AS doc_custom_f4,
    r.custom_f5 AS doc_custom_f5, f.foldertype_id, ft.foldertype_label,
    f.custom_t1 AS fold_custom_t1, f.custom_t2 AS fold_custom_t2,
    f.custom_t3 AS fold_custom_t3, f.custom_t4 AS fold_custom_t4,
    f.custom_t5 AS fold_custom_t5, f.custom_t6 AS fold_custom_t6,
    f.custom_t7 AS fold_custom_t7, f.custom_t8 AS fold_custom_t8,
    f.custom_t9 AS fold_custom_t9, f.custom_t10 AS fold_custom_t10,
    f.custom_t11 AS fold_custom_t11, f.custom_t12 AS fold_custom_t12,
    f.custom_t13 AS fold_custom_t13, f.custom_t14 AS fold_custom_t14,
    f.custom_t15 AS fold_custom_t15, f.custom_d1 AS fold_custom_d1,
    f.custom_d2 AS fold_custom_d2, f.custom_d3 AS fold_custom_d3,
    f.custom_d4 AS fold_custom_d4, f.custom_d5 AS fold_custom_d5,
    f.custom_d6 AS fold_custom_d6, f.custom_d7 AS fold_custom_d7,
    f.custom_d8 AS fold_custom_d8, f.custom_d9 AS fold_custom_d9,
    f.custom_d10 AS fold_custom_d10, f.custom_n1 AS fold_custom_n1,
    f.custom_n2 AS fold_custom_n2, f.custom_n3 AS fold_custom_n3,
    f.custom_n4 AS fold_custom_n4, f.custom_n5 AS fold_custom_n5,
    f.custom_f1 AS fold_custom_f1, f.custom_f2 AS fold_custom_f2,
    f.custom_f3 AS fold_custom_f3, f.custom_f4 AS fold_custom_f4,
    f.custom_f5 AS fold_custom_f5, f.is_complete AS fold_complete,
    f.status AS fold_status, f.subject AS fold_subject,
    f.parent_id AS fold_parent_id, f.folder_level, f.folder_name,
    f.creation_date AS fold_creation_date, r.initiator, r.destination,
    r.dest_user, r.confidentiality, mlb.category_id, mlb.exp_contact_id, mlb.exp_user_id,
    mlb.dest_user_id, mlb.dest_contact_id, mlb.address_id, mlb.nature_id, mlb.alt_identifier,
    mlb.admission_date, mlb.answer_type_bitmask, mlb.other_answer_desc,
    mlb.sve_start_date, mlb.sve_identifier,
    mlb.process_limit_date, mlb.recommendation_limit_date, mlb.closing_date, mlb.alarm1_date, mlb.alarm2_date,
    mlb.flag_notif, mlb.flag_alarm1, mlb.flag_alarm2, mlb.is_multicontacts, r.video_user, r.video_time,
    r.video_batch, r.subject, r.identifier, r.title, r.priority, mlb.process_notes,
    r.locker_user_id, r.locker_time,
    ca.case_id, ca.case_label, ca.case_description, en.entity_label, en.entity_type AS entityType,
    cont.contact_id AS contact_id,
    cont.firstname AS contact_firstname, cont.lastname AS contact_lastname,
    cont.society AS contact_society, u.lastname AS user_lastname,
    u.firstname AS user_firstname, list.item_id AS dest_user_from_listinstance, list.viewed,
    r.is_frozen as res_is_frozen, COALESCE(att.count_attachment, 0::bigint) AS count_attachment
  FROM doctypes d, doctypes_first_level dfl, doctypes_second_level dsl,
    (((((((((((ar_batch a RIGHT JOIN res_letterbox r ON ((r.arbatch_id = a.arbatch_id)))
  LEFT JOIN (SELECT res_attachments.res_id_master, count(res_attachments.res_id_master) AS count_attachment
      FROM res_attachments WHERE res_attachments.status <> 'DEL' GROUP BY res_attachments.res_id_master) att ON (r.res_id = att.res_id_master))
  LEFT JOIN entities en ON (((r.destination)::text = (en.entity_id)::text)))
  LEFT JOIN folders f ON ((r.folders_system_id = f.folders_system_id)))
  LEFT JOIN cases_res cr ON ((r.res_id = cr.res_id)))
  LEFT JOIN mlb_coll_ext mlb ON ((mlb.res_id = r.res_id)))
  LEFT JOIN foldertypes ft ON (((f.foldertype_id = ft.foldertype_id)
      AND ((f.status)::text <> 'DEL'::text))))
  LEFT JOIN cases ca ON ((cr.case_id = ca.case_id)))
  LEFT JOIN contacts_v2 cont ON (((mlb.exp_contact_id = cont.contact_id)
      OR (mlb.dest_contact_id = cont.contact_id))))
  LEFT JOIN users u ON ((((mlb.exp_user_id)::text = (u.user_id)::text)
      OR ((mlb.dest_user_id)::text = (u.user_id)::text))))
  LEFT JOIN listinstance list ON (((r.res_id = list.res_id)
      AND ((list.item_mode)::text = 'dest'::text))))
  WHERE (((r.type_id = d.type_id) AND
  (d.doctypes_first_level_id = dfl.doctypes_first_level_id))
  AND (d.doctypes_second_level_id = dsl.doctypes_second_level_id));

CREATE VIEW view_postindexing AS
  SELECT res_view_letterbox.video_user, (users.firstname::text || ' '::text) || users.lastname::text AS user_name, res_view_letterbox.video_batch, res_view_letterbox.video_time, count(res_view_letterbox.res_id) AS count_documents, res_view_letterbox.folders_system_id, (folders.folder_id::text || ' / '::text) || folders.folder_name::text AS folder_full_label, folders.video_status
  FROM res_view_letterbox
    LEFT JOIN users ON res_view_letterbox.video_user::text = users.user_id::text
    LEFT JOIN folders ON folders.folders_system_id = res_view_letterbox.folders_system_id
  WHERE res_view_letterbox.video_batch IS NOT NULL
  GROUP BY res_view_letterbox.video_user, (users.firstname::text || ' '::text) || users.lastname::text, res_view_letterbox.video_batch, res_view_letterbox.video_time, res_view_letterbox.folders_system_id, (folders.folder_id::text || ' / '::text) || folders.folder_name::text, folders.video_status;

-- EXPORT SEDA
DROP TABLE IF EXISTS seda;
CREATE TABLE seda
(
  "message_id" text NOT NULL,
  "schema" text,
  "type" text NOT NULL,
  "status" text NOT NULL,
  
  "date" timestamp NOT NULL,
  "reference" text NOT NULL,
  
  "account_id" text,
  "sender_org_identifier" text NOT NULL,
  "sender_org_name" text,
  "recipient_org_identifier" text NOT NULL,
  "recipient_org_name" text,

  "archival_agreement_reference" text,
  "reply_code" text,
  "operation_date" timestamp,
  "reception_date" timestamp,
  
  "related_reference" text,
  "request_reference" text,
  "reply_reference" text,
  "derogation" boolean,
  
  "data_object_count" integer,
  "size" numeric,
  
  "data" text,
  
  "active" boolean,
  "archived" boolean,

  PRIMARY KEY ("message_id")
)
WITH (
  OIDS=FALSE
);

DROP TABLE IF EXISTS unit_identifier;
CREATE TABLE unit_identifier
(
  "message_id" text NOT NULL,
  "tablename" text NOT NULL,
  "res_id" text NOT NULL
);

/*************DIS UPDATE***************/
DROP SEQUENCE IF EXISTS allowed_ip_id_seq CASCADE;
CREATE SEQUENCE allowed_ip_id_seq
  INCREMENT 1
  MINVALUE 1
  MAXVALUE 9223372036854775807
  START 1
  CACHE 1;

DROP TABLE IF EXISTS allowed_ip;
CREATE TABLE allowed_ip
(
  id integer NOT NULL DEFAULT nextval('allowed_ip_id_seq'::regclass),
  ip character varying(50) NOT NULL,
  CONSTRAINT allowed_ip_pkey PRIMARY KEY (id)
)
WITH (OIDS=FALSE);

DROP SEQUENCE IF EXISTS user_signatures_seq CASCADE;
CREATE SEQUENCE user_signatures_seq
  INCREMENT 1
  MINVALUE 1
  MAXVALUE 9223372036854775807
  START 1
  CACHE 1;

DROP TABLE IF EXISTS user_signatures;
CREATE TABLE user_signatures
(
  id bigint NOT NULL DEFAULT nextval('user_signatures_seq'::regclass),
  user_id character varying(128) NOT NULL,
  signature_label character varying(255) DEFAULT NULL::character varying,
  signature_path character varying(255) DEFAULT NULL::character varying,
  signature_file_name character varying(255) DEFAULT NULL::character varying,
  fingerprint character varying(255) DEFAULT NULL::character varying,
  CONSTRAINT user_signatures_pkey PRIMARY KEY (id)
)
WITH (OIDS=FALSE);

ALTER TABLE users DROP COLUMN IF EXISTS ra_code;
ALTER TABLE users ADD ra_code character varying(255);
ALTER TABLE users DROP COLUMN IF EXISTS ra_expiration_date;
ALTER TABLE users ADD ra_expiration_date timestamp without time zone;

/** Add new service for group which have view_doc_history service **/
DELETE FROM usergroups_services where service_id = 'view_full_history';
INSERT INTO usergroups_services SELECT group_id, 'view_full_history' FROM usergroups_services WHERE service_id = 'view_doc_history';

/** Add new service by default for view graphic in report **/
DELETE FROM usergroups_services where service_id = 'graphics_reports';
INSERT INTO usergroups_services SELECT group_id, 'graphics_reports' FROM usergroups;

/** Migrate signatures to the new table **/
TRUNCATE TABLE user_signatures;
INSERT INTO user_signatures (user_id, signature_label, signature_path, signature_file_name) SELECT user_id, '', signature_path, signature_file_name FROM users WHERE signature_path is not null and signature_file_name is not null;

UPDATE parameters SET param_value_int = '1706', param_value_string = '17.06.19' WHERE id = 'database_version';

/** ADD NEW COLUMN FOR ORDER RES IN BASKETS **/
ALTER TABLE baskets DROP COLUMN IF EXISTS basket_res_order;
ALTER TABLE baskets ADD COLUMN basket_res_order character varying(255);

/** DELETES OLD TABLES **/
DROP TABLE IF EXISTS adr_business;
DROP TABLE IF EXISTS adr_log;
DROP TABLE IF EXISTS adr_rm;
DROP TABLE IF EXISTS ar_boxes;
DROP TABLE IF EXISTS ar_containers;
DROP TABLE IF EXISTS ar_container_types;
DROP TABLE IF EXISTS ar_deposits;
DROP TABLE IF EXISTS ar_header;
DROP TABLE IF EXISTS ar_natures;
DROP TABLE IF EXISTS ar_positions;
DROP TABLE IF EXISTS ar_sites;
DROP TABLE IF EXISTS ext_docserver;
DROP TABLE IF EXISTS folders_out;
DROP TABLE IF EXISTS fulltext;
DROP TABLE IF EXISTS groupsecurity;
DROP TABLE IF EXISTS invoice_types;
DROP VIEW  IF EXISTS res_view_log;
DROP TABLE IF EXISTS res_log;
DROP TABLE IF EXISTS resgroup_content;
DROP TABLE IF EXISTS resgroups;

DROP TABLE IF EXISTS rm_access_restriction_rules CASCADE;
DROP VIEW  IF EXISTS rm_documents_view;
DROP TABLE IF EXISTS rm_addresses CASCADE;
DROP TABLE IF EXISTS rm_agreements CASCADE;
DROP TABLE IF EXISTS rm_appraisal_rules CASCADE;
DROP TABLE IF EXISTS rm_comments CASCADE; 
DROP VIEW  IF EXISTS rm_ios_view;
DROP TABLE IF EXISTS rm_contacts CASCADE;
DROP TABLE IF EXISTS rm_content_descriptions CASCADE;
DROP TABLE IF EXISTS rm_custodial_history CASCADE;
DROP TABLE IF EXISTS rm_documents CASCADE;
DROP TABLE IF EXISTS rm_entities CASCADE;
DROP VIEW  IF EXISTS rm_ref_organizations CASCADE;
DROP TABLE IF EXISTS rm_io_archives_relations CASCADE;
DROP TABLE IF EXISTS rm_ios CASCADE;
DROP TABLE IF EXISTS rm_items CASCADE;
DROP TABLE IF EXISTS rm_keywords CASCADE;
DROP TABLE IF EXISTS rm_organizations CASCADE;
DROP TABLE IF EXISTS rm_schedule CASCADE;

DROP TABLE IF EXISTS rp_history;

DROP VIEW IF EXISTS res_view_apa;
DROP VIEW IF EXISTS rm_ref_addresses CASCADE;
DROP VIEW IF EXISTS rm_ref_contacts;
