-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 20.10.17 to 20.10.19                            --
--                                                                          --
--                                                                          --
-- *************************************************************************--

UPDATE entities SET short_label = entity_id;

UPDATE parameters SET param_value_string = '20.10.19' WHERE id = 'database_version';
