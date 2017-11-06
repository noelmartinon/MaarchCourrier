ALTER TABLE baskets DROP COLUMN IF EXISTS color;
ALTER TABLE baskets ADD color character varying(16);
ALTER TABLE entities DROP COLUMN IF EXISTS entity_full_name;
ALTER TABLE entities ADD entity_full_name text;

ALTER TABLE res_attachments DROP COLUMN IF EXISTS in_signature_book;
ALTER TABLE res_attachments ADD in_signature_book boolean default false;
ALTER TABLE res_version_attachments DROP COLUMN IF EXISTS in_signature_book;
ALTER TABLE res_version_attachments ADD in_signature_book boolean default false;

DROP VIEW IF EXISTS res_view_attachments;

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

DO $$ BEGIN
  IF (SELECT count(attname) FROM pg_attribute WHERE attrelid = (SELECT oid FROM pg_class WHERE relname = 'users') AND attname = 'id') = 0 THEN
    ALTER TABLE users ADD COLUMN id serial;
    ALTER TABLE users ADD UNIQUE (id);
  END IF;
END$$;
