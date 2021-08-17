-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 20.03.21_TMA1 to 20.03.22_TMA1                              --
--                                                                          --
--                                                                          --
-- *************************************************************************--

/*SGAMI-SO DEBUT*/
CREATE TABLE IF NOT EXISTS procedures (
    procedure_id SERIAL NOT NULL,
    procedure_label CHARACTER VARYING(256),
    CONSTRAINT procedures_key PRIMARY KEY (procedure_id)
);
/*SGAMI-SO FIN*/

DROP TABLE IF EXISTS indexing_models_fields;
CREATE TABLE indexing_models_fields
(
  id SERIAL NOT NULL,
  model_id INTEGER NOT NULL,
  identifier text NOT NULL,
  mandatory BOOLEAN NOT NULL,
  default_value json,
  unit text NOT NULL,
  /*SGAMI-SO DEBUT*/
  allowed_values jsonb,
  /*SGAMI-SO FIN*/
  CONSTRAINT indexing_models_fields_pkey PRIMARY KEY (id)
)
WITH (OIDS=FALSE);

INSERT INTO custom_fields (id, label, type, values) VALUES (2, 'Référence courrier expéditeur', 'string', '[]');
INSERT INTO indexing_models_fields (model_id, identifier, mandatory, default_value, unit) VALUES (2, 'indexingCustomField_2', FALSE, '"Référence courrier expéditeur"', 'mail');

UPDATE parameters SET param_value_string = '20.03.22_TMA1' WHERE id = 'database_version'