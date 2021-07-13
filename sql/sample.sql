
TRUNCATE TABLE res_letterbox;
ALTER SEQUENCE res_id_mlb_seq restart WITH 1;
-- to sign documents
INSERT INTO res_letterbox (res_id, title, subject, description, type_id, format, typist, creation_date, modification_date, converter_result, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, work_batch, origin, priority, policy_id, cycle_id, is_multi_docservers, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_t12, custom_t13, custom_t14, custom_t15, reference_number, tablename, initiator, dest_user) 
VALUES (1, NULL, 'Demande de dérogation carte scolaire', '', 305, 'pdf', 'bblier', NOW(), NOW(), NULL, NULL, NULL, NULL, NULL, CURRENT_DATE, 'FASTHD_MAN', NULL, 'tests#', 'demande_derogation.pdf', '', '0', 24942, 'ATT_MP', 'PJS', NULL, NULL, NULL, 'poiuytre1357nbvc', NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'res_letterbox', 'COU', 'bbain');
INSERT INTO res_letterbox (res_id, title, subject, description, type_id, format, typist, creation_date, modification_date, converter_result, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, work_batch, origin, priority, policy_id, cycle_id, is_multi_docservers, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_t12, custom_t13, custom_t14, custom_t15, reference_number, tablename, initiator, dest_user)
VALUES (2, NULL, 'Demande de travaux route 66', '', 918, 'pdf', 'bblier', NOW(), NOW(), NULL, NULL, NULL, NULL, NULL, CURRENT_DATE, 'FASTHD_MAN', NULL, 'tests#', 'sva_route_66.pdf', '', '0', 24877, 'ATT_MP', 'PTE', NULL, NULL, NULL, 'poiuytre1357nbvc', NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'res_letterbox', 'COU', 'ccharles');
INSERT INTO res_letterbox (res_id, title, subject, description, type_id, format, typist, creation_date, modification_date, converter_result, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, work_batch, origin, priority, policy_id, cycle_id, is_multi_docservers, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_t12, custom_t13, custom_t14, custom_t15, reference_number, tablename, initiator, dest_user)
VALUES (3, NULL, 'Plainte voisin chien bruyant', '', 503, 'pdf', 'bblier', NOW(), NOW(), NULL, NULL, NULL, NULL, NULL, CURRENT_DATE, 'FASTHD_MAN', NULL, 'tests#', 'svr_route_chien_bruyant.pdf', '', '0', 24877, 'ATT_MP', 'DGS', NULL, NULL, NULL, 'poiuytre1357nbvc', NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'res_letterbox', 'COU', 'rrenaud');
INSERT INTO res_letterbox (res_id, title, subject, description, type_id, format, typist, creation_date, modification_date, converter_result, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, work_batch, origin, priority, policy_id, cycle_id, is_multi_docservers, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_t12, custom_t13, custom_t14, custom_t15, reference_number, tablename, initiator, dest_user)
VALUES (4, NULL, 'Invitation pour échanges journées des sports', '', 110, 'pdf', 'bbain', NOW(), NOW(), NULL, NULL, NULL, 'with_empty_file', NULL, CURRENT_DATE, 'FASTHD_MAN', NULL, 'tests#', 'empty.pdf', '', '0', 111108, 'ATT_MP', 'PJS', NULL, NULL, NULL, 'poiuytre1357nbvc', NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'res_letterbox', 'PJS', 'bbain');
INSERT INTO res_letterbox (res_id, title, subject, description, type_id, format, typist, creation_date, modification_date, converter_result, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, work_batch, origin, priority, policy_id, cycle_id, is_multi_docservers, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_t12, custom_t13, custom_t14, custom_t15, reference_number, tablename, initiator, dest_user)
VALUES (5, NULL, 'Demande de place en creche', '', 307, 'pdf', 'bblier', NOW(), NOW(), NULL, NULL, NULL, NULL, NULL, CURRENT_DATE, 'FASTHD_MAN', NULL, 'tests#', 'demande_place_creche.pdf', '', '0', 24877, 'ATT_MP', 'PE', NULL, NULL, NULL, 'poiuytre1357nbvc', NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'res_letterbox', 'COU', 'ssaporta');
INSERT INTO res_letterbox (res_id, title, subject, description, type_id, format, typist, creation_date, modification_date, converter_result, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, work_batch, origin, priority, policy_id, cycle_id, is_multi_docservers, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_t12, custom_t13, custom_t14, custom_t15, reference_number, tablename, initiator, dest_user)
VALUES (6, NULL, 'Relance place en creche', '', 307, 'pdf', 'bblier', NOW(), NOW(), NULL, NULL, NULL, NULL, NULL, CURRENT_DATE, 'FASTHD_MAN', NULL, 'tests#', 'relance_place_creche.pdf', '', '0', 24877, 'ATT_MP', 'PE', NULL, NULL, NULL, 'poiuytre1357nbvc', NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'res_letterbox', 'COU', 'ssaporta');
-- to annotate documents
INSERT INTO res_letterbox (res_id, title, subject, description, type_id, format, typist, creation_date, modification_date, converter_result, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, work_batch, origin, priority, policy_id, cycle_id, is_multi_docservers, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_t12, custom_t13, custom_t14, custom_t15, reference_number, tablename, initiator, dest_user, external_signatory_book_id)
VALUES (7, NULL, 'Pétition pour la survie du square Carré', '', 201, 'pdf', 'bblier', NOW(), NOW(), NULL, NULL, NULL, NULL, NULL, CURRENT_DATE, 'FASTHD_MAN', NULL, 'tests#', 'petition_square_carre.pdf', '', '0', 24877, 'ATT_MP', 'DGS', NULL, NULL, NULL, 'poiuytre1357nbvc', NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'res_letterbox', 'COU', 'rrenaud', 7);
INSERT INTO res_letterbox (res_id, title, subject, description, type_id, format, typist, creation_date, modification_date, converter_result, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, work_batch, origin, priority, policy_id, cycle_id, is_multi_docservers, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_t12, custom_t13, custom_t14, custom_t15, reference_number, tablename, initiator, dest_user, external_signatory_book_id)
VALUES (8, NULL, 'Félicitations élections', '', 205, 'pdf', 'bblier', NOW(), NOW(), NULL, NULL, NULL, NULL, NULL, CURRENT_DATE, 'FASTHD_MAN', NULL, 'tests#', 'felicitations.pdf', '', '0', 24877, 'ATT_MP', 'DGS', NULL, NULL, NULL, 'poiuytre1357nbvc', NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'res_letterbox', 'COU', 'rrenaud', 8);
INSERT INTO res_letterbox (res_id, title, subject, description, type_id, format, typist, creation_date, modification_date, converter_result, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, work_batch, origin, priority, policy_id, cycle_id, is_multi_docservers, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_t12, custom_t13, custom_t14, custom_t15, reference_number, tablename, initiator, dest_user, external_signatory_book_id)
VALUES (9, NULL, 'Demande place creche', '', 307, 'pdf', 'bblier', NOW(), NOW(), NULL, NULL, NULL, NULL, NULL, CURRENT_DATE, 'FASTHD_MAN', NULL, 'tests#', 'formulaire_place_creche.pdf', '', '0', 24877, 'ATT_MP', 'PE', NULL, NULL, NULL, 'poiuytre1357nbvc', NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'res_letterbox', 'COU', 'ssaporta', 9);
INSERT INTO res_letterbox (res_id, title, subject, description, type_id, format, typist, creation_date, modification_date, converter_result, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, work_batch, origin, priority, policy_id, cycle_id, is_multi_docservers, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_t12, custom_t13, custom_t14, custom_t15, reference_number, tablename, initiator, dest_user, external_signatory_book_id)
VALUES (10, NULL, 'Demande subvention jokkolabs', '', 406, 'pdf', 'bblier', NOW(), NOW(), NULL, NULL, NULL, NULL, NULL, CURRENT_DATE, 'FASTHD_MAN', NULL, 'tests#', 'demande_subvention.pdf', '', '0', 24877, 'ATT_MP', 'DGS', NULL, NULL, NULL, 'poiuytre1357nbvc', NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'res_letterbox', 'COU', 'rrenaud', 10);
INSERT INTO res_letterbox (res_id, title, subject, description, type_id, format, typist, creation_date, modification_date, converter_result, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, work_batch, origin, priority, policy_id, cycle_id, is_multi_docservers, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_t12, custom_t13, custom_t14, custom_t15, reference_number, tablename, initiator, dest_user, external_signatory_book_id)
VALUES (11, NULL, 'Facture Maarch', '', 407, 'pdf', 'bblier', NOW(), NOW(), NULL, NULL, NULL, NULL, NULL, CURRENT_DATE, 'FASTHD_MAN', NULL, 'tests#', 'facture.pdf', '', '0', 24877, 'ATT_MP', 'FIN', NULL, NULL, NULL, 'poiuytre1357nbvc', NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'res_letterbox', 'COU', 'sstar', 11);
INSERT INTO res_letterbox (res_id, title, subject, description, type_id, format, typist, creation_date, modification_date, converter_result, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, work_batch, origin, priority, policy_id, cycle_id, is_multi_docservers, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_t12, custom_t13, custom_t14, custom_t15, reference_number, tablename, initiator, dest_user, external_signatory_book_id)
VALUES (12, NULL, 'Demande état civil', '', 602, 'pdf', 'bblier', NOW(), NOW(), NULL, NULL, NULL, NULL, NULL, CURRENT_DATE, 'FASTHD_MAN', NULL, 'tests#', 'etat_civil.pdf', '', '0', 24877, 'ATT_MP', 'DGS', NULL, NULL, NULL, 'poiuytre1357nbvc', NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'res_letterbox', 'COU', 'rrenaud', 12);
INSERT INTO res_letterbox (res_id, title, subject, description, type_id, format, typist, creation_date, modification_date, converter_result, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, work_batch, origin, priority, policy_id, cycle_id, is_multi_docservers, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_t12, custom_t13, custom_t14, custom_t15, reference_number, tablename, initiator, dest_user, external_signatory_book_id)
VALUES (13, NULL, 'Arret maladie vide', '', 701, 'pdf', 'bblier', NOW(), NOW(), NULL, NULL, NULL, NULL, NULL, CURRENT_DATE, 'FASTHD_MAN', NULL, 'tests#', 'arret_maladie.pdf', '', '0', 24877, 'ATT_MP', 'DRH', NULL, NULL, NULL, 'poiuytre1357nbvc', NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'res_letterbox', 'COU', 'ppruvost', 13);
INSERT INTO res_letterbox (res_id, title, subject, description, type_id, format, typist, creation_date, modification_date, converter_result, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, work_batch, origin, priority, policy_id, cycle_id, is_multi_docservers, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_t12, custom_t13, custom_t14, custom_t15, reference_number, tablename, initiator, dest_user, external_signatory_book_id)
VALUES (14, NULL, 'Inscription école', '', 307, 'pdf', 'bblier', NOW(), NOW(), NULL, NULL, NULL, NULL, NULL, CURRENT_DATE, 'FASTHD_MAN', NULL, 'tests#', 'inscription_ecole.pdf', '', '0', 24877, 'ATT_MP', 'PE', NULL, NULL, NULL, 'poiuytre1357nbvc', NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'res_letterbox', 'COU', 'ssaporta', 14);
-- to qualify document
INSERT INTO res_letterbox (res_id, title, subject, description, type_id, format, typist, creation_date, modification_date, converter_result, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, work_batch, origin, priority, policy_id, cycle_id, is_multi_docservers, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_t12, custom_t13, custom_t14, custom_t15, reference_number, tablename, initiator, dest_user)
VALUES (15, NULL, 'Demande intervention à qualifier', '', 505, 'pdf', 'bblier', NOW(), NOW(), NULL, NULL, NULL, NULL, NULL, CURRENT_DATE, 'FASTHD_MAN', NULL, 'tests#', 'demande_intervention.pdf', '', '0', 24877, 'INIT', 'PTE', NULL, NULL, NULL, 'poiuytre1357nbvc', NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'res_letterbox', 'COU', 'ccharles');
-- to validate document
INSERT INTO res_letterbox (res_id, title, subject, description, type_id, format, typist, creation_date, modification_date, converter_result, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, work_batch, origin, priority, policy_id, cycle_id, is_multi_docservers, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_t12, custom_t13, custom_t14, custom_t15, reference_number, tablename, initiator, dest_user)
VALUES (16, NULL, 'Demande intervention à valider', '', 505, 'pdf', 'bblier', NOW(), NOW(), NULL, NULL, NULL, NULL, NULL, CURRENT_DATE, 'FASTHD_MAN', NULL, 'tests#', 'demande_intervention.pdf', '', '0', 24877, 'VAL', 'PTE', NULL, NULL, NULL, 'poiuytre1357nbvc', NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'res_letterbox', 'COU', 'ccharles');
-- to process document ccharles
INSERT INTO res_letterbox (res_id, title, subject, description, type_id, format, typist, creation_date, modification_date, converter_result, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, work_batch, origin, priority, policy_id, cycle_id, is_multi_docservers, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_t12, custom_t13, custom_t14, custom_t15, reference_number, tablename, initiator, dest_user)
VALUES (17, NULL, 'Demande intervention à traiter', '', 505, 'pdf', 'bblier', NOW(), NOW(), NULL, NULL, NULL, NULL, NULL, CURRENT_DATE, 'FASTHD_MAN', NULL, 'tests#', 'demande_intervention.pdf', '', '0', 24877, 'NEW', 'PTE', NULL, NULL, NULL, 'poiuytre1357nbvc', NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'res_letterbox', 'COU', 'ccharles');
-- to process document nnataly
INSERT INTO res_letterbox (res_id, title, subject, description, type_id, format, typist, creation_date, modification_date, converter_result, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, work_batch, origin, priority, policy_id, cycle_id, is_multi_docservers, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_t12, custom_t13, custom_t14, custom_t15, reference_number, tablename, initiator, dest_user)
VALUES (18, NULL, 'Demande intervention à envoyer au parapheur', '', 505, 'pdf', 'bblier', NOW(), NOW(), NULL, NULL, NULL, NULL, NULL, CURRENT_DATE, 'FASTHD_MAN', NULL, 'tests#', 'demande_intervention.pdf', '', '0', 24877, 'NEW', 'PSO', NULL, NULL, NULL, 'poiuytre1357nbvc', NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'res_letterbox', 'COU', 'nnataly');
-- to paraph document ppetit
INSERT INTO res_letterbox (res_id, title, subject, description, type_id, format, typist, creation_date, modification_date, converter_result, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, work_batch, origin, priority, policy_id, cycle_id, is_multi_docservers, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_t12, custom_t13, custom_t14, custom_t15, reference_number, tablename, initiator, dest_user)
VALUES (19, NULL, 'Demande intervention à signer', '', 505, 'pdf', 'bblier', NOW(), NOW(), NULL, NULL, NULL, NULL, NULL, CURRENT_DATE, 'FASTHD_MAN', NULL, 'tests#', 'demande_intervention.pdf', '', '0', 24877, 'ATT_MP', 'PTE', NULL, NULL, NULL, 'poiuytre1357nbvc', NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'res_letterbox', 'COU', 'ccharles');
-- to archive document ggrand
INSERT INTO res_letterbox (res_id, title, subject, description, type_id, format, typist, creation_date, modification_date, converter_result, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, work_batch, origin, priority, policy_id, cycle_id, is_multi_docservers, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_t12, custom_t13, custom_t14, custom_t15, reference_number, tablename, initiator, dest_user)
VALUES (20, NULL, 'Demande intervention à archiver', '', 505, 'pdf', 'bblier', NOW(), NOW(), NULL, NULL, NULL, NULL, NULL, CURRENT_DATE, 'FASTHD_MAN', NULL, 'tests#', 'demande_intervention.pdf', '', '0', 24877, 'END', 'PTE', NULL, NULL, NULL, 'poiuytre1357nbvc', NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'res_letterbox', 'COU', 'ccharles');

Select setval('res_id_mlb_seq', (select max(res_id)+1 from res_letterbox), false);

TRUNCATE TABLE adr_letterbox;
ALTER SEQUENCE adr_letterbox_id_seq restart WITH 1;
-- to sign documents
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (1, 1, 'PDF', 'CONVERT_MLB', 'tests#', 'demande_derogation.pdf', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (2, 2, 'PDF', 'CONVERT_MLB', 'tests#', 'sva_route_66.pdf', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (3, 3, 'PDF', 'CONVERT_MLB', 'tests#', 'svr_route_chien_bruyant.pdf', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (4, 4, 'PDF', 'CONVERT_MLB', 'tests#', 'emtpy.pdf', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (5, 5, 'PDF', 'CONVERT_MLB', 'tests#', 'demande_place_creche.pdf', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (6, 6, 'PDF', 'CONVERT_MLB', 'tests#', 'relance_place_creche.pdf', '0');
-- to annotate documents
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (7, 7, 'PDF', 'CONVERT_MLB', 'tests#', 'petition_square_carre.pdf', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (8, 8, 'PDF', 'CONVERT_MLB', 'tests#', 'felicitations.pdf', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (9, 9, 'PDF', 'CONVERT_MLB', 'tests#', 'formulaire_place_creche.pdf', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (10, 10, 'PDF', 'CONVERT_MLB', 'tests#', 'demande_subvention.pdf', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (11, 11, 'PDF', 'CONVERT_MLB', 'tests#', 'facture.pdf', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (12, 12, 'PDF', 'CONVERT_MLB', 'tests#', 'etat_civil.pdf', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (13, 13, 'PDF', 'CONVERT_MLB', 'tests#', 'arret_maladie.pdf', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (14, 14, 'PDF', 'CONVERT_MLB', 'tests#', 'inscription_ecole.pdf', '0');
-- thumbnails
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (15, 1, 'TNL', 'TNL_MLB', 'tests#', 'demande_derogation.png', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (16, 2, 'TNL', 'TNL_MLB', 'tests#', 'sva_route_66.png', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (17, 3, 'TNL', 'TNL_MLB', 'tests#', 'svr_route_chien_bruyant.png', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (18, 4, 'TNL', 'TNL_MLB', 'tests#', 'invitation.png', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (19, 5, 'TNL', 'TNL_MLB', 'tests#', 'demande_place_creche.png', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (20, 6, 'TNL', 'TNL_MLB', 'tests#', 'relance_place_creche.png', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (21, 7, 'TNL', 'TNL_MLB', 'tests#', 'petition_square_carre.png', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (22, 8, 'TNL', 'TNL_MLB', 'tests#', 'felicitations.png', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (23, 9, 'TNL', 'TNL_MLB', 'tests#', 'formulaire_place_creche.png', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (24, 10, 'TNL', 'TNL_MLB', 'tests#', 'demande_subvention.png', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (25, 11, 'TNL', 'TNL_MLB', 'tests#', 'facture.png', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (26, 12, 'TNL', 'TNL_MLB', 'tests#', 'etat_civil.png', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (27, 13, 'TNL', 'TNL_MLB', 'tests#', 'arret_maladie.png', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (28, 14, 'TNL', 'TNL_MLB', 'tests#', 'inscription_ecole.png', '0');
-- to qualify documents
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (29, 15, 'PDF', 'CONVERT_MLB', 'tests#', 'demande_intervention.pdf', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (30, 15, 'TNL', 'TNL_MLB', 'tests#', 'demande_intervention.png', '0');
-- to validate documents
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (31, 16, 'PDF', 'CONVERT_MLB', 'tests#', 'demande_intervention.pdf', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (32, 16, 'TNL', 'TNL_MLB', 'tests#', 'demande_intervention.png', '0');
-- to process documents ccharles
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (33, 17, 'PDF', 'CONVERT_MLB', 'tests#', 'demande_intervention.pdf', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (34, 17, 'TNL', 'TNL_MLB', 'tests#', 'demande_intervention.png', '0');
-- to process documents nnataly
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (35, 18, 'PDF', 'CONVERT_MLB', 'tests#', 'demande_intervention.pdf', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (36, 18, 'TNL', 'TNL_MLB', 'tests#', 'demande_intervention.png', '0');
-- to paraph documents ppetit
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (37, 19, 'PDF', 'CONVERT_MLB', 'tests#', 'demande_intervention.pdf', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (38, 19, 'TNL', 'TNL_MLB', 'tests#', 'demande_intervention.png', '0');
-- to archive documents ggrand
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (39, 20, 'PDF', 'CONVERT_MLB', 'tests#', 'demande_intervention.pdf', '0');
INSERT INTO adr_letterbox (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (40, 20, 'TNL', 'TNL_MLB', 'tests#', 'demande_intervention.png', '0');

Select setval('adr_letterbox_id_seq', (select max(id)+1 from adr_letterbox), false);

TRUNCATE TABLE res_attachments;
ALTER SEQUENCE res_attachment_res_id_seq restart WITH 1;
INSERT INTO res_attachments (res_id, title, subject, description, type_id, format, typist, creation_date, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, effective_date, work_batch, origin, priority, initiator, dest_user, coll_id, res_id_master, attachment_type, dest_contact_id, dest_address_id, updated_by, is_multicontacts, is_multi_docservers, tnl_path, tnl_filename, fulltext_result, in_signature_book, signatory_user_serial_id, convert_result, convert_attempts, fulltext_attempts, tnl_attempts, tnl_result, external_id)
VALUES (1, 'ar_derogation', NULL, NULL, 0, 'pdf', 'bbain', NOW(), NULL, 'MAARCH/2019D/1', NULL, 1, NULL, 'FASTHD_MAN', NULL, 'tests#', 'ar_derogation.pdf', ' ', '0', 41682, 'FRZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'letterbox_coll', 1, 'response_project', 4, 6, NULL, NULL, 'N', NULL, NULL, NULL, true, NULL, NULL, NULL, NULL, NULL, NULL, '{"signatureBookId": 1}');
INSERT INTO res_attachments (res_id, title, subject, description, type_id, format, typist, creation_date, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, effective_date, work_batch, origin, priority, initiator, dest_user, coll_id, res_id_master, attachment_type, dest_contact_id, dest_address_id, updated_by, is_multicontacts, is_multi_docservers, tnl_path, tnl_filename, fulltext_result, in_signature_book, signatory_user_serial_id, convert_result, convert_attempts, fulltext_attempts, tnl_attempts, tnl_result, external_id)
VALUES (2, 'ar_sva', NULL, NULL, 0, 'pdf', 'ccharles', NOW(), NULL, 'MAARCH/2019D/2', NULL, 1, NULL, 'FASTHD_MAN', NULL, 'tests#', 'ar_sva.pdf', ' ', '0', 44907, 'FRZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'letterbox_coll', 2, 'response_project', 4, 6, NULL, NULL, 'N', NULL, NULL, NULL, true, NULL, NULL, NULL, NULL, NULL, NULL, '{"signatureBookId": 2}');
INSERT INTO res_attachments (res_id, title, subject, description, type_id, format, typist, creation_date, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, effective_date, work_batch, origin, priority, initiator, dest_user, coll_id, res_id_master, attachment_type, dest_contact_id, dest_address_id, updated_by, is_multicontacts, is_multi_docservers, tnl_path, tnl_filename, fulltext_result, in_signature_book, signatory_user_serial_id, convert_result, convert_attempts, fulltext_attempts, tnl_attempts, tnl_result, external_id)
VALUES (3, 'ar_svr', NULL, NULL, 0, 'pdf', 'rrenaud', NOW(), NULL, 'MAARCH/2019D/3', NULL, 1, NULL, 'FASTHD_MAN', NULL, 'tests#', 'ar_svr.pdf', ' ', '0', 44907, 'FRZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'letterbox_coll', 3, 'response_project', 4, 6, NULL, NULL, 'N', NULL, NULL, NULL, true, NULL, NULL, NULL, NULL, NULL, NULL, '{"signatureBookId": 3}');
INSERT INTO res_attachments (res_id, title, subject, description, type_id, format, typist, creation_date, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, effective_date, work_batch, origin, priority, initiator, dest_user, coll_id, res_id_master, attachment_type, dest_contact_id, dest_address_id, updated_by, is_multicontacts, is_multi_docservers, tnl_path, tnl_filename, fulltext_result, in_signature_book, signatory_user_serial_id, convert_result, convert_attempts, fulltext_attempts, tnl_attempts, tnl_result, external_id)
VALUES (4, 'invitation', NULL, NULL, 0, 'pdf', 'bbain', '2019-03-20 17:54:00.954235', NULL, 'MAARCH/2019D/4', NULL, 1, NULL, 'FASTHD_MAN', NULL, 'tests#', 'invitation.pdf', ' ', '0', 47379, 'FRZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'letterbox_coll', 4, 'outgoing_mail', 4, 6, NULL, NULL, 'N', NULL, NULL, NULL, true, NULL, NULL, NULL, NULL, NULL, NULL, '{"signatureBookId": 4}');
INSERT INTO res_attachments (res_id, title, subject, description, type_id, format, typist, creation_date, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, effective_date, work_batch, origin, priority, initiator, dest_user, coll_id, res_id_master, attachment_type, dest_contact_id, dest_address_id, updated_by, is_multicontacts, is_multi_docservers, tnl_path, tnl_filename, fulltext_result, in_signature_book, signatory_user_serial_id, convert_result, convert_attempts, fulltext_attempts, tnl_attempts, tnl_result, external_id)
VALUES (5, 'rep_creche', NULL, NULL, 0, 'pdf', 'ssaporta', NOW(), NULL, 'MAARCH/2019D/5', NULL, 1, NULL, 'FASTHD_MAN', NULL, 'tests#', 'rep_creche.pdf', ' ', '0', 44907, 'FRZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'letterbox_coll', 5, 'response_project', 4, 6, NULL, NULL, 'N', NULL, NULL, NULL, true, NULL, NULL, NULL, NULL, NULL, NULL, '{"signatureBookId": 5}');
INSERT INTO res_attachments (res_id, title, subject, description, type_id, format, typist, creation_date, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, effective_date, work_batch, origin, priority, initiator, dest_user, coll_id, res_id_master, attachment_type, dest_contact_id, dest_address_id, updated_by, is_multicontacts, is_multi_docservers, tnl_path, tnl_filename, fulltext_result, in_signature_book, signatory_user_serial_id, convert_result, convert_attempts, fulltext_attempts, tnl_attempts, tnl_result, external_id)
VALUES (6, 'rep_standard', NULL, NULL, 0, 'pdf', 'ssaporta', NOW(), NULL, 'MAARCH/2019D/6', NULL, 1, NULL, 'FASTHD_MAN', NULL, 'tests#', 'rep_standard.pdf', ' ', '0', 44907, 'FRZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'letterbox_coll', 6, 'response_project', 4, 6, NULL, NULL, 'N', NULL, NULL, NULL, true, NULL, NULL, NULL, NULL, NULL, NULL, '{"signatureBookId": 6}');
-- to process documents nnataly
INSERT INTO res_attachments (res_id, title, subject, description, type_id, format, typist, creation_date, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, effective_date, work_batch, origin, priority, initiator, dest_user, coll_id, res_id_master, attachment_type, dest_contact_id, dest_address_id, updated_by, is_multicontacts, is_multi_docservers, tnl_path, tnl_filename, fulltext_result, in_signature_book, signatory_user_serial_id, convert_result, convert_attempts, fulltext_attempts, tnl_attempts, tnl_result)
VALUES (7, 'rep_standard', NULL, NULL, 0, 'pdf', 'ccharles', NOW(), NULL, 'MAARCH/2019D/7', NULL, 1, NULL, 'FASTHD_MAN', NULL, 'tests#', 'rep_standard_demande_intervention.pdf', ' ', '0', 44907, 'A_TRA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'letterbox_coll', 18, 'response_project', 4, 6, NULL, NULL, 'N', NULL, NULL, NULL, true, NULL, NULL, NULL, NULL, NULL, NULL);
-- to paraph documents ppetit
INSERT INTO res_attachments (res_id, title, subject, description, type_id, format, typist, creation_date, author, identifier, source, relation, doc_date, docserver_id, folders_system_id, path, filename, offset_doc, fingerprint, filesize, status, destination, validation_date, effective_date, work_batch, origin, priority, initiator, dest_user, coll_id, res_id_master, attachment_type, dest_contact_id, dest_address_id, updated_by, is_multicontacts, is_multi_docservers, tnl_path, tnl_filename, fulltext_result, in_signature_book, signatory_user_serial_id, convert_result, convert_attempts, fulltext_attempts, tnl_attempts, tnl_result)
VALUES (8, 'rep_standard', NULL, NULL, 0, 'pdf', 'ccharles', NOW(), NULL, 'MAARCH/2019D/8', NULL, 1, NULL, 'FASTHD_MAN', NULL, 'tests#', 'rep_standard_demande_intervention.pdf', ' ', '0', 44907, 'A_TRA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'letterbox_coll', 19, 'response_project', 4, 6, NULL, NULL, 'N', NULL, NULL, NULL, true, NULL, NULL, NULL, NULL, NULL, NULL);

Select setval('res_attachment_res_id_seq', (select max(res_id)+1 from res_attachments), false);

TRUNCATE TABLE adr_attachments;
ALTER SEQUENCE adr_attachments_id_seq restart WITH 1;
INSERT INTO adr_attachments (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (1, 1, 'PDF', 'CONVERT_ATTACH', 'tests#', 'ar_derogation.pdf', '0');
INSERT INTO adr_attachments (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (2, 2, 'PDF', 'CONVERT_ATTACH', 'tests#', 'ar_sva.pdf', '0');
INSERT INTO adr_attachments (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (3, 3, 'PDF', 'CONVERT_ATTACH', 'tests#', 'ar_svr.pdf', '0');
INSERT INTO adr_attachments (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (4, 4, 'PDF', 'CONVERT_ATTACH', 'tests#', 'invitation.pdf', '0');
INSERT INTO adr_attachments (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (5, 4, 'TNL', 'TNL_MLB', 'tests#', 'invitation.png', '0');
INSERT INTO adr_attachments (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (6, 5, 'PDF', 'CONVERT_ATTACH', 'tests#', 'rep_creche.pdf', '0');
INSERT INTO adr_attachments (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (7, 6, 'PDF', 'CONVERT_ATTACH', 'tests#', 'rep_standard.pdf', '0');
-- to process documents nnataly
INSERT INTO adr_attachments (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (8, 7, 'PDF', 'CONVERT_ATTACH', 'tests#', 'rep_standard_demande_intervention.pdf', '0');
-- to paraph documents ppetit
INSERT INTO adr_attachments (id, res_id, type, docserver_id, path, filename, fingerprint)
VALUES (9, 8, 'PDF', 'CONVERT_ATTACH', 'tests#', 'rep_standard_demande_intervention.pdf', '0');
Select setval('adr_attachments_id_seq', (select max(res_id)+1 from adr_attachments), false);

TRUNCATE TABLE mlb_coll_ext;
-- to sign documents
INSERT INTO mlb_coll_ext (res_id, category_id, exp_contact_id, exp_user_id, dest_contact_id, dest_user_id, nature_id, alt_identifier, admission_date, process_limit_date, closing_date, alarm1_date, alarm2_date, flag_alarm1, flag_alarm2, is_multicontacts, address_id)
VALUES (1, 'incoming', 4, NULL, NULL, NULL, 'simple_mail', 'MAARCH/2019A/1', CURRENT_DATE, CURRENT_DATE + 21, NULL, NULL, NULL, 'N', 'N', NULL, 6);
INSERT INTO mlb_coll_ext (res_id, category_id, exp_contact_id, exp_user_id, dest_contact_id, dest_user_id, nature_id, alt_identifier, admission_date, process_limit_date, closing_date, alarm1_date, alarm2_date, flag_alarm1, flag_alarm2, is_multicontacts, address_id)
VALUES (2, 'incoming', 4, NULL, NULL, NULL, 'simple_mail', 'MAARCH/2019A/2', CURRENT_DATE, CURRENT_DATE + 21, NULL, NULL, NULL, 'N', 'N', NULL, 6);
INSERT INTO mlb_coll_ext (res_id, category_id, exp_contact_id, exp_user_id, dest_contact_id, dest_user_id, nature_id, alt_identifier, admission_date, process_limit_date, closing_date, alarm1_date, alarm2_date, flag_alarm1, flag_alarm2, is_multicontacts, address_id)
VALUES (3, 'incoming', 4, NULL, NULL, NULL, 'simple_mail', 'MAARCH/2019A/3', CURRENT_DATE, CURRENT_DATE + 21, NULL, NULL, NULL, 'N', 'N', NULL, 6);
INSERT INTO mlb_coll_ext (res_id, category_id, exp_contact_id, exp_user_id, dest_contact_id, dest_user_id, nature_id, alt_identifier, admission_date, process_limit_date, closing_date, alarm1_date, alarm2_date, flag_alarm1, flag_alarm2, is_multicontacts, address_id)
VALUES (4, 'outgoing', NULL, NULL, 4, NULL, 'simple_mail', 'MAARCH/2019D/4', NULL, CURRENT_DATE + 21, NULL, NULL, NULL, 'N', 'N', NULL, 6);
INSERT INTO mlb_coll_ext (res_id, category_id, exp_contact_id, exp_user_id, dest_contact_id, dest_user_id, nature_id, alt_identifier, admission_date, process_limit_date, closing_date, alarm1_date, alarm2_date, flag_alarm1, flag_alarm2, is_multicontacts, address_id)
VALUES (5, 'incoming', 4, NULL, NULL, NULL, 'simple_mail', 'MAARCH/2019A/4', CURRENT_DATE, CURRENT_DATE + 21, NULL, NULL, NULL, 'N', 'N', NULL, 6);
INSERT INTO mlb_coll_ext (res_id, category_id, exp_contact_id, exp_user_id, dest_contact_id, dest_user_id, nature_id, alt_identifier, admission_date, process_limit_date, closing_date, alarm1_date, alarm2_date, flag_alarm1, flag_alarm2, is_multicontacts, address_id)
VALUES (6, 'incoming', 4, NULL, NULL, NULL, 'simple_mail', 'MAARCH/2019A/5', CURRENT_DATE, CURRENT_DATE + 21, NULL, NULL, NULL, 'N', 'N', NULL, 6);
-- to annotate documents
INSERT INTO mlb_coll_ext (res_id, category_id, exp_contact_id, exp_user_id, dest_contact_id, dest_user_id, nature_id, alt_identifier, admission_date, process_limit_date, closing_date, alarm1_date, alarm2_date, flag_alarm1, flag_alarm2, is_multicontacts, address_id)
VALUES (7, 'incoming', 5, NULL, NULL, NULL, 'simple_mail', 'MAARCH/2019A/6', CURRENT_DATE, CURRENT_DATE + 21, NULL, NULL, NULL, 'N', 'N', NULL, 7);
INSERT INTO mlb_coll_ext (res_id, category_id, exp_contact_id, exp_user_id, dest_contact_id, dest_user_id, nature_id, alt_identifier, admission_date, process_limit_date, closing_date, alarm1_date, alarm2_date, flag_alarm1, flag_alarm2, is_multicontacts, address_id)
VALUES (8, 'incoming', 6, NULL, NULL, NULL, 'simple_mail', 'MAARCH/2019A/7', CURRENT_DATE, CURRENT_DATE + 21, NULL, NULL, NULL, 'N', 'N', NULL, 8);
INSERT INTO mlb_coll_ext (res_id, category_id, exp_contact_id, exp_user_id, dest_contact_id, dest_user_id, nature_id, alt_identifier, admission_date, process_limit_date, closing_date, alarm1_date, alarm2_date, flag_alarm1, flag_alarm2, is_multicontacts, address_id)
VALUES (9, 'incoming', 7, NULL, NULL, NULL, 'simple_mail', 'MAARCH/2019A/8', CURRENT_DATE, CURRENT_DATE + 21, NULL, NULL, NULL, 'N', 'N', NULL, 9);
INSERT INTO mlb_coll_ext (res_id, category_id, exp_contact_id, exp_user_id, dest_contact_id, dest_user_id, nature_id, alt_identifier, admission_date, process_limit_date, closing_date, alarm1_date, alarm2_date, flag_alarm1, flag_alarm2, is_multicontacts, address_id)
VALUES (10, 'incoming', 1, NULL, NULL, NULL, 'simple_mail', 'MAARCH/2019A/9', CURRENT_DATE, CURRENT_DATE + 21, NULL, NULL, NULL, 'N', 'N', NULL, 10);
INSERT INTO mlb_coll_ext (res_id, category_id, exp_contact_id, exp_user_id, dest_contact_id, dest_user_id, nature_id, alt_identifier, admission_date, process_limit_date, closing_date, alarm1_date, alarm2_date, flag_alarm1, flag_alarm2, is_multicontacts, address_id)
VALUES (11, 'incoming', 1, NULL, NULL, NULL, 'simple_mail', 'MAARCH/2019A/10', CURRENT_DATE, CURRENT_DATE + 21, NULL, NULL, NULL, 'N', 'N', NULL, 10);
INSERT INTO mlb_coll_ext (res_id, category_id, exp_contact_id, exp_user_id, dest_contact_id, dest_user_id, nature_id, alt_identifier, admission_date, process_limit_date, closing_date, alarm1_date, alarm2_date, flag_alarm1, flag_alarm2, is_multicontacts, address_id)
VALUES (12, 'incoming', 8, NULL, NULL, NULL, 'simple_mail', 'MAARCH/2019A/11', CURRENT_DATE, CURRENT_DATE + 21, NULL, NULL, NULL, 'N', 'N', NULL, 11);
INSERT INTO mlb_coll_ext (res_id, category_id, exp_contact_id, exp_user_id, dest_contact_id, dest_user_id, nature_id, alt_identifier, admission_date, process_limit_date, closing_date, alarm1_date, alarm2_date, flag_alarm1, flag_alarm2, is_multicontacts, address_id)
VALUES (13, 'incoming', 4, NULL, NULL, NULL, 'simple_mail', 'MAARCH/2019A/12', CURRENT_DATE, CURRENT_DATE + 21, NULL, NULL, NULL, 'N', 'N', NULL, 6);
INSERT INTO mlb_coll_ext (res_id, category_id, exp_contact_id, exp_user_id, dest_contact_id, dest_user_id, nature_id, alt_identifier, admission_date, process_limit_date, closing_date, alarm1_date, alarm2_date, flag_alarm1, flag_alarm2, is_multicontacts, address_id)
VALUES (14, 'incoming', 7, NULL, NULL, NULL, 'simple_mail', 'MAARCH/2019A/13', CURRENT_DATE, CURRENT_DATE + 21, NULL, NULL, NULL, 'N', 'N', NULL, 9);
-- to qualify document
INSERT INTO mlb_coll_ext (res_id, category_id, exp_contact_id, exp_user_id, dest_contact_id, dest_user_id, nature_id, alt_identifier, admission_date, process_limit_date, closing_date, alarm1_date, alarm2_date, flag_alarm1, flag_alarm2, is_multicontacts, address_id)
VALUES (15, 'incoming', 4, NULL, NULL, NULL, 'simple_mail', 'MAARCH/2019A/14', CURRENT_DATE, CURRENT_DATE + 21, NULL, NULL, NULL, 'N', 'N', NULL, 6);
-- to validate document
INSERT INTO mlb_coll_ext (res_id, category_id, exp_contact_id, exp_user_id, dest_contact_id, dest_user_id, nature_id, alt_identifier, admission_date, process_limit_date, closing_date, alarm1_date, alarm2_date, flag_alarm1, flag_alarm2, is_multicontacts, address_id)
VALUES (16, 'incoming', 4, NULL, NULL, NULL, 'simple_mail', 'MAARCH/2019A/15', CURRENT_DATE, CURRENT_DATE + 21, NULL, NULL, NULL, 'N', 'N', NULL, 6);
-- to process document ccharles
INSERT INTO mlb_coll_ext (res_id, category_id, exp_contact_id, exp_user_id, dest_contact_id, dest_user_id, nature_id, alt_identifier, admission_date, process_limit_date, closing_date, alarm1_date, alarm2_date, flag_alarm1, flag_alarm2, is_multicontacts, address_id)
VALUES (17, 'incoming', 4, NULL, NULL, NULL, 'simple_mail', 'MAARCH/2019A/16', CURRENT_DATE, CURRENT_DATE + 21, NULL, NULL, NULL, 'N', 'N', NULL, 6);
-- to process document nnataly
INSERT INTO mlb_coll_ext (res_id, category_id, exp_contact_id, exp_user_id, dest_contact_id, dest_user_id, nature_id, alt_identifier, admission_date, process_limit_date, closing_date, alarm1_date, alarm2_date, flag_alarm1, flag_alarm2, is_multicontacts, address_id)
VALUES (18, 'incoming', 4, NULL, NULL, NULL, 'simple_mail', 'MAARCH/2019A/17', CURRENT_DATE, CURRENT_DATE + 21, NULL, NULL, NULL, 'N', 'N', NULL, 6);
-- to paraph document ppetit
INSERT INTO mlb_coll_ext (res_id, category_id, exp_contact_id, exp_user_id, dest_contact_id, dest_user_id, nature_id, alt_identifier, admission_date, process_limit_date, closing_date, alarm1_date, alarm2_date, flag_alarm1, flag_alarm2, is_multicontacts, address_id)
VALUES (19, 'incoming', 4, NULL, NULL, NULL, 'simple_mail', 'MAARCH/2019A/18', CURRENT_DATE, CURRENT_DATE + 21, NULL, NULL, NULL, 'N', 'N', NULL, 6);
-- to archive document ggrand
INSERT INTO mlb_coll_ext (res_id, category_id, exp_contact_id, exp_user_id, dest_contact_id, dest_user_id, nature_id, alt_identifier, admission_date, process_limit_date, closing_date, alarm1_date, alarm2_date, flag_alarm1, flag_alarm2, is_multicontacts, address_id)
VALUES (20, 'incoming', 4, NULL, NULL, NULL, 'simple_mail', 'MAARCH/2019A/19', CURRENT_DATE, CURRENT_DATE + 21, NULL, NULL, NULL, 'N', 'N', NULL, 6);

TRUNCATE TABLE listinstance;
ALTER SEQUENCE listinstance_id_seq restart WITH 1;
-- to sign documents
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (1, 'letterbox_coll', 1, 'DOC', 0, 'bbain', 'user_id', 'dest', 'bbain', 'PJS', 'Y', 0, 'entity_id', NULL, '', false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (2, 'letterbox_coll', 1, 'DOC', 0, 'DSG', 'entity_id', 'cc', 'bbain', 'PJS', 'Y', 0, 'entity_id', NULL, NULL, false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (3, 'letterbox_coll', 2, 'DOC', 0, 'ccharles', 'user_id', 'dest', 'ccharles', 'PTE', 'Y', 0, 'entity_id', NULL, '', false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (4, 'letterbox_coll', 2, 'DOC', 0, 'DSG', 'entity_id', 'cc', 'ccharles', 'PTE', 'Y', 0, 'entity_id', NULL, NULL, false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (5, 'letterbox_coll', 3, 'DOC', 0, 'rrenaud', 'user_id', 'dest', 'rrenaud', 'DGS', 'Y', 0, 'entity_id', NULL, '', false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (6, 'letterbox_coll', 4, 'DOC', 0, 'bbain', 'user_id', 'dest', 'bbain', 'PJS', 'Y', 0, 'entity_id', NULL, '', false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (7, 'letterbox_coll', 5, 'DOC', 0, 'ssaporta', 'user_id', 'dest', 'ssaporta', 'PE', 'Y', 0, 'entity_id', NULL, '', false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (8, 'letterbox_coll', 5, 'DOC', 0, 'DSG', 'entity_id', 'cc', 'bbain', 'PJS', 'Y', 0, 'entity_id', NULL, NULL, false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (9, 'letterbox_coll', 6, 'DOC', 0, 'ssaporta', 'user_id', 'dest', 'ssaporta', 'PE', 'Y', 0, 'entity_id', NULL, '', false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (10, 'letterbox_coll', 6, 'DOC', 0, 'DSG', 'entity_id', 'cc', 'bbain', 'PJS', 'Y', 0, 'entity_id', NULL, NULL, false, false);
-- to annotate documents
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (11, 'letterbox_coll', 7, 'DOC', 0, 'rrenaud', 'user_id', 'dest', 'rrenaud', 'DGS', 'Y', 0, 'entity_id', NULL, '', false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (12, 'letterbox_coll', 8, 'DOC', 0, 'rrenaud', 'user_id', 'dest', 'rrenaud', 'DGS', 'Y', 0, 'entity_id', NULL, '', false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (13, 'letterbox_coll', 9, 'DOC', 0, 'ssaporta', 'user_id', 'dest', 'ssaporta', 'PE', 'Y', 0, 'entity_id', NULL, '', false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (14, 'letterbox_coll', 9, 'DOC', 0, 'DSG', 'entity_id', 'cc', 'bbain', 'PJS', 'Y', 0, 'entity_id', NULL, NULL, false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (15, 'letterbox_coll', 10, 'DOC', 0, 'rrenaud', 'user_id', 'dest', 'rrenaud', 'DGS', 'Y', 0, 'entity_id', NULL, '', false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (16, 'letterbox_coll', 11, 'DOC', 0, 'sstar', 'user_id', 'dest', 'sstar', 'FIN', 'Y', 0, 'entity_id', NULL, '', false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (17, 'letterbox_coll', 11, 'DOC', 0, 'DSG', 'entity_id', 'cc', 'sstar', 'FIN', 'Y', 0, 'entity_id', NULL, NULL, false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (18, 'letterbox_coll', 11, 'DOC', 0, 'jjane', 'user_id', 'cc', 'sstar', 'FIN', 'Y', 0, 'entity_id', NULL, NULL, false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (19, 'letterbox_coll', 12, 'DOC', 0, 'rrenaud', 'user_id', 'dest', 'rrenaud', 'DGS', 'Y', 0, 'entity_id', NULL, '', false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (20, 'letterbox_coll', 13, 'DOC', 0, 'ppruvost', 'user_id', 'dest', 'bbain', 'PJS', 'Y', 0, 'entity_id', NULL, '', false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (21, 'letterbox_coll', 13, 'DOC', 0, 'DSG', 'entity_id', 'cc', 'bbain', 'PJS', 'Y', 0, 'entity_id', NULL, NULL, false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (22, 'letterbox_coll', 14, 'DOC', 0, 'ssaporta', 'user_id', 'dest', 'ssaporta', 'PE', 'Y', 0, 'entity_id', NULL, '', false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (23, 'letterbox_coll', 14, 'DOC', 0, 'DSG', 'entity_id', 'cc', 'bbain', 'PJS', 'Y', 0, 'entity_id', NULL, NULL, false, false);
-- to qualify document
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (24, 'letterbox_coll', 15, 'DOC', 0, 'ccharles', 'user_id', 'dest', 'ccharles', 'PTE', 'Y', 0, 'entity_id', NULL, '', false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (25, 'letterbox_coll', 15, 'DOC', 0, 'DSG', 'entity_id', 'cc', 'ccharles', 'PTE', 'Y', 0, 'entity_id', NULL, NULL, false, false);
-- to validate document
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (26, 'letterbox_coll', 16, 'DOC', 0, 'ccharles', 'user_id', 'dest', 'ccharles', 'PTE', 'Y', 0, 'entity_id', NULL, '', false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (27, 'letterbox_coll', 16, 'DOC', 0, 'DSG', 'entity_id', 'cc', 'ccharles', 'PTE', 'Y', 0, 'entity_id', NULL, NULL, false, false);
-- to process document ccharles
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (28, 'letterbox_coll', 17, 'DOC', 0, 'ccharles', 'user_id', 'dest', 'ccharles', 'PTE', 'Y', 0, 'entity_id', NULL, '', false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (29, 'letterbox_coll', 17, 'DOC', 0, 'DSG', 'entity_id', 'cc', 'ccharles', 'PTE', 'Y', 0, 'entity_id', NULL, NULL, false, false);
-- to process document nnataly
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (30, 'letterbox_coll', 18, 'DOC', 0, 'nnataly', 'user_id', 'dest', 'nnataly', 'PSO', 'Y', 0, 'entity_id', NULL, '', false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (31, 'letterbox_coll', 18, 'DOC', 0, 'DSG', 'entity_id', 'cc', 'nnataly', 'PSO', 'Y', 0, 'entity_id', NULL, NULL, false, false);
-- to paraph document ppetit
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (32, 'letterbox_coll', 19, 'DOC', 0, 'ccharles', 'user_id', 'dest', 'ccharles', 'PTE', 'Y', 0, 'entity_id', NULL, '', false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (33, 'letterbox_coll', 19, 'DOC', 0, 'DSG', 'entity_id', 'cc', 'ccharles', 'PTE', 'Y', 0, 'entity_id', NULL, NULL, false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature) 
VALUES (34, 'letterbox_coll', 19, 'DOC', 0, 'mmanfred', 'user_id', 'visa', 'ccharles', 'PTE', 'Y', 1, 'VISA_CIRCUIT', CURRENT_DATE, '', false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature) 
VALUES (35, 'letterbox_coll', 19, 'DOC', 0, 'ppetit', 'user_id', 'sign', 'ccharles', 'PTE', 'Y', 0, 'VISA_CIRCUIT', NULL, '', false, true);
-- to archive document ggrand
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (36, 'letterbox_coll', 20, 'DOC', 0, 'ccharles', 'user_id', 'dest', 'ccharles', 'PTE', 'Y', 0, 'entity_id', NULL, '', false, false);
INSERT INTO listinstance (listinstance_id, coll_id, res_id, listinstance_type, sequence, item_id, item_type, item_mode, added_by_user, added_by_entity, visible, viewed, difflist_type, process_date, process_comment, signatory, requested_signature)
VALUES (37, 'letterbox_coll', 20, 'DOC', 0, 'DSG', 'entity_id', 'cc', 'ccharles', 'PTE', 'Y', 0, 'entity_id', NULL, NULL, false, false);

Select setval('listinstance_id_seq', (select max(listinstance_id)+1 from listinstance), false);

--signature of ppetit
TRUNCATE TABLE user_signatures;
INSERT INTO user_signatures (id, user_serial_id, signature_label, signature_path, signature_file_name, fingerprint) 
VALUES (1, 10, 'ppetit.jpeg', '0000#', 'ppetit.jpeg', NULL);
Select setval('user_signatures_id_seq', (select max(id)+1 from user_signatures), false);

--update parameters for chrono
DELETE FROM parameters WHERE id = 'chrono_incoming_2019' OR  id = 'chrono_outgoing_2019';
INSERT INTO parameters (id, description, param_value_string, param_value_int, param_value_date) 
VALUES ('chrono_incoming_2019', NULL, NULL, 100, NULL);
INSERT INTO parameters (id, description, param_value_string, param_value_int, param_value_date) 
VALUES ('chrono_outgoing_2019', NULL, NULL, 100, NULL);

