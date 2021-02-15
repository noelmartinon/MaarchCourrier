-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 20.10.5 to 20.10.6                              --
--                                                                          --
--                                                                          --
-- *************************************************************************--

DELETE FROM parameters WHERE id = 'workflowEndBySignatory';
INSERT INTO parameters (id, description, param_value_int) VALUES ('workflowEndBySignatory', 'Si activé (1), le dernier utilisateur du circuit de visa doit être Signataire (0 pour désactiver)', 0);

UPDATE parameters SET param_value_string = '20.10.6' WHERE id = 'database_version';
