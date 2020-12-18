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
