-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 20.10.3 to 20.10.4                              --
--                                                                          --
--                                                                          --
-- *************************************************************************--

ALTER TABLE listinstance_history_details DROP COLUMN IF EXISTS requested_signature;
ALTER TABLE listinstance_history_details ADD COLUMN requested_signature boolean default false;
ALTER TABLE listinstance_history_details DROP COLUMN IF EXISTS signatory;
ALTER TABLE listinstance_history_details ADD COLUMN signatory BOOLEAN DEFAULT FALSE;

UPDATE parameters SET param_value_string = '20.10.4' WHERE id = 'database_version';
