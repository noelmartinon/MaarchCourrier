-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 20.03.12 to 20.03.13                              --
--                                                                          --
--                                                                          --
-- *************************************************************************--
--DATABASE_BACKUP|parameters

DELETE FROM parameters WHERE id = 'keepDiffusionRoleInOutgoingIndexation';
INSERT INTO parameters (id, param_value_int) VALUES ('keepDiffusionRoleInOutgoingIndexation', 1);

UPDATE parameters SET param_value_string = '20.03.13' WHERE id = 'database_version';
