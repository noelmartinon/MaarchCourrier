
/****** M2M *******/
ALTER TABLE unit_identifier DROP COLUMN IF EXISTS disposition;
ALTER TABLE unit_identifier ADD disposition text default NULL;

ALTER TABLE sendmail DROP COLUMN IF EXISTS message_exchange_id;
ALTER TABLE sendmail ADD message_exchange_id text default NULL;

ALTER TABLE IF EXISTS seda RENAME TO message_exchange;

ALTER TABLE message_exchange DROP COLUMN IF EXISTS file_path;
ALTER TABLE message_exchange ADD file_path text default NULL;

ALTER TABLE message_exchange DROP COLUMN IF EXISTS res_id_master;
ALTER TABLE message_exchange ADD res_id_master numeric default NULL;

/** ADD NEW COLUMN IS TRANSFERABLE **/
ALTER TABLE contact_addresses DROP COLUMN  IF EXISTS  external_contact_id;
ALTER TABLE contact_addresses ADD COLUMN external_contact_id character varying(128);

DROP SEQUENCE IF EXISTS contact_communication_id_seq CASCADE;
CREATE SEQUENCE contact_communication_id_seq
INCREMENT 1
MINVALUE 1
MAXVALUE 9223372036854775807
START 1
CACHE 1;

DROP TABLE IF EXISTS contact_communication;
CREATE TABLE contact_communication
(
  id bigint NOT NULL DEFAULT nextval('contact_communication_id_seq'::regclass),
  contact_id bigint NOT NULL,
  type character varying(255) NOT NULL,
  value character varying(255) NOT NULL,
  CONSTRAINT contact_communication_pkey PRIMARY KEY (id)
) WITH (OIDS=FALSE);

DROP VIEW IF EXISTS view_contacts;
CREATE OR REPLACE VIEW view_contacts AS 
 SELECT c.contact_id, c.contact_type, c.is_corporate_person, c.society, c.society_short, c.firstname AS contact_firstname
, c.lastname AS contact_lastname, c.title AS contact_title, c.function AS contact_function, c.other_data AS contact_other_data
, c.user_id AS contact_user_id, c.entity_id AS contact_entity_id, c.creation_date, c.update_date, c.enabled AS contact_enabled, ca.id AS ca_id
, ca.contact_purpose_id, ca.departement, ca.firstname, ca.lastname, ca.title, ca.function, ca.occupancy
, ca.address_num, ca.address_street, ca.address_complement, ca.address_town, ca.address_postal_code, ca.address_country
, ca.phone, ca.email, ca.website, ca.salutation_header, ca.salutation_footer, ca.other_data, ca.user_id, ca.entity_id, ca.is_private, ca.enabled, ca.external_contact_id
, cp.label as contact_purpose_label, ct.label as contact_type_label
   FROM contacts_v2 c
   RIGHT JOIN contact_addresses ca ON c.contact_id = ca.contact_id
   LEFT JOIN contact_purposes cp ON ca.contact_purpose_id = cp.id
   LEFT JOIN contact_types ct ON c.contact_type = ct.id;
 
ALTER TABLE sendmail DROP COLUMN IF EXISTS res_version_att_id_list; 
ALTER TABLE sendmail ADD COLUMN res_version_att_id_list character varying(255); 

ALTER TABLE message_exchange DROP COLUMN IF EXISTS docserver_id;
ALTER TABLE message_exchange ADD docserver_id character varying(32) DEFAULT NULL;

ALTER TABLE message_exchange DROP COLUMN IF EXISTS path;
ALTER TABLE message_exchange ADD path character varying(255) DEFAULT NULL;

ALTER TABLE message_exchange DROP COLUMN IF EXISTS filename;
ALTER TABLE message_exchange ADD filename character varying(255) DEFAULT NULL;

ALTER TABLE message_exchange DROP COLUMN IF EXISTS fingerprint;
ALTER TABLE message_exchange ADD fingerprint character varying(255) DEFAULT NULL;

ALTER TABLE message_exchange DROP COLUMN IF EXISTS filesize;
ALTER TABLE message_exchange ADD filesize bigint;

DELETE FROM docservers WHERE docserver_id = 'ARCHIVETRANSFER';
INSERT INTO docservers (docserver_id, docserver_type_id, device_label, is_readonly, enabled, size_limit_number, actual_size_number, path_template, ext_docserver_info, chain_before, chain_after, creation_date, closing_date, coll_id, priority_number, docserver_location_id, adr_priority_number) 
VALUES ('ARCHIVETRANSFER', 'ARCHIVETRANSFER', 'Fast internal disc bay for archive transfer', 'N', 'Y', 50000000000, 1, '/opt/maarch/docservers/archive_transfer/', NULL, NULL, NULL, '2017-01-13 14:47:49.197164', NULL, 'archive_transfer_coll', 10, 'NANTERRE', 2);

DELETE FROM docserver_types WHERE docserver_type_id = 'ARCHIVETRANSFER';
INSERT INTO docserver_types (docserver_type_id, docserver_type_label, enabled, is_container, container_max_number, is_compressed, compression_mode, is_meta, meta_template, is_logged, log_template, is_signed, fingerprint_mode) 
VALUES ('ARCHIVETRANSFER', 'Archive Transfer', 'Y', 'N', 0, 'N', 'NONE', 'N', 'NONE', 'N', 'NONE', 'Y', 'SHA256');

ALTER TABLE sendmail ALTER COLUMN res_id DROP NOT NULL;
