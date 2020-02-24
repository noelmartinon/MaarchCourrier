-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 20.03 to 20.03 Alfresco                         --
--                                                                          --
--                                                                          --
-- *************************************************************************--

ALTER TABLE entities DROP COLUMN IF EXISTS external_id;
ALTER TABLE entities ADD COLUMN external_id jsonb DEFAULT '{}';
