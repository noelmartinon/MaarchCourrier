-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 20.10.18 to 20.10.19                            --
--                                                                          --
--                                                                          --
-- *************************************************************************--

UPDATE contacts_parameters set searchable = true;
UPDATE contacts_parameters set searchable = false
where  identifier NOT IN ('lastname', 'firstname', 'company');