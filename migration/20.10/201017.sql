-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 20.10.16 to 20.10.17                              --
--                                                                          --
--                                                                          --
-- *************************************************************************--
--DATABASE_BACKUP|res_mark_as_read

ALTER TABLE res_letterbox ADD COLUMN  IF NOT exists original_filename varchar;
Update baskets set  basket_id = 'MyBasket' where  basket_id = 'ParafBasket';