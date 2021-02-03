-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 20.10 to 21.04                                  --
--                                                                          --
--                                                                          --
-- *************************************************************************--
UPDATE parameters SET param_value_string = '21.04.1' WHERE id = 'database_version';

DROP TABLE IF EXISTS attachment_types;
CREATE TABLE attachment_types
(
    id SERIAL NOT NULL,
    type_id text NOT NULL,
    label text NOT NULL,
    visible BOOLEAN NOT NULL,
    email_link BOOLEAN NOT NULL,
    signable BOOLEAN NOT NULL,
    icon text,
    chrono BOOLEAN NOT NULL,
    version_enabled BOOLEAN NOT NULL,
    new_version_default BOOLEAN NOT NULL,
    CONSTRAINT attachment_types_pkey PRIMARY KEY (id),
    CONSTRAINT attachment_types_unique_key UNIQUE (type_id)
)
WITH (OIDS=FALSE);

UPDATE history_batch SET total_errors = 0 WHERE total_errors IS NULL;

ALTER TABLE listinstance_history_details DROP COLUMN IF EXISTS requested_signature;
ALTER TABLE listinstance_history_details ADD COLUMN requested_signature boolean default false;
ALTER TABLE listinstance_history_details DROP COLUMN IF EXISTS signatory;
ALTER TABLE listinstance_history_details ADD COLUMN signatory BOOLEAN DEFAULT FALSE;

DO $$ BEGIN
    IF (SELECT count(column_name) from information_schema.columns where table_name = 'entities' and column_name = 'adrs_1') THEN
        ALTER TABLE entities RENAME COLUMN adrs_1 TO address_number;
        ALTER TABLE entities RENAME COLUMN adrs_2 TO address_street;
        ALTER TABLE entities RENAME COLUMN adrs_3 TO address_additional1;
        ALTER TABLE entities ADD COLUMN address_additional2 CHARACTER VARYING(256);
        ALTER TABLE entities RENAME COLUMN zipcode TO address_postcode;
        ALTER TABLE entities RENAME COLUMN city TO address_town;
        ALTER TABLE entities RENAME COLUMN country TO address_country;
    END IF;
END$$;

DROP TABLE IF EXISTS tiles;
CREATE TABLE tiles
(
    id SERIAL NOT NULL,
    user_id INTEGER NOT NULL,
    type text NOT NULL,
    view text NOT NULL,
    position INTEGER NOT NULL,
    color text,
    parameters jsonb DEFAULT '{}' NOT NULL,
    CONSTRAINT tiles_pkey PRIMARY KEY (id)
)
WITH (OIDS=FALSE);
