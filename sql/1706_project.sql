ALTER TABLE baskets DROP COLUMN IF EXISTS color;
ALTER TABLE baskets ADD color character varying(16);
ALTER TABLE entities DROP COLUMN IF EXISTS entity_full_name;
ALTER TABLE entities ADD entity_full_name text;

ALTER TABLE res_attachments DROP COLUMN IF EXISTS in_signature_book;
ALTER TABLE res_attachments ADD in_signature_book boolean default false;
ALTER TABLE res_version_attachments DROP COLUMN IF EXISTS in_signature_book;
ALTER TABLE res_version_attachments ADD in_signature_book boolean default false;

DROP VIEW IF EXISTS res_view_attachments;
CREATE VIEW res_view_attachments AS
  SELECT '0' as res_id, res_id as res_id_version, title, subject, description, publisher, contributor, type_id, format, typist,
    creation_date, fulltext_result, ocr_result, author, author_name, identifier, source,
    doc_language, relation, coverage, doc_date, docserver_id, folders_system_id, arbox_id, path,
    filename, offset_doc, logical_adr, fingerprint, filesize, is_paper, page_count,
    scan_date, scan_user, scan_location, scan_wkstation, scan_batch, burn_batch, scan_postmark,
    envelop_id, status, destination, approver, validation_date, effective_date, work_batch, origin, is_ingoing, priority, initiator, dest_user,
    coll_id, dest_contact_id, dest_address_id, updated_by, is_multicontacts, is_multi_docservers, res_id_master, attachment_type, attachment_id_master, in_signature_book
  FROM res_version_attachments
  UNION ALL
  SELECT res_id, '0' as res_id_version, title, subject, description, publisher, contributor, type_id, format, typist,
    creation_date, fulltext_result, ocr_result, author, author_name, identifier, source,
    doc_language, relation, coverage, doc_date, docserver_id, folders_system_id, arbox_id, path,
    filename, offset_doc, logical_adr, fingerprint, filesize, is_paper, page_count,
    scan_date, scan_user, scan_location, scan_wkstation, scan_batch, burn_batch, scan_postmark,
    envelop_id, status, destination, approver, validation_date, effective_date, work_batch, origin, is_ingoing, priority, initiator, dest_user,
    coll_id, dest_contact_id, dest_address_id, updated_by, is_multicontacts, is_multi_docservers, res_id_master, attachment_type, '0', in_signature_book
  FROM res_attachments;