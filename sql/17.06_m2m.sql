
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
ALTER TABLE contacts_v2 DROP COLUMN  IF EXISTS  is_external_contact;
ALTER TABLE contacts_v2 ADD COLUMN is_external_contact character(1) DEFAULT 'N';

/** ADD NEW COLUMN IS TRANSFERABLE **/
ALTER TABLE contacts_v2 DROP COLUMN  IF EXISTS  external_contact_id;
ALTER TABLE contacts_v2 ADD COLUMN external_contact_id character varying(128);

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
, c.user_id AS contact_user_id, c.entity_id AS contact_entity_id, c.creation_date, c.update_date, c.enabled AS contact_enabled, c.external_contact_id, ca.id AS ca_id
, ca.contact_purpose_id, ca.departement, ca.firstname, ca.lastname, ca.title, ca.function, ca.occupancy
, ca.address_num, ca.address_street, ca.address_complement, ca.address_town, ca.address_postal_code, ca.address_country
, ca.phone, ca.email, ca.website, ca.salutation_header, ca.salutation_footer, ca.other_data, ca.user_id, ca.entity_id, ca.is_private, ca.enabled
, cp.label as contact_purpose_label, ct.label as contact_type_label
   FROM contacts_v2 c
   RIGHT JOIN contact_addresses ca ON c.contact_id = ca.contact_id
   LEFT JOIN contact_purposes cp ON ca.contact_purpose_id = cp.id
   LEFT JOIN contact_types ct ON c.contact_type = ct.id;
