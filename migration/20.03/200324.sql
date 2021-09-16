-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 20.03.23 to 20.03.24                            --
--                                                                          --
--                                                                          --
-- *************************************************************************--


UPDATE parameters SET param_value_string = '20.03.24_TMA1' WHERE id = 'database_version';

actived character varying(1) DEFAULT 'N',

