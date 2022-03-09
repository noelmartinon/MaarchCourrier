-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 20.10.25_TMA1 to 20.10.26_TMA1                  --
--                                                                          --
--                                                                          --
-- *************************************************************************--

ALTER TABLE indexing_models_fields ADD column IF NOT EXISTS allowed_values jsonb;