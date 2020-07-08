-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 20.03.5 to 20.03.6                              --
--                                                                          --
--                                                                          --
-- *************************************************************************--

DELETE FROM usergroups_services WHERE service_id = 'include_folder_perimeter';

INSERT INTO usergroups_services (group_id, service_id)
SELECT distinct(group_id), 'include_folder_perimeter' FROM usergroups_services;
