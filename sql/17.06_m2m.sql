
/****** M2M *******/
ALTER TABLE unit_identifier DROP COLUMN IF EXISTS disposition;
ALTER TABLE unit_identifier ADD disposition text default NULL;

ALTER TABLE sendmail DROP COLUMN IF EXISTS message_exchange_id;
ALTER TABLE sendmail ADD message_exchange_id text default NULL;

ALTER TABLE seda RENAME TO message_exchange;

ALTER TABLE message_exchange DROP COLUMN IF EXISTS file_path;
ALTER TABLE message_exchange ADD file_path text default NULL;

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
