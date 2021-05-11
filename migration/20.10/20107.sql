-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 20.10.6 to 20.10.7                              --
--                                                                          --
--                                                                          --
-- *************************************************************************--
--DATABASE_BACKUP|res_mark_as_read

UPDATE res_mark_as_read SET basket_id =  (SELECT basket_id FROM baskets WHERE baskets.id = res_mark_as_read.basket_id::int) WHERE basket_id < '9999999';

UPDATE parameters SET param_value_string = '20.10.7' WHERE id = 'database_version';
