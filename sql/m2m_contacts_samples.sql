DELETE FROM usergroups WHERE group_id = 'MAARCHTOGEC';
INSERT INTO usergroups (group_id,group_desc,enabled) VALUES ('MAARCHTOGEC', 'Envoi dématérialisé','Y');
DELETE FROM usergroups_services WHERE group_id = 'MAARCHTOGEC';
INSERT INTO usergroups_services (group_id, service_id) VALUES ('MAARCHTOGEC', 'manage_numeric_package');

DELETE FROM security WHERE group_id = 'MAARCHTOGEC';
INSERT INTO security (group_id, coll_id, where_clause, maarch_comment) VALUES ('MAARCHTOGEC', 'letterbox_coll', '1=0', 'Aucun courrier');

DELETE FROM users WHERE user_id = 'cchaplin';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, status, mode) VALUES ('cchaplin', '$2y$10$C.QSslBKD3yNMfRPuZfcaubFwPKiCkqqOUyAdOr5FSGKPaePwuEjG', 'Jean', 'WEBSERVICE', 'info@maarch.org', 'Y', 'OK', 'rest');
DELETE FROM usergroup_content WHERE user_id = 'cchaplin';
INSERT INTO usergroup_content (user_id, group_id, role) VALUES ('cchaplin', 'MAARCHTOGEC', '');

DELETE FROM contacts_v2 where contact_id >= 1000000;
DELETE FROM contact_addresses where id >= 1000000;
DELETE FROM contact_communication where contact_id >= 1000000;
-- INSTANCE A
INSERT INTO contacts_v2 (contact_id, contact_type, is_corporate_person, is_external_contact, society, user_id, entity_id, creation_date) VALUES 
(1000000, 102, 'Y', 'Y', 'Custom 1', 'cchaplin', 'COU', '2019-03-28 21:43:54.97424');
INSERT INTO contact_addresses (id, contact_id, contact_purpose_id, user_id, entity_id, external_id) 
VALUES (1000000, 1000000, 1, 'cchaplin', 'COU', '{"m2m": "org_custom_1"}');
INSERT INTO contact_communication (contact_id, type, value) 
VALUES (1000000, 'url', 'http://cchaplin:maarch@127.0.0.1/MaarchCourrier/cs_custom_1/');
-- INSTANCE B
INSERT INTO contacts_v2 (contact_id, contact_type, is_corporate_person, is_external_contact, society, user_id, entity_id, creation_date) VALUES 
(1000001, 102, 'Y', 'Y', 'Custom 2', 'cchaplin', 'COU', '2019-03-28 21:43:54.97424');
INSERT INTO contact_addresses (id, contact_id, contact_purpose_id, user_id, entity_id, external_id) 
VALUES (1000001, 1000001, 1, 'cchaplin', 'COU', '{"m2m": "org_custom_2"}');
INSERT INTO contact_communication (contact_id, type, value) 
VALUES (1000001, 'url', 'http://cchaplin:maarch@127.0.0.1/MaarchCourrier/cs_custom_2/');
-- INSTANCE C
INSERT INTO contacts_v2 (contact_id, contact_type, is_corporate_person, is_external_contact, society, user_id, entity_id, creation_date) VALUES 
(1000002, 102, 'Y', 'Y', 'Custom 3', 'cchaplin', 'COU', '2019-03-28 21:43:54.97424');
INSERT INTO contact_addresses (id, contact_id, contact_purpose_id, user_id, entity_id, external_id) 
VALUES (1000002, 1000002, 1, 'cchaplin', 'COU', '{"m2m": "org_custom_2"}');
INSERT INTO contact_communication (contact_id, type, value) 
VALUES (1000002, 'url', 'http://cchaplin:maarch@127.0.0.1/MaarchCourrier/cs_custom_3/');

DO $$
BEGIN
	IF (SELECT current_database() = 'custom_1') THEN
		UPDATE entities set business_id = 'org_custom_1';
	END IF;
END $$;
DO $$
BEGIN
	IF (SELECT current_database() = 'custom_2') THEN
		UPDATE entities set business_id = 'org_custom_2';
	END IF;
END $$;
DO $$
BEGIN
	IF (SELECT current_database() = 'custom_3') THEN
		UPDATE entities set business_id = 'org_custom_3';
	END IF;
END $$;
