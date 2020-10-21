-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 20.03.13 to 20.03.14                              --
--                                                                          --
--                                                                          --
-- *************************************************************************--
--DATABASE_BACKUP|contacts_parameters

DELETE FROM contacts_parameters WHERE identifier = 'notes';
INSERT INTO contacts_parameters (identifier, mandatory, filling, searchable, displayable) VALUES ('notes', false, false, false, false);

UPDATE parameters SET param_value_string = '20.03.14' WHERE id = 'database_version';
