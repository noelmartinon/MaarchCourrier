SET CLIENT_ENCODING TO 'UTF8';
-- Create USERGROUPS & USERGROUPS_SERVICES
TRUNCATE TABLE usergroups;
TRUNCATE TABLE usergroups_services;
DELETE FROM usergroups WHERE group_id = 'COURRIER';
DELETE FROM usergroups_services WHERE group_id = 'COURRIER';
INSERT INTO usergroups VALUES ('COURRIER', 'Opérateur de scan', 'N', 'N', 'N', 'N', 'N', 'Y');
DELETE FROM usergroups WHERE group_id = 'AGENT';
DELETE FROM usergroups_services WHERE group_id = 'AGENT';
INSERT INTO usergroups VALUES ('AGENT', 'Utilisateur', 'N', 'N', 'N', 'N', 'N', 'Y');
DELETE FROM usergroups WHERE group_id = 'RESP_COURRIER';
DELETE FROM usergroups_services WHERE group_id = 'RESP_COURRIER';
INSERT INTO usergroups VALUES ('RESP_COURRIER', 'Responsable Courrier', 'N', 'N', 'N', 'N', 'N', 'Y');
DELETE FROM usergroups WHERE group_id = 'RESPONSABLE';
DELETE FROM usergroups_services WHERE group_id = 'RESPONSABLE';
INSERT INTO usergroups VALUES ('RESPONSABLE', 'Manager', 'N', 'N', 'N', 'N', 'N', 'Y');
DELETE FROM usergroups WHERE group_id = 'ADMINISTRATEUR';
DELETE FROM usergroups_services WHERE group_id = 'ADMINISTRATEUR';
INSERT INTO usergroups VALUES ('ADMINISTRATEUR', 'Admin. fonctionnel', 'N', 'N', 'N', 'N', 'N', 'Y');
DELETE FROM usergroups WHERE group_id = 'DIRECTEUR';
DELETE FROM usergroups_services WHERE group_id = 'DIRECTEUR';
INSERT INTO usergroups VALUES ('DIRECTEUR', 'Directeur', 'N', 'N', 'N', 'N', 'N', 'Y');
DELETE FROM usergroups WHERE group_id = 'ELU';
DELETE FROM usergroups_services WHERE group_id = 'ELU';
INSERT INTO usergroups VALUES ('ELU', 'Elu', 'N', 'N', 'N', 'N', 'N', 'Y');
DELETE FROM usergroups WHERE group_id = 'ARCHIVISTE';
DELETE FROM usergroups_services WHERE group_id = 'ARCHIVISTE';
INSERT INTO usergroups VALUES ('ARCHIVISTE', 'Archiviste', 'N', 'N', 'N', 'N', 'N', 'Y');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'admin');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'adv_search_mlb');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'index_mlb');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'search_contacts');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'my_contacts');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'my_contacts_menu');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'create_contacts');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'update_contacts');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'reopen_mail');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'view_technical_infos');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'view_doc_history');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'view_full_history');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'add_links');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'print_details');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'print_doc_details_from_list');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'update_case');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'join_res_case');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'join_res_case_in_process');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'close_case');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'add_cases');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'view_baskets');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'view_folder_tree');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'associate_folder');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'folder_search');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'close_folder');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'modify_folder');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'folder_freeze');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'delete_folder');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'add_copy_in_process');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'add_copy_in_indexing_validation');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'update_list_diff_in_details');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'entities_print_sep_mlb');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'sendmail');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'use_mail_services');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'reports');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'edit_attachments_from_detail');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'modify_attachments');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'delete_attachments');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'add_new_version');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'view_version_letterbox');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'print_folder_doc');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'add_tag_to_res');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'create_tag');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'private_tag');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'tag_view');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', '_print_sep');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'physical_archive_print_sep_mlb');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'physical_archive');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'admin_fileplan');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'put_doc_in_fileplan');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'fileplan');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('COURRIER', 'notes_restriction');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'adv_search_mlb');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'index_mlb');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'search_contacts');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'my_contacts');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'my_contacts_menu');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'update_contacts');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'view_doc_history');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'add_links');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'print_details');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'print_doc_details_from_list');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'update_case');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'join_res_case');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'join_res_case_in_process');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'close_case');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'add_cases');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'view_baskets');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'view_folder_tree');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'create_folder');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'associate_folder');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'add_copy_in_process');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'add_copy_in_indexing_validation');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'update_list_diff_in_details');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'sendmail');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'use_mail_services');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'edit_attachments_from_detail');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'modify_attachments');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'delete_attachments');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'add_new_version');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'view_version_letterbox');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'config_visa_workflow');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'config_visa_workflow_in_detail');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'print_folder_doc');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'config_avis_workflow');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'config_avis_workflow_in_detail');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'add_tag_to_res');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'private_tag');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'tag_view');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'put_doc_in_fileplan');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'fileplan');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'notes_restriction');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'thesaurus_view');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('AGENT', 'add_thesaurus_to_res');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'admin');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'adv_search_mlb');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'index_mlb');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'search_contacts');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'my_contacts');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'my_contacts_menu');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'update_contacts');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'view_doc_history');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'view_full_history');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'add_links');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'print_details');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'print_doc_details_from_list');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'update_case');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'join_res_case');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'join_res_case_in_process');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'close_case');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'add_cases');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'view_baskets');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'view_folder_tree');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'create_folder');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'associate_folder');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'add_copy_in_process');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'add_copy_in_indexing_validation');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'update_list_diff_in_details');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'sendmail');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'use_mail_services');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'reports');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'add_new_version');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'view_version_letterbox');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'print_folder_doc');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'add_tag_to_res');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'private_tag');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'tag_view');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'put_doc_in_fileplan');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'fileplan');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'notes_restriction');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'thesaurus_view');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESP_COURRIER', 'add_thesaurus_to_res');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'adv_search_mlb');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'index_mlb');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'search_contacts');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'my_contacts');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'my_contacts_menu');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'update_contacts');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'view_doc_history');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'add_links');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'print_details');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'print_doc_details_from_list');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'update_case');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'join_res_case');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'join_res_case_in_process');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'close_case');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'add_cases');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'view_baskets');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'view_folder_tree');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'create_folder');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'associate_folder');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'add_copy_in_process');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'add_copy_in_indexing_validation');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'update_list_diff_in_details');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'sendmail');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'use_mail_services');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'reports');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'edit_attachments_from_detail');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'modify_attachments');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'delete_attachments');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'add_new_version');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'view_version_letterbox');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'config_visa_workflow');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'config_visa_workflow_in_detail');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'sign_document');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'visa_documents');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'modify_visa_in_signatureBook');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'print_folder_doc');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'config_avis_workflow');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'config_avis_workflow_in_detail');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'avis_documents');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'add_tag_to_res');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'private_tag');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'tag_view');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'put_doc_in_fileplan');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'fileplan');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'notes_restriction');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'thesaurus_view');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('RESPONSABLE', 'add_thesaurus_to_res');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'admin');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'adv_search_mlb');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'index_mlb');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'search_contacts');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'admin_users');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'admin_groups');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'admin_architecture');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'view_history');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'view_history_batch');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'admin_status');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'admin_actions');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'admin_contacts');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'my_contacts');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'my_contacts_menu');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'create_contacts');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'update_contacts');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'reopen_mail');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'admin_docservers');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'view_technical_infos');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'view_doc_history');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'view_full_history');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'add_links');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'admin_parameters');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'admin_priorities');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'print_details');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'print_doc_details_from_list');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'update_case');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'join_res_case');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'join_res_case_in_process');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'close_case');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'add_cases');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'admin_baskets');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'view_baskets');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'view_folder_tree');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'create_folder');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'associate_folder');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'folder_search');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'close_folder');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'modify_folder');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'folder_freeze');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'delete_folder');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'admin_foldertypes');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'manage_entities');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'admin_difflist_types');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'admin_listmodels');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'add_copy_in_process');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'add_copy_in_indexing_validation');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'update_list_diff_in_details');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'entities_print_sep_mlb');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'sendmail');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'use_mail_services');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'reports');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'admin_reports');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'graphics_reports');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'edit_attachments_from_detail');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'modify_attachments');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'delete_attachments');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'add_new_version');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'view_version_letterbox');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'config_visa_workflow');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'config_visa_workflow_in_detail');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'sign_document');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'visa_documents');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'modify_visa_in_signatureBook');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'print_folder_doc');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'config_avis_workflow');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'admin_templates');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'admin_tag');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'add_tag_to_res');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'create_tag');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'private_tag');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'tag_view');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'admin_notif');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', '_print_sep');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'physical_archive_print_sep_mlb');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'physical_archive_batch_manage');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'physical_archive_batch_read');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'physical_archive');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'physical_archive_box_read');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'physical_archive_box_manage');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'admin_fileplan');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'put_doc_in_fileplan');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'fileplan');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'admin_life_cycle');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'notes_restriction');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'admin_thesaurus');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'thesaurus_view');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ADMINISTRATEUR', 'add_thesaurus_to_res');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('DIRECTEUR', 'view_doc_history');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ELU', 'adv_search_mlb');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ELU', 'search_contacts');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ELU', 'update_contacts');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ELU', 'view_baskets');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ELU', 'view_folder_tree');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ELU', 'sendmail');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ELU', 'use_mail_services');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ELU', 'add_new_version');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ELU', 'view_version_letterbox');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ELU', 'config_visa_workflow_in_detail');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ELU', 'sign_document');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ELU', 'visa_documents');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ELU', 'modify_visa_in_signatureBook');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ELU', 'print_folder_doc');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ELU', 'avis_documents');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ELU', 'tag_view');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ELU', 'notes_restriction');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ARCHIVISTE', 'adv_search_mlb');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ARCHIVISTE', 'create_contacts');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ARCHIVISTE', 'view_technical_infos');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ARCHIVISTE', 'view_doc_history');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ARCHIVISTE', 'view_full_history');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ARCHIVISTE', 'view_baskets');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ARCHIVISTE', 'sendmail');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ARCHIVISTE', 'avis_documents');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ARCHIVISTE', 'tag_view');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ARCHIVISTE', 'put_doc_in_fileplan');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ARCHIVISTE', 'fileplan');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ARCHIVISTE', 'export_seda_view');
INSERT INTO usergroups_services (group_id, service_id) VALUES ('ARCHIVISTE', 'add_thesaurus_to_res');

-- Create DOCTYPES
TRUNCATE TABLE DOCTYPES_FIRST_LEVEL;
TRUNCATE TABLE DOCTYPES_SECOND_LEVEL;
TRUNCATE TABLE DOCTYPES;
TRUNCATE TABLE MLB_DOCTYPE_EXT;
TRUNCATE TABLE DOCTYPES_INDEXES;
TRUNCATE TABLE TEMPLATES_DOCTYPE_EXT;
TRUNCATE TABLE FOLDERTYPES_DOCTYPES_LEVEL1;

INSERT INTO doctypes_first_level (doctypes_first_level_id, doctypes_first_level_label, css_style, enabled) VALUES (1, 'COURRIERS', 'black_style_big', 'Y');
INSERT INTO foldertypes_doctypes_level1 (foldertype_id, doctypes_first_level_id) VALUES (1, 1);
INSERT INTO doctypes_second_level (doctypes_second_level_id, doctypes_second_level_label, doctypes_first_level_id, css_style, enabled) VALUES (1, '01. Correspondances', 1, 'black_style_big', 'Y');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',101, 'Abonnements – documentation – archives', 'Y', 1, 1, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (101, 30, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',102, 'Convocation', 'Y', 1, 1, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (102, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',103, 'Demande de documents', 'Y', 1, 1, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (103, 30, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',104, 'Demande de fournitures et matériels', 'Y', 1, 1, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (104, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',105, 'Demande de RDV', 'Y', 1, 1, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (105, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',106, 'Demande de renseignements', 'Y', 1, 1, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (106, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',107, 'Demande mise à jour de fichiers', 'Y', 1, 1, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (107, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',108, 'Demande Multi-Objet', 'Y', 1, 1, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (108, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',109, 'Installation provisoire dans un équipement ville', 'Y', 1, 1, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (109, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',110, 'Invitation', 'Y', 1, 1, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (110, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',111, 'Rapport – Compte-rendu – Bilan', 'Y', 1, 1, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (111, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',112, 'Réservation d''un local communal et scolaire', 'Y', 1, 1, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (112, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes_second_level (doctypes_second_level_id, doctypes_second_level_label, doctypes_first_level_id, css_style, enabled) VALUES (2, '02. Cabinet', 1, 'black_style_big', 'Y');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',201, 'Pétition', 'Y', 1, 2, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (201, 15, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',202, 'Communication', 'Y', 1, 2, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (202, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',203, 'Politique', 'Y', 1, 2, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (203, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',204, 'Relations et solidarité internationales ', 'Y', 1, 2, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (204, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',205, 'Remerciements et félicitations', 'Y', 1, 2, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (205, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',206, 'Sécurité', 'Y', 1, 2, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (206, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',207, 'Suggestion', 'Y', 1, 2, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (207, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes_second_level (doctypes_second_level_id, doctypes_second_level_label, doctypes_first_level_id, css_style, enabled) VALUES (3, '03. Éducation', 1, 'black_style_big', 'Y');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',301, 'Culture', 'Y', 1, 3, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (301, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',302, 'Demande scolaire hors inscription et dérogation', 'Y', 1, 3, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (302, 60, 14, 1, 'SVR');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',303, 'Éducation nationale', 'Y', 1, 3, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (303, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',304, 'Jeunesse', 'Y', 1, 3, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (304, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',305, 'Lycées et collèges', 'Y', 1, 3, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (305, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',306, 'Parentalité', 'Y', 1, 3, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (306, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',307, 'Petite Enfance', 'Y', 1, 3, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (307, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',308, 'Sport', 'Y', 1, 3, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (308, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes_second_level (doctypes_second_level_id, doctypes_second_level_label, doctypes_first_level_id, css_style, enabled) VALUES (4, '04. Finances', 1, 'black_style_big', 'Y');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',401, 'Contestation financière', 'Y', 1, 4, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (401, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',402, 'Contrat de prêt', 'Y', 1, 4, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (402, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',403, 'Garantie d''emprunt', 'Y', 1, 4, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (403, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',404, 'Paiement', 'Y', 1, 4, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (404, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',405, 'Quotient familial', 'Y', 1, 4, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (405, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',406, 'Subvention', 'Y', 1, 4, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (406, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',407, 'Facture ou avoir', 'Y', 1, 4, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (407, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes_indexes (type_id, coll_id, field_name, mandatory) VALUES (407, 'letterbox_coll', 'custom_t1', 'Y');
INSERT INTO doctypes_indexes (type_id, coll_id, field_name, mandatory) VALUES (407, 'letterbox_coll', 'custom_t2', 'N');
INSERT INTO doctypes_indexes (type_id, coll_id, field_name, mandatory) VALUES (407, 'letterbox_coll', 'custom_f1', 'N');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',408, 'Proposition financière', 'Y', 1, 4, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (408, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes_second_level (doctypes_second_level_id, doctypes_second_level_label, doctypes_first_level_id, css_style, enabled) VALUES (5, '05. Juridique', 1, 'black_style_big', 'Y');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',501, 'Hospitalisation d''office', 'Y', 1, 5, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (501, 2, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',502, 'Mise en demeure', 'Y', 1, 5, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (502, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',503, 'Plainte', 'Y', 1, 5, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (503, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',504, 'Recours contentieux', 'Y', 1, 5, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (504, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',505, 'Recours gracieux et réclamations', 'Y', 1, 5, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (505, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes_second_level (doctypes_second_level_id, doctypes_second_level_label, doctypes_first_level_id, css_style, enabled) VALUES (6, '06. Population ', 1, 'black_style_big', 'Y');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',601, 'Débits de boisson', 'Y', 1, 6, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (601, 60, 14, 1, 'SVR');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',602, 'Demande d’État Civil', 'Y', 1, 6, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (602, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',603, 'Élections', 'Y', 1, 6, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (603, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',604, 'Étrangers', 'Y', 1, 6, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (604, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',605, 'Marché', 'Y', 1, 6, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (605, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',606, 'Médaille du travail', 'Y', 1, 6, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (606, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',607, 'Stationnement taxi', 'Y', 1, 6, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (607, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',608, 'Vente au déballage', 'Y', 1, 6, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (608, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes_second_level (doctypes_second_level_id, doctypes_second_level_label, doctypes_first_level_id, css_style, enabled) VALUES (7, '07. Ressources Humaines', 1, 'black_style_big', 'Y');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',701, 'Arrêts de travail et maladie', 'Y', 1, 7, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (701, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes_indexes (type_id, coll_id, field_name, mandatory) VALUES (701, 'letterbox_coll', 'custom_t3', 'N');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',702, 'Assurance du personnel', 'Y', 1, 7, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (702, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes_indexes (type_id, coll_id, field_name, mandatory) VALUES (702, 'letterbox_coll', 'custom_t3', 'N');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',703, 'Candidature', 'Y', 1, 7, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (703, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',704, 'Carrière', 'Y', 1, 7, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (704, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes_indexes (type_id, coll_id, field_name, mandatory) VALUES (704, 'letterbox_coll', 'custom_t3', 'N');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',705, 'Conditions de travail santé', 'Y', 1, 7, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (705, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes_indexes (type_id, coll_id, field_name, mandatory) VALUES (705, 'letterbox_coll', 'custom_t3', 'N');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',706, 'Congés exceptionnels et concours', 'Y', 1, 7, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (706, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes_indexes (type_id, coll_id, field_name, mandatory) VALUES (706, 'letterbox_coll', 'custom_t3', 'N');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',707, 'Formation', 'Y', 1, 7, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (707, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes_indexes (type_id, coll_id, field_name, mandatory) VALUES (707, 'letterbox_coll', 'custom_t3', 'N');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',708, 'Instances RH', 'Y', 1, 7, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (708, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes_indexes (type_id, coll_id, field_name, mandatory) VALUES (708, 'letterbox_coll', 'custom_t3', 'N');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',709, 'Retraite', 'Y', 1, 7, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (709, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes_indexes (type_id, coll_id, field_name, mandatory) VALUES (709, 'letterbox_coll', 'custom_t3', 'N');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',710, 'Stage', 'Y', 1, 7, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (710, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes_indexes (type_id, coll_id, field_name, mandatory) VALUES (710, 'letterbox_coll', 'custom_t3', 'N');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',711, 'Syndicats', 'Y', 1, 7, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (711, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes_indexes (type_id, coll_id, field_name, mandatory) VALUES (711, 'letterbox_coll', 'custom_t3', 'N');
INSERT INTO doctypes_second_level (doctypes_second_level_id, doctypes_second_level_label, doctypes_first_level_id, css_style, enabled) VALUES (8, '08. Social', 1, 'black_style_big', 'Y');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',801, 'Aide à domicile', 'Y', 1, 8, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (801, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',802, 'Aide Financière', 'Y', 1, 8, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (802, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',803, 'Animations retraités', 'Y', 1, 8, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (803, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',804, 'Domiciliation', 'Y', 1, 8, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (804, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',805, 'Dossier de logement', 'Y', 1, 8, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (805, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',806, 'Expulsion', 'Y', 1, 8, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (806, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',807, 'Foyer', 'Y', 1, 8, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (807, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',808, 'Obligation alimentaire', 'Y', 1, 8, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (808, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',809, 'RSA', 'Y', 1, 8, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (809, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',810, 'Scolarisation à domicile', 'Y', 1, 8, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (810, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes_second_level (doctypes_second_level_id, doctypes_second_level_label, doctypes_first_level_id, css_style, enabled) VALUES (9, '09. Technique', 1, 'black_style_big', 'Y');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',901, 'Aire d''accueil des gens du voyage', 'Y', 1, 9, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (901, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',902, 'Assainissement', 'Y', 1, 9, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (902, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',903, 'Assurance et sinistre', 'Y', 1, 9, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (903, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',904, 'Autorisation d''occupation du domaine public', 'Y', 1, 9, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (904, 60, 14, 1, 'SVR');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',905, 'Contrat et convention hors marchés publics', 'Y', 1, 9, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (905, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',906, 'Détention de chiens dangereux', 'Y', 1, 9, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (906, 60, 14, 1, 'SVR');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',907, 'Espaces verts – Environnement – Développement durable', 'Y', 1, 9, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (907, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',908, 'Hygiène et Salubrité', 'Y', 1, 9, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (908, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',909, 'Marchés Publics', 'Y', 1, 9, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (909, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',910, 'Mobiliers urbains', 'Y', 1, 9, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (910, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',911, 'NTIC', 'Y', 1, 9, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (911, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',912, 'Opération d''aménagement', 'Y', 1, 9, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (912, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',913, 'Patrimoine', 'Y', 1, 9, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (913, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',914, 'Problème de voisinage', 'Y', 1, 9, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (914, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',915, 'Propreté', 'Y', 1, 9, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (915, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',916, 'Stationnement et circulation', 'Y', 1, 9, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (916, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',917, 'Transports', 'Y', 1, 9, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (917, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',918, 'Travaux', 'Y', 1, 9, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (918, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes_second_level (doctypes_second_level_id, doctypes_second_level_label, doctypes_first_level_id, css_style, enabled) VALUES (10, '10. Urbanisme', 1, 'black_style_big', 'Y');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',1001, 'Alignement', 'Y', 1, 10, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (1001, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',1002, 'Avis d''urbanisme', 'Y', 1, 10, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (1002, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',1003, 'Commerces', 'Y', 1, 10, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (1003, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',1004, 'Numérotation', 'Y', 1, 10, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (1004, 60, 14, 1, 'NORMAL');
INSERT INTO doctypes_second_level (doctypes_second_level_id, doctypes_second_level_label, doctypes_first_level_id, css_style, enabled) VALUES (11, '11. Silence vaut acceptation', 1, 'orange_style_big', 'Y');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',1101, 'Autorisation de buvette', 'Y', 1, 11, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (1101, 60, 14, 1, 'SVA');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',1102, 'Cimetière', 'Y', 1, 11, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (1102, 60, 14, 1, 'SVA');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',1103, 'Demande de dérogation scolaire', 'Y', 1, 11, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (1103, 60, 14, 1, 'SVA');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',1104, 'Inscription à la cantine et activités périscolaires ', 'Y', 1, 11, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (1104, 60, 14, 1, 'SVA');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',1105, 'Inscription toutes petites sections', 'Y', 1, 11, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (1105, 90, 14, 1, 'SVA');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',1106, 'Travaux ERP', 'Y', 1, 11, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (1106, 60, 14, 1, 'SVA');
INSERT INTO doctypes_second_level (doctypes_second_level_id, doctypes_second_level_label, doctypes_first_level_id, css_style, enabled) VALUES (12, '12. Formulaires', 1, 'blue_style_big', 'Y');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',1201, 'Appel téléphonique', 'Y', 1, 12, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (1201, 21, 14, 1, 'NORMAL');
INSERT INTO doctypes (coll_id, type_id, description, enabled, doctypes_first_level_id, doctypes_second_level_id, primary_retention, secondary_retention) VALUES ('letterbox_coll',1202, 'Demande intervention voirie', 'Y', 1, 12, NULL, NULL);
INSERT INTO mlb_doctype_ext(type_id, process_delay, delay1, delay2, process_mode) VALUES (1202, 21, 14, 1, 'NORMAL');
select setval('doctypes_first_level_id_seq', (select max(doctypes_first_level_id)+1 from doctypes_first_level), false);
select setval('doctypes_second_level_id_seq', (select max(doctypes_second_level_id)+1 from doctypes_second_level), false);
select setval('doctypes_type_id_seq', (select max(type_id)+1 from doctypes), false);

-- Create USERS
DELETE FROM users WHERE user_id <> 'superadmin';
TRUNCATE TABLE users_entities;
DELETE FROM users WHERE user_id = 'rrenaud';
DELETE FROM users_entities WHERE user_id = 'rrenaud';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('rrenaud', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Robert', 'RENAUD', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('rrenaud', 'DGS', '', 'Y');
DELETE FROM users WHERE user_id = 'ccordy';
DELETE FROM users_entities WHERE user_id = 'ccordy';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('ccordy', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Chloé', 'CORDY', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('ccordy', 'DSI', '', 'Y');
DELETE FROM users WHERE user_id = 'ssissoko';
DELETE FROM users_entities WHERE user_id = 'ssissoko';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('ssissoko', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Sylvain', 'SISSOKO', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('ssissoko', 'DSI', '', 'Y');
DELETE FROM users WHERE user_id = 'nnataly';
DELETE FROM users_entities WHERE user_id = 'nnataly';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('nnataly', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Nancy', 'NATALY', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('nnataly', 'PSO', '', 'Y');
DELETE FROM users WHERE user_id = 'ddur';
DELETE FROM users_entities WHERE user_id = 'ddur';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('ddur', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Dominique', 'DUR', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('ddur', 'ELUS', '', 'Y');
DELETE FROM users WHERE user_id = 'jjane';
DELETE FROM users_entities WHERE user_id = 'jjane';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('jjane', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Jenny', 'JANE', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('jjane', 'CCAS', '', 'Y');
DELETE FROM users WHERE user_id = 'eerina';
DELETE FROM users_entities WHERE user_id = 'eerina';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('eerina', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Edith', 'ERINA', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('eerina', 'CAB', '', 'Y');
DELETE FROM users WHERE user_id = 'kkaar';
DELETE FROM users_entities WHERE user_id = 'kkaar';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('kkaar', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Katy', 'KAAR', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('kkaar', 'DGA', '', 'Y');
DELETE FROM users WHERE user_id = 'bboule';
DELETE FROM users_entities WHERE user_id = 'bboule';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('bboule', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Bruno', 'BOULE', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('bboule', 'PCU', '', 'Y');
DELETE FROM users WHERE user_id = 'ppetit';
DELETE FROM users_entities WHERE user_id = 'ppetit';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('ppetit', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Patricia', 'PETIT', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('ppetit', 'VILLE', '', 'Y');
DELETE FROM users WHERE user_id = 'aackermann';
DELETE FROM users_entities WHERE user_id = 'aackermann';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('aackermann', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Amanda', 'ACKERMANN', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('aackermann', 'PSF', '', 'Y');
DELETE FROM users WHERE user_id = 'ppruvost';
DELETE FROM users_entities WHERE user_id = 'ppruvost';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('ppruvost', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Pierre', 'PRUVOST', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('ppruvost', 'DRH', '', 'Y');
DELETE FROM users WHERE user_id = 'ttong';
DELETE FROM users_entities WHERE user_id = 'ttong';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('ttong', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Tony', 'TONG', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('ttong', 'SP', '', 'Y');
DELETE FROM users WHERE user_id = 'sstar';
DELETE FROM users_entities WHERE user_id = 'sstar';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('sstar', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Suzanne', 'STAR', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('sstar', 'FIN', '', 'Y');
DELETE FROM users WHERE user_id = 'ssaporta';
DELETE FROM users_entities WHERE user_id = 'ssaporta';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('ssaporta', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Sabrina', 'SAPORTA', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('ssaporta', 'PE', '', 'Y');
DELETE FROM users WHERE user_id = 'ccharles';
DELETE FROM users_entities WHERE user_id = 'ccharles';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('ccharles', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Charlotte', 'CHARLES', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('ccharles', 'PTE', '', 'Y');
DELETE FROM users WHERE user_id = 'mmanfred';
DELETE FROM users_entities WHERE user_id = 'mmanfred';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('mmanfred', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Martin', 'MANFRED', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('mmanfred', 'DGA', '', 'Y');
DELETE FROM users WHERE user_id = 'ddaull';
DELETE FROM users_entities WHERE user_id = 'ddaull';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('ddaull', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Denis', 'DAULL', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('ddaull', 'DSG', '', 'Y');
DELETE FROM users WHERE user_id = 'bbain';
DELETE FROM users_entities WHERE user_id = 'bbain';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('bbain', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Barbara', 'BAIN', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('bbain', 'PJS', '', 'Y');
DELETE FROM users WHERE user_id = 'jjonasz';
DELETE FROM users_entities WHERE user_id = 'jjonasz';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('jjonasz', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Jean', 'JONASZ', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('jjonasz', 'PJU', '', 'Y');
DELETE FROM users WHERE user_id = 'bblier';
DELETE FROM users_entities WHERE user_id = 'bblier';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('bblier', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Bernard', 'BLIER', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('bblier', 'COU', '', 'Y');
DELETE FROM users WHERE user_id = 'ggrand';
DELETE FROM users_entities WHERE user_id = 'ggrand';
INSERT INTO users (user_id, password, firstname, lastname, mail, enabled, change_password, status, loginmode) VALUES ('ggrand', '65d1d802c2c5e7e9035c5cef3cfc0902b6d0b591bfa85977055290736bbfcdd7e19cb7cfc9f980d0c815bbf7fe329a4efd8da880515ba520b22c0aa3a96514cc', 'Georges', 'GRAND', 'info@maarch.org', 'Y', 'N', 'OK', 'standard');
INSERT INTO users_entities (user_id, entity_id, user_role, primary_entity) VALUES ('ggrand', 'COR', '', 'Y');

-- Create USERGROUP_CONTENT
TRUNCATE TABLE usergroup_content;
DELETE FROM usergroup_content WHERE user_id = 'rrenaud';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('rrenaud', 'RESPONSABLE', 'Y','');
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('rrenaud', 'DIRECTEUR', 'N','');
DELETE FROM usergroup_content WHERE user_id = 'ccordy';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('ccordy', 'AGENT', 'Y','');
DELETE FROM usergroup_content WHERE user_id = 'ssissoko';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('ssissoko', 'RESPONSABLE', 'Y','');
DELETE FROM usergroup_content WHERE user_id = 'nnataly';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('nnataly', 'AGENT', 'Y','');
DELETE FROM usergroup_content WHERE user_id = 'ddur';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('ddur', 'ELU', 'Y','');
DELETE FROM usergroup_content WHERE user_id = 'jjane';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('jjane', 'RESPONSABLE', 'Y','');
DELETE FROM usergroup_content WHERE user_id = 'eerina';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('eerina', 'RESPONSABLE', 'Y','');
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('eerina', 'DIRECTEUR', 'N','');
DELETE FROM usergroup_content WHERE user_id = 'kkaar';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('kkaar', 'AGENT', 'Y','');
DELETE FROM usergroup_content WHERE user_id = 'bboule';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('bboule', 'AGENT', 'Y','');
DELETE FROM usergroup_content WHERE user_id = 'ppetit';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('ppetit', 'RESPONSABLE', 'Y','');
DELETE FROM usergroup_content WHERE user_id = 'aackermann';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('aackermann', 'AGENT', 'Y','');
DELETE FROM usergroup_content WHERE user_id = 'ppruvost';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('ppruvost', 'AGENT', 'Y','');
DELETE FROM usergroup_content WHERE user_id = 'ttong';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('ttong', 'AGENT', 'Y','');
DELETE FROM usergroup_content WHERE user_id = 'sstar';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('sstar', 'RESPONSABLE', 'Y','');
DELETE FROM usergroup_content WHERE user_id = 'ssaporta';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('ssaporta', 'AGENT', 'Y','');
DELETE FROM usergroup_content WHERE user_id = 'ccharles';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('ccharles', 'AGENT', 'Y','');
DELETE FROM usergroup_content WHERE user_id = 'mmanfred';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('mmanfred', 'RESPONSABLE', 'Y','');
DELETE FROM usergroup_content WHERE user_id = 'ddaull';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('ddaull', 'RESP_COURRIER', 'Y','');
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('ddaull', 'RESPONSABLE', 'N','');
DELETE FROM usergroup_content WHERE user_id = 'bbain';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('bbain', 'AGENT', 'Y','');
DELETE FROM usergroup_content WHERE user_id = 'jjonasz';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('jjonasz', 'AGENT', 'Y','');
DELETE FROM usergroup_content WHERE user_id = 'bblier';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('bblier', 'COURRIER', 'Y','');
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('bblier', 'ADMINISTRATEUR', 'N','');
DELETE FROM usergroup_content WHERE user_id = 'ggrand';
INSERT INTO usergroup_content (user_id, group_id, primary_group, role) VALUES ('ggrand', 'ARCHIVISTE', 'Y','');

-- Create ENTITIES and LISTMODELS
TRUNCATE TABLE entities;
TRUNCATE TABLE listmodels;
DELETE FROM entities WHERE entity_id = 'VILLE';
INSERT INTO entities (entity_id, entity_label, short_label, enabled, adrs_1, adrs_2, adrs_3, zipcode, city, country, email, business_id, parent_entity_id, entity_type) VALUES ('VILLE', 'Ville de Maarch-les-bains', 'Ville de Maarch-les-bains', 'Y', '', '', '', '', '', '', 'info@maarch.org', '', '', 'Direction');
DELETE FROM listmodels WHERE object_id = 'VILLE' AND object_type = 'entity_id';
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'VILLE', 'entity_id', 0, '', 'user_id', 'dest', 'DOC', 'Ville de Maarch-les-bains');
DELETE FROM entities WHERE entity_id = 'CAB';
INSERT INTO entities (entity_id, entity_label, short_label, enabled, adrs_1, adrs_2, adrs_3, zipcode, city, country, email, business_id, parent_entity_id, entity_type) VALUES ('CAB', 'Cabinet du Maire', 'Cabinet du Maire', 'Y', '', '', '', '', '', '', 'info@maarch.org', '', 'VILLE', 'Direction');
DELETE FROM listmodels WHERE object_id = 'CAB' AND object_type = 'entity_id';
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'CAB', 'entity_id', 0, 'eerina', 'user_id', 'dest', 'DOC', 'Cabinet du Maire');
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'CAB', 'entity_id', 0, 'DSG', 'entity_id', 'cc', 'DOC', 'Cabinet du Maire');
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'CAB', 'entity_id', 0, 'ppetit', 'user_id', 'cc', 'DOC', 'Cabinet du Maire');
DELETE FROM entities WHERE entity_id = 'DGS';
INSERT INTO entities (entity_id, entity_label, short_label, enabled, adrs_1, adrs_2, adrs_3, zipcode, city, country, email, business_id, parent_entity_id, entity_type) VALUES ('DGS', 'Direction Générale des Services', 'Direction Générale des Services', 'Y', '', '', '', '', '', '', 'info@maarch.org', '', 'VILLE', 'Direction');
DELETE FROM listmodels WHERE object_id = 'DGS' AND object_type = 'entity_id';
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'DGS', 'entity_id', 0, 'rrenaud', 'user_id', 'dest', 'DOC', 'Direction Générale des Services');
DELETE FROM entities WHERE entity_id = 'DGA';
INSERT INTO entities (entity_id, entity_label, short_label, enabled, adrs_1, adrs_2, adrs_3, zipcode, city, country, email, business_id, parent_entity_id, entity_type) VALUES ('DGA', 'Direction Générale Adjointe', 'Direction Générale Adjointe', 'Y', '', '', '', '', '', '', 'info@maarch.org', '', 'DGS', 'Bureau');
DELETE FROM listmodels WHERE object_id = 'DGA' AND object_type = 'entity_id';
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'DGA', 'entity_id', 0, 'mmanfred', 'user_id', 'dest', 'DOC', 'Direction Générale Adjointe');
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'DGA', 'entity_id', 0, 'DSG', 'entity_id', 'cc', 'DOC', 'Direction Générale Adjointe');
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'DGA', 'entity_id', 0, 'kkaar', 'user_id', 'cc', 'DOC', 'Direction Générale Adjointe');
DELETE FROM entities WHERE entity_id = 'PCU';
INSERT INTO entities (entity_id, entity_label, short_label, enabled, adrs_1, adrs_2, adrs_3, zipcode, city, country, email, business_id, parent_entity_id, entity_type) VALUES ('PCU', 'Pôle Culturel', 'Pôle Culturel', 'Y', '', '', '', '', '', '', 'info@maarch.org', '', 'DGA', 'Service');
DELETE FROM listmodels WHERE object_id = 'PCU' AND object_type = 'entity_id';
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'PCU', 'entity_id', 0, 'bboule', 'user_id', 'dest', 'DOC', 'Pôle Culturel');
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'PCU', 'entity_id', 0, 'DSG', 'entity_id', 'cc', 'DOC', 'Pôle Culturel');
DELETE FROM entities WHERE entity_id = 'PJS';
INSERT INTO entities (entity_id, entity_label, short_label, enabled, adrs_1, adrs_2, adrs_3, zipcode, city, country, email, business_id, parent_entity_id, entity_type) VALUES ('PJS', 'Pôle Jeunesse et Sport', 'Pôle Jeunesse et Sport', 'Y', '', '', '', '', '', '', 'info@maarch.org', '', 'DGA', 'Service');
DELETE FROM listmodels WHERE object_id = 'PJS' AND object_type = 'entity_id';
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'PJS', 'entity_id', 0, 'bbain', 'user_id', 'dest', 'DOC', 'Pôle Jeunesse et Sport');
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'PJS', 'entity_id', 0, 'DSG', 'entity_id', 'cc', 'DOC', 'Pôle Jeunesse et Sport');
DELETE FROM entities WHERE entity_id = 'PE';
INSERT INTO entities (entity_id, entity_label, short_label, enabled, adrs_1, adrs_2, adrs_3, zipcode, city, country, email, business_id, parent_entity_id, entity_type) VALUES ('PE', 'Petite enfance', 'Petite enfance', 'Y', '', '', '', '', '', '', 'info@maarch.org', '', 'PJS', 'Service');
DELETE FROM listmodels WHERE object_id = 'PE' AND object_type = 'entity_id';
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'PE', 'entity_id', 0, 'ssaporta', 'user_id', 'dest', 'DOC', 'Petite enfance');
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'PE', 'entity_id', 0, 'DSG', 'entity_id', 'cc', 'DOC', 'Petite enfance');
DELETE FROM entities WHERE entity_id = 'SP';
INSERT INTO entities (entity_id, entity_label, short_label, enabled, adrs_1, adrs_2, adrs_3, zipcode, city, country, email, business_id, parent_entity_id, entity_type) VALUES ('SP', 'Sport', 'Sport', 'Y', '', '', '', '', '', '', 'info@maarch.org', '', 'PJS', 'Service');
DELETE FROM listmodels WHERE object_id = 'SP' AND object_type = 'entity_id';
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'SP', 'entity_id', 0, 'ttong', 'user_id', 'dest', 'DOC', 'Sport');
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'SP', 'entity_id', 0, 'DSG', 'entity_id', 'cc', 'DOC', 'Sport');
DELETE FROM entities WHERE entity_id = 'PSO';
INSERT INTO entities (entity_id, entity_label, short_label, enabled, adrs_1, adrs_2, adrs_3, zipcode, city, country, email, business_id, parent_entity_id, entity_type) VALUES ('PSO', 'Pôle Social', 'Pôle Social', 'Y', '', '', '', '', '', '', 'info@maarch.org', '', 'DGA', 'Service');
DELETE FROM listmodels WHERE object_id = 'PSO' AND object_type = 'entity_id';
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'PSO', 'entity_id', 0, 'nnataly', 'user_id', 'dest', 'DOC', 'Pôle Social');
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'PSO', 'entity_id', 0, 'DSG', 'entity_id', 'cc', 'DOC', 'Pôle Social');
DELETE FROM entities WHERE entity_id = 'PTE';
INSERT INTO entities (entity_id, entity_label, short_label, enabled, adrs_1, adrs_2, adrs_3, zipcode, city, country, email, business_id, parent_entity_id, entity_type) VALUES ('PTE', 'Pôle Technique', 'Pôle Technique', 'Y', '', '', '', '', '', '', 'info@maarch.org', '', 'DGA', 'Service');
DELETE FROM listmodels WHERE object_id = 'PTE' AND object_type = 'entity_id';
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'PTE', 'entity_id', 0, 'ccharles', 'user_id', 'dest', 'DOC', 'Pôle Technique');
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'PTE', 'entity_id', 0, 'DSG', 'entity_id', 'cc', 'DOC', 'Pôle Technique');
DELETE FROM entities WHERE entity_id = 'DRH';
INSERT INTO entities (entity_id, entity_label, short_label, enabled, adrs_1, adrs_2, adrs_3, zipcode, city, country, email, business_id, parent_entity_id, entity_type) VALUES ('DRH', 'Direction des Ressources Humaines', 'Direction des Ressources Humaines', 'Y', '', '', '', '', '', '', 'info@maarch.org', '', 'DGS', 'Service');
DELETE FROM listmodels WHERE object_id = 'DRH' AND object_type = 'entity_id';
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'DRH', 'entity_id', 0, 'ppruvost', 'user_id', 'dest', 'DOC', 'Direction des Ressources Humaines');
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'DRH', 'entity_id', 0, 'DSG', 'entity_id', 'cc', 'DOC', 'Direction des Ressources Humaines');
DELETE FROM entities WHERE entity_id = 'DSG';
INSERT INTO entities (entity_id, entity_label, short_label, enabled, adrs_1, adrs_2, adrs_3, zipcode, city, country, email, business_id, parent_entity_id, entity_type) VALUES ('DSG', 'Secrétariat Général', 'Secrétariat Général', 'Y', '', '', '', '', '', '', 'info@maarch.org', '', 'DGS', 'Direction');
DELETE FROM listmodels WHERE object_id = 'DSG' AND object_type = 'entity_id';
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'DSG', 'entity_id', 0, 'ddaull', 'user_id', 'dest', 'DOC', 'Secrétariat Général');
DELETE FROM entities WHERE entity_id = 'COU';
INSERT INTO entities (entity_id, entity_label, short_label, enabled, adrs_1, adrs_2, adrs_3, zipcode, city, country, email, business_id, parent_entity_id, entity_type) VALUES ('COU', 'Service Courrier', 'Service Courrier', 'Y', '', '', '', '', '', '', 'info@maarch.org', '', 'DSG', 'Service');
DELETE FROM listmodels WHERE object_id = 'COU' AND object_type = 'entity_id';
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'COU', 'entity_id', 0, 'bblier', 'user_id', 'dest', 'DOC', 'Service Courrier');
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'COU', 'entity_id', 0, 'DSG', 'entity_id', 'cc', 'DOC', 'Service Courrier');
DELETE FROM entities WHERE entity_id = 'COR';
INSERT INTO entities (entity_id, entity_label, short_label, enabled, adrs_1, adrs_2, adrs_3, zipcode, city, country, email, business_id, parent_entity_id, entity_type) VALUES ('COR', 'Correspondants Archive', 'Correspondants Archive', 'Y', '', '', '', '', '', '', 'info@maarch.org', '', 'COU', 'Service');
DELETE FROM listmodels WHERE object_id = 'COR' AND object_type = 'entity_id';
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'COR', 'entity_id', 0, '', 'user_id', 'dest', 'DOC', 'Correspondants Archive');
DELETE FROM entities WHERE entity_id = 'PSF';
INSERT INTO entities (entity_id, entity_label, short_label, enabled, adrs_1, adrs_2, adrs_3, zipcode, city, country, email, business_id, parent_entity_id, entity_type) VALUES ('PSF', 'Pôle des Services Fonctionnels', 'Services Fonctionnels', 'Y', '', '', '', '', '', '', 'info@maarch.org', '', 'DSG', 'Service');
DELETE FROM listmodels WHERE object_id = 'PSF' AND object_type = 'entity_id';
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'PSF', 'entity_id', 0, 'aackermann', 'user_id', 'dest', 'DOC', 'Pôle des Services Fonctionnels');
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'PSF', 'entity_id', 0, 'DSG', 'entity_id', 'cc', 'DOC', 'Pôle des Services Fonctionnels');
DELETE FROM entities WHERE entity_id = 'DSI';
INSERT INTO entities (entity_id, entity_label, short_label, enabled, adrs_1, adrs_2, adrs_3, zipcode, city, country, email, business_id, parent_entity_id, entity_type) VALUES ('DSI', 'Direction des Systèmes d''Information', 'Direction des Systèmes d''Information', 'Y', '', '', '', '', '', '', 'info@maarch.org', '', 'DGS', 'Service');
DELETE FROM listmodels WHERE object_id = 'DSI' AND object_type = 'entity_id';
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'DSI', 'entity_id', 0, 'ssissoko', 'user_id', 'dest', 'DOC', 'Direction des Systèmes d''Information');
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'DSI', 'entity_id', 0, 'DSG', 'entity_id', 'cc', 'DOC', 'Direction des Systèmes d''Information');
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'DSI', 'entity_id', 0, 'ccordy', 'user_id', 'cc', 'DOC', 'Direction des Systèmes d''Information');
DELETE FROM entities WHERE entity_id = 'FIN';
INSERT INTO entities (entity_id, entity_label, short_label, enabled, adrs_1, adrs_2, adrs_3, zipcode, city, country, email, business_id, parent_entity_id, entity_type) VALUES ('FIN', 'Direction des Finances', 'Direction des Finances', 'Y', '', '', '', '', '', '', 'info@maarch.org', '', 'DGS', 'Service');
DELETE FROM listmodels WHERE object_id = 'FIN' AND object_type = 'entity_id';
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'FIN', 'entity_id', 0, 'sstar', 'user_id', 'dest', 'DOC', 'Direction des Finances');
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'FIN', 'entity_id', 0, 'DSG', 'entity_id', 'cc', 'DOC', 'Direction des Finances');
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'FIN', 'entity_id', 0, 'jjane', 'user_id', 'cc', 'DOC', 'Direction des Finances');
DELETE FROM entities WHERE entity_id = 'PJU';
INSERT INTO entities (entity_id, entity_label, short_label, enabled, adrs_1, adrs_2, adrs_3, zipcode, city, country, email, business_id, parent_entity_id, entity_type) VALUES ('PJU', 'Pôle Juridique', 'Pôle Juridique', 'Y', '', '', '', '', '', '', 'info@maarch.org', '', 'FIN', 'Service');
DELETE FROM listmodels WHERE object_id = 'PJU' AND object_type = 'entity_id';
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'PJU', 'entity_id', 0, 'jjonasz', 'user_id', 'dest', 'DOC', 'Pôle Juridique');
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'PJU', 'entity_id', 0, 'DSG', 'entity_id', 'cc', 'DOC', 'Pôle Juridique');
DELETE FROM entities WHERE entity_id = 'ELUS';
INSERT INTO entities (entity_id, entity_label, short_label, enabled, adrs_1, adrs_2, adrs_3, zipcode, city, country, email, business_id, parent_entity_id, entity_type) VALUES ('ELUS', 'Ensemble des élus', 'ELUS:Ensemble des élus', 'Y', '', '', '', '', '', '', 'info@maarch.org', '', 'VILLE', 'Direction');
DELETE FROM listmodels WHERE object_id = 'ELUS' AND object_type = 'entity_id';
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'ELUS', 'entity_id', 0, '', 'user_id', 'dest', 'DOC', 'Ensemble des élus');
DELETE FROM entities WHERE entity_id = 'CCAS';
INSERT INTO entities (entity_id, entity_label, short_label, enabled, adrs_1, adrs_2, adrs_3, zipcode, city, country, email, business_id, parent_entity_id, entity_type) VALUES ('CCAS', 'Centre Communal d''Action Sociale', 'Centre Communal d''Action Sociale', 'Y', '', '', '', '', '', '', 'info@maarch.org', '', '', 'Direction');
DELETE FROM listmodels WHERE object_id = 'CCAS' AND object_type = 'entity_id';
INSERT INTO listmodels (coll_id, object_id, object_type, "sequence", item_id, item_type, item_mode, listmodel_type, description) VALUES ('letterbox_coll', 'CCAS', 'entity_id', 0, '', 'user_id', 'dest', 'DOC', 'Centre Communal d''Action Sociale');

-- Create BASKETS
TRUNCATE TABLE baskets;
DELETE FROM baskets WHERE basket_id = 'QualificationBasket';
DELETE FROM actions_groupbaskets WHERE basket_id = 'QualificationBasket';
DELETE FROM groupbasket_redirect WHERE basket_id = 'QualificationBasket';
INSERT INTO baskets (basket_id, basket_name, basket_desc, basket_clause, coll_id, is_visible, is_folder_basket, enabled, basket_order) VALUES ('QualificationBasket', 'Courriers à qualifier', 'Corbeille de qualification', 'status=''INIT''', 'letterbox_coll', 'Y', 'N', 'Y',10);
DELETE FROM baskets WHERE basket_id = 'IndexingBasket';
DELETE FROM actions_groupbaskets WHERE basket_id = 'IndexingBasket';
DELETE FROM groupbasket_redirect WHERE basket_id = 'IndexingBasket';
INSERT INTO baskets (basket_id, basket_name, basket_desc, basket_clause, coll_id, is_visible, is_folder_basket, enabled, basket_order) VALUES ('IndexingBasket', 'Courriers à indexer', 'Corbeille d''indexation', ' ', 'letterbox_coll', 'Y', 'N', 'Y',20);
DELETE FROM baskets WHERE basket_id = 'CopyMailBasket';
DELETE FROM actions_groupbaskets WHERE basket_id = 'CopyMailBasket';
DELETE FROM groupbasket_redirect WHERE basket_id = 'CopyMailBasket';
INSERT INTO baskets (basket_id, basket_name, basket_desc, basket_clause, coll_id, is_visible, is_folder_basket, enabled, basket_order) VALUES ('CopyMailBasket', 'Courriers en copie', 'Courriers en copie non clos ou sans suite', '(res_id in (select res_id from listinstance WHERE coll_id = ''letterbox_coll'' and item_type = ''user_id'' and item_id = @user and item_mode = ''cc'') or res_id in (select res_id from listinstance WHERE coll_id = ''letterbox_coll'' and item_type = ''entity_id'' and item_mode = ''cc'' and item_id in (@my_entities))) and status not in ( ''DEL'', ''END'', ''SSUITE'') and res_id not in (select res_id from res_mark_as_read WHERE user_id = @user)', 'letterbox_coll', 'Y', 'N', 'Y',30);
DELETE FROM baskets WHERE basket_id = 'RetourCourrier';
DELETE FROM actions_groupbaskets WHERE basket_id = 'RetourCourrier';
DELETE FROM groupbasket_redirect WHERE basket_id = 'RetourCourrier';
INSERT INTO baskets (basket_id, basket_name, basket_desc, basket_clause, coll_id, is_visible, is_folder_basket, enabled, basket_order) VALUES ('RetourCourrier', 'Retours Courrier', 'Courriers retournés au service Courrier', 'STATUS=''RET''', 'letterbox_coll', 'Y', 'N', 'Y',40);
DELETE FROM baskets WHERE basket_id = 'DdeAvisBasket';
DELETE FROM actions_groupbaskets WHERE basket_id = 'DdeAvisBasket';
DELETE FROM groupbasket_redirect WHERE basket_id = 'DdeAvisBasket';
INSERT INTO baskets (basket_id, basket_name, basket_desc, basket_clause, coll_id, is_visible, is_folder_basket, enabled, basket_order) VALUES ('DdeAvisBasket', 'Avis : Avis à émettre', 'Courriers nécessitant un avis', 'status = ''EAVIS'' AND res_id IN (SELECT res_id FROM listinstance WHERE coll_id = ''letterbox_coll'' AND item_type = ''user_id'' AND item_id = @user AND item_mode = ''avis'' and process_date is NULL)', 'letterbox_coll', 'Y', 'N', 'Y',50);
DELETE FROM baskets WHERE basket_id = 'SupAvisBasket';
DELETE FROM actions_groupbaskets WHERE basket_id = 'SupAvisBasket';
DELETE FROM groupbasket_redirect WHERE basket_id = 'SupAvisBasket';
INSERT INTO baskets (basket_id, basket_name, basket_desc, basket_clause, coll_id, is_visible, is_folder_basket, enabled, basket_order) VALUES ('SupAvisBasket', 'Avis : En attente de réponse', 'Courriers en attente d''avis', 'status=''EAVIS'' and ((DEST_USER = @user) OR (DEST_USER IN (select user_id from users_entities WHERE entity_id IN( @my_entities)) or DESTINATION in (@subentities[@my_entities]))) and res_id NOT IN (SELECT res_id FROM listinstance WHERE item_mode = ''avis'' and difflist_type = ''entity_id'' and process_date is not NULL and res_view_letterbox.res_id = res_id group by res_id) AND res_id IN (SELECT res_id FROM listinstance WHERE item_mode = ''avis'' and difflist_type = ''entity_id'' and process_date is NULL and res_view_letterbox.res_id = res_id group by res_id)', 'letterbox_coll', 'Y', 'N', 'Y',60);
DELETE FROM baskets WHERE basket_id = 'RetAvisBasket';
DELETE FROM actions_groupbaskets WHERE basket_id = 'RetAvisBasket';
DELETE FROM groupbasket_redirect WHERE basket_id = 'RetAvisBasket';
INSERT INTO baskets (basket_id, basket_name, basket_desc, basket_clause, coll_id, is_visible, is_folder_basket, enabled, basket_order) VALUES ('RetAvisBasket', 'Avis : Retours partiels', 'Courriers avec avis reçus', 'status=''EAVIS'' and ((DEST_USER = @user) OR (DEST_USER IN (select user_id from users_entities WHERE entity_id IN( @my_entities)) or DESTINATION in (@subentities[@my_entities]))) and res_id IN (SELECT res_id FROM listinstance WHERE item_mode = ''avis'' and difflist_type = ''entity_id'' and process_date is not NULL and res_view_letterbox.res_id = res_id group by res_id)', 'letterbox_coll', 'Y', 'N', 'Y',70);
DELETE FROM baskets WHERE basket_id = 'ValidationBasket';
DELETE FROM actions_groupbaskets WHERE basket_id = 'ValidationBasket';
DELETE FROM groupbasket_redirect WHERE basket_id = 'ValidationBasket';
INSERT INTO baskets (basket_id, basket_name, basket_desc, basket_clause, coll_id, is_visible, is_folder_basket, enabled, basket_order) VALUES ('ValidationBasket', 'Courriers signalés à instruire', 'Courriers signalés en attente d''instruction pour les services', 'status=''VAL''', 'letterbox_coll', 'Y', 'N', 'Y',80);
DELETE FROM baskets WHERE basket_id = 'InValidationBasket';
DELETE FROM actions_groupbaskets WHERE basket_id = 'InValidationBasket';
DELETE FROM groupbasket_redirect WHERE basket_id = 'InValidationBasket';
INSERT INTO baskets (basket_id, basket_name, basket_desc, basket_clause, coll_id, is_visible, is_folder_basket, enabled, basket_order) VALUES ('InValidationBasket', 'Courriers signalés en attente d''instruction', 'Courriers signalés en attente d''instruction par le responsable', 'destination in (@my_entities, @subentities[@my_entities]) and status=''VAL''', 'letterbox_coll', 'Y', 'N', 'Y',90);
DELETE FROM baskets WHERE basket_id = 'MyBasket';
DELETE FROM actions_groupbaskets WHERE basket_id = 'MyBasket';
DELETE FROM groupbasket_redirect WHERE basket_id = 'MyBasket';
INSERT INTO baskets (basket_id, basket_name, basket_desc, basket_clause, coll_id, is_visible, is_folder_basket, enabled, basket_order, flag_notif) VALUES ('MyBasket', 'Courriers à traiter', 'Corbeille de traitement', 'status in (''NEW'', ''COU'', ''SVX'') and dest_user = @user', 'letterbox_coll', 'Y', 'N', 'Y',100, 'Y');
DELETE FROM baskets WHERE basket_id = 'LateMailBasket';
DELETE FROM actions_groupbaskets WHERE basket_id = 'LateMailBasket';
DELETE FROM groupbasket_redirect WHERE basket_id = 'LateMailBasket';
INSERT INTO baskets (basket_id, basket_name, basket_desc, basket_clause, coll_id, is_visible, is_folder_basket, enabled, basket_order) VALUES ('LateMailBasket', 'Courriers en retard', 'Courriers en retard', 'destination in (@my_entities, @subentities[@my_primary_entity]) and (status <> ''DEL'' AND status <> ''REP'') and (now() > process_limit_date)', 'letterbox_coll', 'Y', 'N', 'Y',110);
DELETE FROM baskets WHERE basket_id = 'DepartmentBasket';
DELETE FROM actions_groupbaskets WHERE basket_id = 'DepartmentBasket';
DELETE FROM groupbasket_redirect WHERE basket_id = 'DepartmentBasket';
INSERT INTO baskets (basket_id, basket_name, basket_desc, basket_clause, coll_id, is_visible, is_folder_basket, enabled, basket_order) VALUES ('DepartmentBasket', 'Courriers de ma direction', 'Corbeille de supervision', 'destination in (@my_entities, @subentities[@my_primary_entity]) and (status <> ''DEL'' AND status <> ''REP'' and status <> ''VAL'')', 'letterbox_coll', 'Y', 'N', 'Y',120);
DELETE FROM baskets WHERE basket_id = 'EvisBasket';
DELETE FROM actions_groupbaskets WHERE basket_id = 'EvisBasket';
DELETE FROM groupbasket_redirect WHERE basket_id = 'EvisBasket';
INSERT INTO baskets (basket_id, basket_name, basket_desc, basket_clause, coll_id, is_visible, is_folder_basket, enabled, basket_order) VALUES ('EvisBasket', 'Courriers à e-viser', 'Courriers à e-viser', 'status=''EVIS'' and (res_id,@user) IN (SELECT res_id, item_id FROM listinstance WHERE item_mode = ''visa'' and process_date ISNULL and res_view_letterbox.res_id = res_id order by listinstance_id asc limit 1)', 'letterbox_coll', 'Y', 'N', 'Y',130);
DELETE FROM baskets WHERE basket_id = 'EsigBasket';
DELETE FROM actions_groupbaskets WHERE basket_id = 'EsigBasket';
DELETE FROM groupbasket_redirect WHERE basket_id = 'EsigBasket';
INSERT INTO baskets (basket_id, basket_name, basket_desc, basket_clause, coll_id, is_visible, is_folder_basket, enabled, basket_order) VALUES ('EsigBasket', 'Courriers à e-signer', 'Courriers à e-signer', 'status=''ESIG'' and (res_id,@user) IN (SELECT res_id, item_id FROM listinstance WHERE item_mode = ''sign'' and process_date ISNULL and res_view_letterbox.res_id = res_id order by listinstance_id asc limit 1)', 'letterbox_coll', 'Y', 'N', 'Y',140);
DELETE FROM baskets WHERE basket_id = 'EsigARBasket';
DELETE FROM actions_groupbaskets WHERE basket_id = 'EsigARBasket';
DELETE FROM groupbasket_redirect WHERE basket_id = 'EsigARBasket';
INSERT INTO baskets (basket_id, basket_name, basket_desc, basket_clause, coll_id, is_visible, is_folder_basket, enabled, basket_order) VALUES ('EsigARBasket', 'AR à e-signer', 'AR à e-signer', 'status=''ESIGAR'' and (res_id,@user) IN (SELECT res_id, item_id FROM listinstance WHERE item_mode = ''sign'' and process_date ISNULL and res_view_letterbox.res_id = res_id order by listinstance_id asc limit 1)', 'letterbox_coll', 'Y', 'N', 'Y',150);
DELETE FROM baskets WHERE basket_id = 'EenvBasket';
DELETE FROM actions_groupbaskets WHERE basket_id = 'EenvBasket';
DELETE FROM groupbasket_redirect WHERE basket_id = 'EenvBasket';
INSERT INTO baskets (basket_id, basket_name, basket_desc, basket_clause, coll_id, is_visible, is_folder_basket, enabled, basket_order) VALUES ('EenvBasket', 'Courriers à e-envoyer', 'Courriers à e-envoyer', 'status=''EENV'' and dest_user = @user', 'letterbox_coll', 'Y', 'N', 'Y',160);
DELETE FROM baskets WHERE basket_id = 'EenvARBasket';
DELETE FROM actions_groupbaskets WHERE basket_id = 'EenvARBasket';
DELETE FROM groupbasket_redirect WHERE basket_id = 'EenvARBasket';
INSERT INTO baskets (basket_id, basket_name, basket_desc, basket_clause, coll_id, is_visible, is_folder_basket, enabled, basket_order) VALUES ('EenvARBasket', 'AR à e-envoyer', 'AR à e-envoyer', 'status=''EENVAR'' and dest_user = @user', 'letterbox_coll', 'Y', 'N', 'Y',170);
DELETE FROM baskets WHERE basket_id = 'ToArcBasket';
DELETE FROM actions_groupbaskets WHERE basket_id = 'ToArcBasket';
DELETE FROM groupbasket_redirect WHERE basket_id = 'ToArcBasket';
INSERT INTO baskets (basket_id, basket_name, basket_desc, basket_clause, coll_id, is_visible, is_folder_basket, enabled, basket_order) VALUES ('ToArcBasket', 'Courriers à archiver', 'Courriers arrivés en fin de DUC à envoyer en archive intermédiaire', 'status = ''EXP_SEDA'' OR status = ''END'' OR status = ''SEND_SEDA''', 'letterbox_coll', 'Y', 'N', 'Y',180);
DELETE FROM baskets WHERE basket_id = 'SentArcBasket';
DELETE FROM actions_groupbaskets WHERE basket_id = 'SentArcBasket';
DELETE FROM groupbasket_redirect WHERE basket_id = 'SentArcBasket';
INSERT INTO baskets (basket_id, basket_name, basket_desc, basket_clause, coll_id, is_visible, is_folder_basket, enabled, basket_order) VALUES ('SentArcBasket', 'Courriers en cours d''archivage', 'Courriers envoyés au SAE, en attente de réponse de transfert', 'status=''ACK_SEDA''', 'letterbox_coll', 'Y', 'N', 'Y',190);
DELETE FROM baskets WHERE basket_id = 'AckArcBasket';
DELETE FROM actions_groupbaskets WHERE basket_id = 'AckArcBasket';
DELETE FROM groupbasket_redirect WHERE basket_id = 'AckArcBasket';
INSERT INTO baskets (basket_id, basket_name, basket_desc, basket_clause, coll_id, is_visible, is_folder_basket, enabled, basket_order) VALUES ('AckArcBasket', 'Courriers archivés', 'Courriers archivés et acceptés dans le SAE', 'status=''REPLY_SEDA''', 'letterbox_coll', 'Y', 'N', 'Y',200);

-- Create GROUPBASKET
TRUNCATE TABLE groupbasket;
DELETE FROM groupbasket WHERE basket_id = 'QualificationBasket';
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('COURRIER', 'QualificationBasket', 1, NULL, NULL, 'list_with_attachments','N', 'N', 'N', NULL, NULL);
DELETE FROM groupbasket WHERE basket_id = 'IndexingBasket';
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('COURRIER', 'IndexingBasket', 2, NULL, NULL, 'redirect_to_action','N', 'N', 'N', NULL, NULL);
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('AGENT', 'IndexingBasket', 3, NULL, NULL, 'redirect_to_action','N', 'N', 'N', NULL, NULL);
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('RESP_COURRIER', 'IndexingBasket', 4, NULL, NULL, 'redirect_to_action','N', 'N', 'N', NULL, NULL);
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('RESPONSABLE', 'IndexingBasket', 5, NULL, NULL, 'redirect_to_action','N', 'N', 'N', NULL, NULL);
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('ELU', 'IndexingBasket', 6, NULL, NULL, 'redirect_to_action','N', 'N', 'N', NULL, NULL);
DELETE FROM groupbasket WHERE basket_id = 'CopyMailBasket';
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('AGENT', 'CopyMailBasket', 7, NULL, NULL, 'list_copies','N', 'N', 'N', NULL, NULL);
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('RESP_COURRIER', 'CopyMailBasket', 8, NULL, NULL, 'list_copies','N', 'N', 'N', NULL, NULL);
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('RESPONSABLE', 'CopyMailBasket', 9, NULL, NULL, 'list_copies','N', 'N', 'N', NULL, NULL);
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('ELU', 'CopyMailBasket', 10, NULL, NULL, 'list_copies','N', 'N', 'N', NULL, NULL);
DELETE FROM groupbasket WHERE basket_id = 'RetourCourrier';
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('COURRIER', 'RetourCourrier', 11, NULL, NULL, 'list_with_attachments','N', 'N', 'N', NULL, NULL);
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('RESP_COURRIER', 'RetourCourrier', 12, NULL, NULL, 'list_with_attachments','N', 'N', 'N', NULL, NULL);
DELETE FROM groupbasket WHERE basket_id = 'DdeAvisBasket';
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('AGENT', 'DdeAvisBasket', 13, NULL, NULL, 'list_with_avis','N', 'N', 'N', NULL, NULL);
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('RESP_COURRIER', 'DdeAvisBasket', 14, NULL, NULL, 'list_with_avis','N', 'N', 'N', NULL, NULL);
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('RESPONSABLE', 'DdeAvisBasket', 15, NULL, NULL, 'list_with_avis','N', 'N', 'N', NULL, NULL);
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('ELU', 'DdeAvisBasket', 16, NULL, NULL, 'list_with_avis','N', 'N', 'N', NULL, NULL);
DELETE FROM groupbasket WHERE basket_id = 'SupAvisBasket';
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('AGENT', 'SupAvisBasket', 17, NULL, NULL, 'list_with_avis','N', 'N', 'N', NULL, NULL);
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('RESP_COURRIER', 'SupAvisBasket', 18, NULL, NULL, 'list_with_avis','N', 'N', 'N', NULL, NULL);
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('RESPONSABLE', 'SupAvisBasket', 19, NULL, NULL, 'list_with_avis','N', 'N', 'N', NULL, NULL);
DELETE FROM groupbasket WHERE basket_id = 'RetAvisBasket';
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('AGENT', 'RetAvisBasket', 20, NULL, NULL, 'list_with_avis','N', 'N', 'N', NULL, NULL);
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('RESP_COURRIER', 'RetAvisBasket', 21, NULL, NULL, 'list_with_avis','N', 'N', 'N', NULL, NULL);
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('RESPONSABLE', 'RetAvisBasket', 22, NULL, NULL, 'list_with_avis','N', 'N', 'N', NULL, NULL);
DELETE FROM groupbasket WHERE basket_id = 'ValidationBasket';
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('RESP_COURRIER', 'ValidationBasket', 23, NULL, NULL, 'list_with_attachments','N', 'N', 'N', NULL, NULL);
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('DIRECTEUR', 'ValidationBasket', 24, NULL, NULL, 'list_with_attachments','N', 'N', 'N', NULL, NULL);
DELETE FROM groupbasket WHERE basket_id = 'InValidationBasket';
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('AGENT', 'InValidationBasket', 25, NULL, NULL, 'list_with_attachments','N', 'N', 'N', NULL, NULL);
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('RESPONSABLE', 'InValidationBasket', 26, NULL, NULL, 'list_with_attachments','N', 'N', 'N', NULL, NULL);
DELETE FROM groupbasket WHERE basket_id = 'MyBasket';
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('AGENT', 'MyBasket', 27, NULL, NULL, 'list_with_attachments','N', 'N', 'N', NULL, NULL);
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('RESP_COURRIER', 'MyBasket', 28, NULL, NULL, 'list_with_attachments','N', 'N', 'N', NULL, NULL);
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('RESPONSABLE', 'MyBasket', 29, NULL, NULL, 'list_with_attachments','N', 'N', 'N', NULL, NULL);
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('ELU', 'MyBasket', 30, NULL, NULL, 'list_with_attachments','N', 'N', 'N', NULL, NULL);
DELETE FROM groupbasket WHERE basket_id = 'LateMailBasket';
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('AGENT', 'LateMailBasket', 31, NULL, NULL, 'list_with_attachments','N', 'N', 'N', NULL, NULL);
DELETE FROM groupbasket WHERE basket_id = 'DepartmentBasket';
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('RESPONSABLE', 'DepartmentBasket', 32, NULL, NULL, 'list_with_attachments','N', 'N', 'N', NULL, NULL);
DELETE FROM groupbasket WHERE basket_id = 'EvisBasket';
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('RESPONSABLE', 'EvisBasket', 33, NULL, NULL, 'list_with_attachments','N', 'N', 'N', NULL, NULL);
DELETE FROM groupbasket WHERE basket_id = 'EsigBasket';
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('RESPONSABLE', 'EsigBasket', 34, NULL, NULL, 'list_with_attachments','N', 'N', 'N', NULL, NULL);
DELETE FROM groupbasket WHERE basket_id = 'EsigARBasket';
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('RESPONSABLE', 'EsigARBasket', 35, NULL, NULL, 'list_with_attachments','N', 'N', 'N', NULL, NULL);
DELETE FROM groupbasket WHERE basket_id = 'EenvBasket';
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('AGENT', 'EenvBasket', 36, NULL, NULL, 'list_with_attachments','N', 'N', 'N', NULL, NULL);
DELETE FROM groupbasket WHERE basket_id = 'EenvARBasket';
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('AGENT', 'EenvARBasket', 37, NULL, NULL, 'list_with_attachments','N', 'N', 'N', NULL, NULL);
DELETE FROM groupbasket WHERE basket_id = 'ToArcBasket';
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('ARCHIVISTE', 'ToArcBasket', 38, NULL, NULL, 'list_with_attachments','N', 'N', 'N', NULL, NULL);
DELETE FROM groupbasket WHERE basket_id = 'SentArcBasket';
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('ARCHIVISTE', 'SentArcBasket', 39, NULL, NULL, 'list_with_attachments','N', 'N', 'N', NULL, NULL);
DELETE FROM groupbasket WHERE basket_id = 'AckArcBasket';
INSERT INTO groupbasket (group_id, basket_id, sequence, redirect_basketlist, redirect_grouplist, result_page, can_redirect, can_delete, can_insert, list_lock_clause, sublist_lock_clause) VALUES ('ARCHIVISTE', 'AckArcBasket', 40, NULL, NULL, 'list_with_attachments','N', 'N', 'N', NULL, NULL);


-- Create Security
TRUNCATE TABLE security;
DELETE FROM security WHERE group_id = 'COURRIER';
INSERT INTO security (group_id, coll_id, where_clause, maarch_comment, can_insert, can_update, can_delete, rights_bitmask, mr_start_date, mr_stop_date, where_target) VALUES ('COURRIER', 'letterbox_coll', 'typist=@user', 'Les courriers que j''ai numérisé','N','N','N', 25, NULL, NULL, 'DOC');
DELETE FROM security WHERE group_id = 'AGENT';
INSERT INTO security (group_id, coll_id, where_clause, maarch_comment, can_insert, can_update, can_delete, rights_bitmask, mr_start_date, mr_stop_date, where_target) VALUES ('AGENT', 'letterbox_coll', 'destination in (@my_entities, @subentities[@my_entities])', 'Les courriers de mes services et sous-services','N','N','N', 25, NULL, NULL, 'DOC');
DELETE FROM security WHERE group_id = 'RESP_COURRIER';
INSERT INTO security (group_id, coll_id, where_clause, maarch_comment, can_insert, can_update, can_delete, rights_bitmask, mr_start_date, mr_stop_date, where_target) VALUES ('RESP_COURRIER', 'letterbox_coll', '1=1', 'Tous les courriers','N','N','N', 9, NULL, NULL, 'DOC');
DELETE FROM security WHERE group_id = 'RESPONSABLE';
INSERT INTO security (group_id, coll_id, where_clause, maarch_comment, can_insert, can_update, can_delete, rights_bitmask, mr_start_date, mr_stop_date, where_target) VALUES ('RESPONSABLE', 'letterbox_coll', 'destination in (@my_entities, @subentities[@my_entities])', 'Les courriers de mes services et sous-services','N','N','N', 25, NULL, NULL, 'DOC');
DELETE FROM security WHERE group_id = 'ADMINISTRATEUR';
INSERT INTO security (group_id, coll_id, where_clause, maarch_comment, can_insert, can_update, can_delete, rights_bitmask, mr_start_date, mr_stop_date, where_target) VALUES ('ADMINISTRATEUR', 'letterbox_coll', '1=1', 'Tous les courriers','N','N','N', 24, NULL, NULL, 'DOC');
DELETE FROM security WHERE group_id = 'DIRECTEUR';
INSERT INTO security (group_id, coll_id, where_clause, maarch_comment, can_insert, can_update, can_delete, rights_bitmask, mr_start_date, mr_stop_date, where_target) VALUES ('DIRECTEUR', 'letterbox_coll', '1=1', 'Tous les courriers','N','N','N', 25, NULL, NULL, 'DOC');
DELETE FROM security WHERE group_id = 'ELU';
INSERT INTO security (group_id, coll_id, where_clause, maarch_comment, can_insert, can_update, can_delete, rights_bitmask, mr_start_date, mr_stop_date, where_target) VALUES ('ELU', 'letterbox_coll', 'destination in (@my_entities, @subentities[@my_entities])', 'Les courriers de mes services et sous-services','N','N','N', 0, NULL, NULL, 'DOC');
DELETE FROM security WHERE group_id = 'ARCHIVISTE';
INSERT INTO security (group_id, coll_id, where_clause, maarch_comment, can_insert, can_update, can_delete, rights_bitmask, mr_start_date, mr_stop_date, where_target) VALUES ('ARCHIVISTE', 'letterbox_coll', '1=1', 'Tous les courriers','N','N','N', 0, NULL, NULL, 'DOC');

-- Donnees manuelles
------------
--DOCSERVERS
------------
TRUNCATE TABLE docserver_locations;
TRUNCATE TABLE docserver_types;
TRUNCATE TABLE docservers;
INSERT INTO docserver_locations (docserver_location_id, ipv4, ipv6, net_domain, mask, net_link, enabled) VALUES ('NANTERRE', '127.0.0.1', '', 'MAARCH', '255.255.255.0', NULL, 'Y');
INSERT INTO docserver_locations (docserver_location_id, ipv4, ipv6, net_domain, mask, net_link, enabled) VALUES ('NICE', '192.168.21.63', '', '', '', NULL, 'Y');
INSERT INTO docserver_types (docserver_type_id, docserver_type_label, enabled, is_container, container_max_number, is_compressed, compression_mode, is_meta, meta_template, is_logged, log_template, is_signed, fingerprint_mode) VALUES ('FASTHD', 'FASTHD', 'Y', 'N', 0, 'N', 'NONE', 'N', 'NONE', 'N', 'NONE', 'Y', 'SHA256');
INSERT INTO docserver_types (docserver_type_id, docserver_type_label, enabled, is_container, container_max_number, is_compressed, compression_mode, is_meta, meta_template, is_logged, log_template, is_signed, fingerprint_mode) VALUES ('TEMPLATES', 'TEMPLATES', 'Y', 'N', 0, 'N', 'NONE', 'N', 'NONE', 'N', 'NONE', 'N', 'NONE');
INSERT INTO docserver_types (docserver_type_id, docserver_type_label, enabled, is_container, container_max_number, is_compressed, compression_mode, is_meta, meta_template, is_logged, log_template, is_signed, fingerprint_mode) VALUES ('OAIS_MAIN', 'Main OAIS store', 'Y', 'Y', 100, 'Y', '7Z', 'Y', 'OAIS_std.dtd', 'Y', 'log_std.dtd', 'Y', 'SHA512');
INSERT INTO docserver_types (docserver_type_id, docserver_type_label, enabled, is_container, container_max_number, is_compressed, compression_mode, is_meta, meta_template, is_logged, log_template, is_signed, fingerprint_mode) VALUES ('OFFLINE', 'Off line tape', 'Y', 'Y', 1000, 'Y', '7Z', 'Y', 'OAIS_std.dtd', 'Y', 'log_std.dtd', 'Y', 'SHA512');
INSERT INTO docserver_types (docserver_type_id, docserver_type_label, enabled, is_container, container_max_number, is_compressed, compression_mode, is_meta, meta_template, is_logged, log_template, is_signed, fingerprint_mode) VALUES ('OAIS_SAFE', 'Distant backup OAIS store', 'Y', 'Y', 20, 'Y', 'ZIP', 'Y', 'OAIS_std.dtd', 'Y', 'log_std.dtd', 'Y', 'SHA512');
INSERT INTO docserver_types (docserver_type_id, docserver_type_label, enabled, is_container, container_max_number, is_compressed, compression_mode, is_meta, meta_template, is_logged, log_template, is_signed, fingerprint_mode) VALUES ('TNL', 'Thumbnails', 'Y', 'N', 0, 'N', 'NONE', 'N', 'NONE', 'N', 'NONE', 'Y', 'NONE');
INSERT INTO docservers (docserver_id, docserver_type_id, device_label, is_readonly, enabled, size_limit_number, actual_size_number, path_template, ext_docserver_info, chain_before, chain_after, creation_date, closing_date, coll_id, priority_number, docserver_location_id, adr_priority_number) VALUES ('FASTHD_AI', 'FASTHD', 'Fast internal disc bay for autoimport', 'N', 'Y', 50000000000, 1, '/opt/maarch/docservers/ai/', NULL, NULL, NULL, '2011-01-07 13:43:48.696644', NULL, 'letterbox_coll', 11, 'NANTERRE', 1);
INSERT INTO docservers (docserver_id, docserver_type_id, device_label, is_readonly, enabled, size_limit_number, actual_size_number, path_template, ext_docserver_info, chain_before, chain_after, creation_date, closing_date, coll_id, priority_number, docserver_location_id, adr_priority_number) VALUES ('TNL', 'TNL', 'Server for thumbnails of documents', 'N', 'Y', 50000000000, 0, '/opt/maarch/docservers/thumbnails_mlb/', NULL, NULL, NULL, '2015-03-16 14:47:49.197164', NULL, 'letterbox_coll', 11, 'NANTERRE', 3);
INSERT INTO docservers (docserver_id, docserver_type_id, device_label, is_readonly, enabled, size_limit_number, actual_size_number, path_template, ext_docserver_info, chain_before, chain_after, creation_date, closing_date, coll_id, priority_number, docserver_location_id, adr_priority_number) VALUES ('TEMPLATES', 'TEMPLATES', '[system] Templates', 'N', 'Y', 50000000000, 71511, '/opt/maarch/docservers/templates/', NULL, NULL, NULL, '2012-04-01 14:49:05.095119', NULL, 'templates', 1, 'NANTERRE', 1);
INSERT INTO docservers (docserver_id, docserver_type_id, device_label, is_readonly, enabled, size_limit_number, actual_size_number, path_template, ext_docserver_info, chain_before, chain_after, creation_date, closing_date, coll_id, priority_number, docserver_location_id, adr_priority_number) VALUES ('FASTHD_MAN', 'FASTHD', 'Fast internal disc bay for letterbox mode', 'N', 'Y', 50000000000, 1290730, '/opt/maarch/docservers/manual/', NULL, NULL, NULL, '2011-01-13 14:47:49.197164', NULL, 'letterbox_coll', 10, 'NANTERRE', 2);
------------
--SUPERADMIN USER
------------
DELETE FROM users WHERE user_id='superadmin';
INSERT INTO users (user_id, password, firstname, lastname, phone, mail, department, custom_t1, custom_t2, custom_t3, cookie_key, cookie_date, enabled, change_password, delay_number, status, loginmode, docserver_location_id) VALUES ('superadmin', '964a5502faec7a27f63ab5f7bddbe1bd8a685616a90ffcba633b5ad404569bd8fed4693cc00474a4881f636f3831a3e5a36bda049c568a89cfe54b1285b0c13e', 'Super', 'ADMIN', '0147245159', 'info@maarch.org', 'Maarch', '11', NULL, NULL, 'e657b3542b0362910db9195cb0fd0fb5', '2012-02-28 10:02:08', 'Y', 'N', NULL, 'OK', 'standard', NULL);
------------
-- CONTACTS
------------
TRUNCATE TABLE contact_types;
INSERT INTO contact_types (id, label, can_add_contact, contact_target) VALUES (100, '1. Entreprises', 'Y', 'corporate');
INSERT INTO contact_types (id, label, can_add_contact, contact_target) VALUES (101, '2. Associations', 'Y', 'both');
INSERT INTO contact_types (id, label, can_add_contact, contact_target) VALUES (102, '3. Administrations', 'Y', 'corporate');
INSERT INTO contact_types (id, label, can_add_contact, contact_target) VALUES (103, '4. Collectivités territoriales', 'Y', 'corporate');
INSERT INTO contact_types (id, label, can_add_contact, contact_target) VALUES (104, '5. Autorités juridictionnelles', 'Y', 'corporate');
INSERT INTO contact_types (id, label, can_add_contact, contact_target) VALUES (105, '6. Organisations syndicales', 'Y', 'corporate');
INSERT INTO contact_types (id, label, can_add_contact, contact_target) VALUES (106, '0. Particuliers', 'Y', 'no_corporate');
INSERT INTO contact_types (id, label, can_add_contact, contact_target) VALUES (107, '7. Banques', 'Y', 'corporate');
INSERT INTO contact_types (id, label, can_add_contact, contact_target) VALUES (108, '8. CCI', 'Y', 'corporate');
select setval('contact_types_id_seq', (select max(id)+1 from contact_types), false);
TRUNCATE TABLE contact_purposes;
INSERT INTO contact_purposes (id, label) VALUES (1, 'Siège social France');
INSERT INTO contact_purposes (id, label) VALUES (2, 'Siège social Sénégal');
INSERT INTO contact_purposes (id, label) VALUES (3, 'Adresse principale');
Select setval('contact_purposes_id_seq', (select max(id)+1 from contact_purposes), false);
TRUNCATE TABLE contacts_v2;
INSERT INTO contacts_v2 (contact_id, contact_type, is_corporate_person, society, society_short, firstname, lastname, title, function, other_data, user_id, entity_id, creation_date, update_date, enabled) VALUES (1, 100, 'Y', 'MAARCH', '', '', '', '', '', 'Editeur du logiciel libre Maarch', 'bblier', 'VILLE', '2015-04-24 12:43:54.97424', '2016-07-25 16:28:38.498185', 'Y');
Select setval('contact_v2_id_seq', (select max(contact_id)+1 from contacts_v2), false);
-- Default adresses
TRUNCATE TABLE contact_addresses;
INSERT INTO contact_addresses (id, contact_id, contact_purpose_id, departement, firstname, lastname, title, function, occupancy, address_num, address_street, address_complement, address_town, address_postal_code, address_country, phone, email, website, salutation_header, salutation_footer, other_data, user_id, entity_id, is_private, enabled) VALUES (1, 1, 1, '', 'Jean-Louis', 'ERCOLANI', 'title1', 'Président', '', '11', 'Boulevard du Sud-Est', '92000', 'NANTERRE', 'Nanterre', '', '', 'jeanlouis.ercolani@maarch.org', 'http://www.maarch.com', '', '', '', 'bblier', 'VILLE', 'N', 'Y');
INSERT INTO contact_addresses (id, contact_id, contact_purpose_id, departement, firstname, lastname, title, function, occupancy, address_num, address_street, address_complement, address_town, address_postal_code, address_country, phone, email, website, salutation_header, salutation_footer, other_data, user_id, entity_id, is_private, enabled) VALUES (2, 1, 2, '', 'Karim', 'SY', 'title1', 'Administrateur', '', '', 'Sacré Coeur 3', 'Villa 9653 4ème phase', 'DAKAR', '', 'SENEGAL', '', 'karim.sy@maarch.org', 'http://www.maarch.com', '', '', '', 'bblier', 'VILLE', 'N', 'Y');
INSERT INTO contact_addresses (id, contact_id, contact_purpose_id, departement, firstname, lastname, title, function, occupancy, address_num, address_street, address_complement, address_town, address_postal_code, address_country, phone, email, website, salutation_header, salutation_footer, other_data, user_id, entity_id, is_private, enabled) VALUES (3, 1, 1, '', 'Laurent', 'GIOVANNONI', 'title1', 'Directeur Général', NULL, '11', 'Boulevard du Sud-Est', '92000', 'NANTERRE', 'Nanterre', 'FRANCE', '', 'laurent.giovannoni@maarch.org', 'http://www.maarch.com', '', '', '', 'bblier', 'COU', 'N', 'Y');
Select setval('contact_addresses_id_seq', (select max(id)+1 from contact_addresses), false);
------------
--STATUS-
------------
TRUNCATE TABLE status;
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('COU', 'En cours', 'Y', 'N', 'fm-letter-status-inprogress', 'apps', 'Y', 'Y');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('DEL', 'Supprimé', 'Y', 'N', 'fm-letter-del', 'apps', 'N', 'Y');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('END', 'Clos / fin du workflow', 'Y', 'N', 'fm-letter-status-end', 'apps', 'Y', 'Y');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('NEW', 'Nouveau courrier pour le service', 'Y', 'N', 'fm-letter-status-new', 'apps', 'Y', 'Y');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('RET', 'Retour courrier ou document en qualification', 'N', 'N', 'fm-letter-status-rejected', 'apps', 'Y', 'Y');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('VAL', 'Courrier signalé à instruire', 'Y', 'N', 'fm-letter-status-aval', 'apps', 'Y', 'Y');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('INIT', 'Nouveau courrier ou document non qualifié', 'Y', 'N', 'fm-letter-status-attr', 'apps', 'Y', 'Y');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('EAVIS', 'Avis demandé', 'N', 'N', 'fa-lightbulb-o', 'apps', 'Y', 'Y');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('EENV', 'A e-envoyer', 'N', 'N', 'fm-letter-status-aenv', 'apps', 'Y', 'Y');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('ESIG', 'A e-signer', 'N', 'N', 'fm-file-fingerprint', 'apps', 'Y', 'Y');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('EVIS', 'A e-viser', 'N', 'N', 'fm-letter-status-aval', 'apps', 'Y', 'Y');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('ESIGAR', 'AR à e-signer', 'N', 'N', 'fm-file-fingerprint', 'apps', 'Y', 'Y');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('EENVAR', 'AR à e-envoyer', 'N', 'N', 'fm-letter-status-aenv', 'apps', 'Y', 'Y');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('SVX', 'En attente  de traitement SVE', 'N', 'N', 'fm-letter-status-wait', 'apps', 'Y', 'Y');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('SSUITE', 'Sans suite', 'Y', 'N', 'fm-letter-del', 'apps', 'Y', 'Y');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('A_TRA', 'PJ à traiter', 'Y', 'N', 'fm-letter-new', 'apps', 'Y', 'Y');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('TRA', 'PJ traitée', 'Y', 'N', 'fm-letter-end', 'apps', 'Y', 'Y');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('OBS', 'PJ obsolète', 'Y', 'N', 'fm-letter-end', 'apps', 'Y', 'Y');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('TMP', 'PJ brouillon', 'Y', 'N', 'fm-letter-cou', 'apps', 'N', 'N');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('EXP_SEDA', 'A archiver', 'Y', 'N', 'fm-letter-status-acla', 'apps', 'Y', 'Y');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('SEND_SEDA', 'Courrier envoyé au système d''archivage', 'Y', 'N', 'fm-letter-status-inprogress', 'apps', 'Y', 'Y');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('ACK_SEDA', 'Accusé de reception reçu', 'Y', 'N', 'fm-letter-status-acla', 'apps', 'Y', 'Y');
INSERT INTO status (id, label_status, is_system, is_folder_status, img_filename, maarch_module, can_be_searched, can_be_modified) VALUES ('REPLY_SEDA', 'Courrier archivé', 'Y', 'N', 'fm-letter-status-acla', 'apps', 'Y', 'Y');
------------
--PARAMETERS
------------
TRUNCATE TABLE parameters;
INSERT INTO parameters (id, param_value_string, param_value_int, param_value_date) VALUES ('apa_reservation_batch', NULL, 1, NULL);
INSERT INTO parameters (id, param_value_string, param_value_int, param_value_date) VALUES ('workbatch_rec', '', 1, NULL);
INSERT INTO parameters (id, param_value_string, param_value_int, param_value_date) VALUES ('folder_id_increment', '', 200, NULL);
INSERT INTO parameters (id, param_value_string, param_value_int, param_value_date) VALUES ('work_batch_autoimport_id', NULL, 1, NULL);
INSERT INTO parameters (id, param_value_string, param_value_int, param_value_date) VALUES ('postindexing_workbatch', NULL, 1, NULL);
INSERT INTO parameters (id, param_value_string, param_value_int, param_value_date) VALUES ('database_version', '17.06.21', 1706, NULL);
------------
--DIFFLIST_TYPES
------------
TRUNCATE TABLE difflist_types;
INSERT INTO difflist_types (difflist_type_id, difflist_type_label, difflist_type_roles, allow_entities, is_system) VALUES ('entity_id', 'Diffusion aux services', 'dest copy avis', 'Y', 'Y');
INSERT INTO difflist_types (difflist_type_id, difflist_type_label, difflist_type_roles, allow_entities, is_system) VALUES ('type_id', 'Diffusion selon le type de document', 'dest copy', 'Y', 'Y');
INSERT INTO difflist_types (difflist_type_id, difflist_type_label, difflist_type_roles, allow_entities, is_system) VALUES ('foldertype_id', 'Diffusion selon le type de dossiers', 'dest copy', 'Y', 'Y');
INSERT INTO difflist_types (difflist_type_id, difflist_type_label, difflist_type_roles, allow_entities, is_system) VALUES ('VISA_CIRCUIT', 'Circuit de visa', 'visa sign ', 'N', 'N');
------------
--ACTIONS
------------
TRUNCATE TABLE actions;
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (1, 'redirect', 'Rediriger', 'NEW', 'Y', 'N', 'Y', 'redirect', 'Y', 'entities', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (2, 'to_validate', 'Valider', 'VAL', 'Y', 'N', 'N', 'confirm_status', 'N', 'apps', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (3, '', 'Retourner au service Courrier', 'RET', 'N', 'N', 'Y', 'confirm_status', 'Y', 'apps', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (4, '', 'Enregistrer les modifications', '_NOSTATUS_', 'N', 'N', 'Y', 'process', 'N', 'apps', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (5, '', 'Remettre en traitement', 'COU', 'N', 'N', 'Y', '', 'Y', 'apps', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (6, '', 'Classer sans suite', 'SSUITE', 'N', 'N', 'Y', '', 'Y', 'apps', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (7, '', 'Affecter au service', 'NEW', 'N', 'N', 'Y', 'confirm_status', 'Y', 'apps', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (18, 'indexing', 'Valider courrier', 'NEW', 'N', 'N', 'Y', 'validate_mail', 'Y', 'apps', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (19, '', 'Traiter courrier', 'COU', 'N', 'N', 'Y', 'process', 'N', 'apps', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (20, '', 'Cloturer', 'END', 'N', 'N', 'Y', 'close_mail', 'Y', 'apps', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (21, 'indexing', 'Indexation', 'INIT', 'N', 'N', 'Y', 'index_mlb', 'Y', 'apps', 'Y', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (22, 'indexing', 'Envoyer pour validation', 'VAL', 'N', 'N', 'Y', 'validate_mail', 'Y', 'apps', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (36, '', 'Envoyer pour avis', 'EAVIS', 'N', 'N', 'Y','send_docs_to_recommendation', 'Y', 'apps', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (37, '', 'Donner un avis', '_NOSTATUS_', 'N', 'N', 'Y','avis_workflow_simple', 'Y', 'apps', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (100, '', 'Voir le document', '', 'N', 'N', 'Y', 'view', 'N', 'apps', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (101, '', 'Envoyer pour visa', 'VIS', 'N', 'N', 'Y', '', 'Y', 'apps', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (112, 'indexing', 'Enregistrer', '_NOSTATUS_', 'N', 'N', 'Y', 'index_mlb', 'Y', 'apps', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (113, 'redirect', 'Ajouter en copie', '', 'N', 'N', 'Y', 'put_in_copy', 'Y', 'apps', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (114, '', 'Marquer comme lu', '', 'N', 'N', 'Y', 'mark_as_read', 'N', 'apps', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (200, '', 'Envoyer l''AR pour e-signature', 'ESIGAR', 'N', 'N', 'Y', 'redirect_visa_sign', 'Y', 'visa', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (210, '', 'Transmettre l''AR signé', 'EENVAR', 'N', 'N', 'Y', 'confirm_status', 'Y', 'visa', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (405, '', 'Viser le courrier', '_NOSTATUS_', 'N', 'N', 'Y', 'visa_mail', 'Y', 'visa', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (407, '', 'Renvoyer pour traitement', 'COU', 'N', 'N', 'Y', 'confirm_status', 'Y', 'visa', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (410, '', 'Transmettre la réponse signée', 'EENV', 'N', 'N', 'Y', 'interrupt_visa', 'Y', 'visa', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (411, '', 'Transmettre pour classement', 'CLAS', 'N', 'N', 'Y', '', 'Y', 'visa', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (414, '', 'Envoyer pour e-visa et e-signature', '_NOSTATUS_', 'N', 'N', 'Y', 'send_to_visa', 'Y', 'visa', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (415, '', 'Envoyer pour e-signature', 'ESIG', 'N', 'N', 'Y', 'redirect_visa_sign', 'Y', 'visa', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (416, '', 'Viser et poursuivre le circuit', '_NOSTATUS_', 'N', 'N', 'Y', 'visa_workflow', 'Y', 'visa', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (417, '', 'Envoyer l''AR', 'SVX', 'N', 'N', 'Y', 'send_to_contact_with_mandatory_attachment', 'Y', 'apps', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (500, '', 'Transférer au système d''archivage', 'SEND_SEDA', 'N', 'N', 'Y', 'export_seda', 'Y', 'export_seda', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (501, '', 'Valider la réception du courrier par le système d''archivage', 'ACK_SEDA', 'N', 'N', 'Y', 'check_acknowledgement', 'Y', 'apps', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (502, '', 'Valider l''archivage du courrier', 'REPLY_SEDA', 'N', 'N', 'Y', 'check_reply', 'Y', 'apps', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (503, '', 'Supprimer courrier', 'DEL', 'N', 'N', 'Y', 'purge_letter', 'Y', 'apps', 'N', NULL);
INSERT INTO actions (id, keyword, label_action, id_status, is_system, is_folder_action, enabled, action_page, history, origin, create_id, category_id) VALUES (504, '', 'Remise à zero du courrier', 'END', 'N', 'N', 'Y', 'reset_letter', 'Y', 'apps', 'N', NULL);
Select setval('actions_id_seq', (select max(id)+1 from actions), false);
------------
-- BANNETTE SECONDAIRE POUR LE GROUPE DES SUPERVISEURS DE COURRIER
------------
TRUNCATE TABLE user_baskets_secondary;
INSERT INTO user_baskets_secondary (user_id, group_id, basket_id) VALUES ( 'ddaull', 'RESPONSABLE', 'EvisBasket');
INSERT INTO user_baskets_secondary (user_id, group_id, basket_id) VALUES ( 'rrenaud', 'DIRECTEUR', 'ValidationBasket');
INSERT INTO user_baskets_secondary (user_id, group_id, basket_id) VALUES ( 'eerina', 'DIRECTEUR', 'ValidationBasket');
Select setval('user_baskets_secondary_seq', (select max(system_id)+1 from user_baskets_secondary), false);
------------
--ACTIONS_GROUPBASKETS
------------
TRUNCATE TABLE actions_groupbaskets;
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (112, '', 'COURRIER', 'IndexingBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (22, '', 'COURRIER', 'RetourCourrier', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (22, '', 'COURRIER', 'QualificationBasket', 'N', 'N', 'Y');

INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (100, '', 'AGENT', 'CopyMailBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (114, '', 'AGENT', 'CopyMailBasket', 'Y', 'N', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (1, '', 'AGENT', 'MyBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (20, '', 'AGENT', 'MyBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (414, '', 'AGENT', 'MyBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (415, '', 'AGENT', 'MyBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (19, '', 'AGENT', 'MyBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (36, '', 'AGENT', 'MyBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (200, '', 'AGENT', 'MyBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (6, '', 'AGENT', 'MyBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (100, '', 'AGENT', 'InValidationBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (4, '', 'AGENT', 'RetAvisBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (5, '', 'AGENT', 'RetAvisBasket', 'Y', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (112, '', 'AGENT', 'IndexingBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (37, '', 'AGENT', 'DdeAvisBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (4, '', 'AGENT', 'DdeAvisBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (100, '', 'AGENT', 'SupAvisBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (100, '', 'AGENT', 'EenvBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (20, '', 'AGENT', 'EenvBasket', 'Y', 'N', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (4, '', 'AGENT', 'EenvARBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (417, '', 'AGENT', 'EenvARBasket', 'N', 'Y', 'N');

INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (18, '', 'RESP_COURRIER', 'ValidationBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (7, '', 'RESP_COURRIER', 'ValidationBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (3, '', 'RESP_COURRIER', 'ValidationBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (6, '', 'RESP_COURRIER', 'ValidationBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (100, '', 'RESP_COURRIER', 'CopyMailBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (114, '', 'RESP_COURRIER', 'CopyMailBasket', 'Y', 'N', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (22, '', 'RESP_COURRIER', 'RetourCourrier', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (20, '', 'RESP_COURRIER', 'MyBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (1, '', 'RESP_COURRIER', 'MyBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (3, '', 'RESP_COURRIER', 'MyBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (19, '', 'RESP_COURRIER', 'MyBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (414, '', 'RESP_COURRIER', 'MyBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (36, '', 'RESP_COURRIER', 'MyBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (6, '', 'RESP_COURRIER', 'MyBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (503, '', 'RESP_COURRIER', 'MyBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (112, '', 'RESP_COURRIER', 'IndexingBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (37, '', 'RESP_COURRIER', 'DdeAvisBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (4, '', 'RESP_COURRIER', 'DdeAvisBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (100, '', 'RESP_COURRIER', 'SupAvisBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (4, '', 'RESP_COURRIER', 'RetAvisBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (5, '', 'RESP_COURRIER', 'RetAvisBasket', 'Y', 'Y', 'N');

INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (1, '', 'RESPONSABLE', 'MyBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (20, '', 'RESPONSABLE', 'MyBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (19, '', 'RESPONSABLE', 'MyBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (414, '', 'RESPONSABLE', 'MyBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (6, '', 'RESPONSABLE', 'MyBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (503, '', 'RESPONSABLE', 'MyBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (100, '', 'RESPONSABLE', 'InValidationBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (100, '', 'RESPONSABLE', 'CopyMailBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (113, '', 'RESPONSABLE', 'CopyMailBasket', 'Y', 'N', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (114, '', 'RESPONSABLE', 'CopyMailBasket', 'Y', 'N', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (19, '', 'RESPONSABLE', 'ValidAnswerBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (20, '', 'RESPONSABLE', 'DepartmentBasket', 'Y', 'N', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (3, '', 'RESPONSABLE', 'DepartmentBasket', 'Y', 'N', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (100, '', 'RESPONSABLE', 'DepartmentBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (112, '', 'RESPONSABLE', 'IndexingBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (36, '', 'RESPONSABLE', 'MyBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (37, '', 'RESPONSABLE', 'DdeAvisBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (4, '', 'RESPONSABLE', 'DdeAvisBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (100, '', 'RESPONSABLE', 'SupAvisBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (4, '', 'RESPONSABLE', 'RetAvisBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (5, '', 'RESPONSABLE', 'RetAvisBasket', 'Y', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (405, '', 'RESPONSABLE', 'EvisBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (408, '', 'RESPONSABLE', 'EvisBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (407, '', 'RESPONSABLE', 'EvisBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (416, '', 'RESPONSABLE', 'EvisBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (405, '', 'RESPONSABLE', 'EsigBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (407, '', 'RESPONSABLE', 'EsigBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (410, '', 'RESPONSABLE', 'EsigBasket', 'Y', 'N', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (405, '', 'RESPONSABLE', 'EsigARBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (407, '', 'RESPONSABLE', 'EsigARBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (210, '', 'RESPONSABLE', 'EsigARBasket', 'Y', 'N', 'N');

INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (18, '', 'DIRECTEUR', 'ValidationBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (7, '', 'DIRECTEUR', 'ValidationBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (3, '', 'DIRECTEUR', 'ValidationBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (6, '', 'DIRECTEUR', 'ValidationBasket', 'N', 'Y', 'N');

INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (19, '', 'ELU', 'MyBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (112, '', 'ELU', 'IndexingBasket', 'N', 'N', 'Y');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (37, '', 'ELU', 'DdeAvisBasket', 'N', 'Y', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (4, '', 'ELU', 'DdeAvisBasket', 'N', 'N', 'Y');

INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (500, '', 'ARCHIVISTE', 'ToArcBasket', 'Y', 'N', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (501, '', 'ARCHIVISTE', 'ToArcBasket', 'Y', 'N', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (502, '', 'ARCHIVISTE', 'SentArcBasket', 'Y', 'N', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (503, '', 'ARCHIVISTE', 'AckArcBasket', 'Y', 'N', 'N');
INSERT INTO actions_groupbaskets (id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES (504, '', 'ARCHIVISTE', 'AckArcBasket', 'Y', 'N', 'N');
------------
--GROUPBASKET_REDIRECT
------------
TRUNCATE TABLE groupbasket_redirect;
INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('COURRIER', 'IndexingBasket', 112, '', 'ALL_ENTITIES', 'ENTITY');

INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('COURRIER', 'QualificationBasket', 22, '', 'ALL_ENTITIES', 'ENTITY');
INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('COURRIER', 'RetourCourrier', 22, '', 'ALL_ENTITIES', 'ENTITY');
INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('COURRIER', 'ValidationBasket', 22, '', 'ALL_ENTITIES', 'ENTITY');

INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('AGENT', 'LateMailBasket', 21, '', 'ENTITIES_JUST_BELOW', 'ENTITY');
INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('AGENT', 'MyBasket', 1, '', 'ALL_ENTITIES', 'ENTITY');
INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('AGENT', 'IndexingBasket', 112, '', 'MY_ENTITIES', 'ENTITY');
INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('AGENT', 'IndexingBasket', 112, '', 'ENTITIES_BELOW', 'ENTITY');

INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('RESP_COURRIER', 'ValidationBasket', 18, '', 'ALL_ENTITIES', 'ENTITY');
INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('RESP_COURRIER', 'ValidationBasket', 1, '', 'ALL_ENTITIES', 'ENTITY');
INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('RESP_COURRIER', 'RetourCourrier', 21, '', 'ALL_ENTITIES', 'ENTITY');
INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('RESP_COURRIER', 'MyBasket', 1, '', 'ALL_ENTITIES', 'ENTITY');
INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('RESP_COURRIER', 'RetourCourrier', 1, '', 'ALL_ENTITIES', 'ENTITY');
INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('RESP_COURRIER', 'IndexingBasket', 112, '', 'ALL_ENTITIES', 'ENTITY');

INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('RESPONSABLE', 'MyBasket', 1, '', 'ALL_ENTITIES', 'ENTITY');
INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('RESPONSABLE', 'CopyMailBasket', 113, '', 'ALL_ENTITIES', 'USERS');
INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('RESPONSABLE', 'CopyMailBasket', 113, '', 'ALL_ENTITIES', 'ENTITY');
INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('RESPONSABLE', 'IndexingBasket', 112, '', 'MY_ENTITIES', 'ENTITY');
INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('RESPONSABLE', 'IndexingBasket', 112, '', 'ENTITIES_BELOW', 'ENTITY');

INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('DIRECTEUR', 'ValidationBasket', 18, '', 'ALL_ENTITIES', 'ENTITY');
INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('DIRECTEUR', 'ValidationBasket', 1, '', 'ALL_ENTITIES', 'ENTITY');

INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('ELU', 'MyBasket', 1, '', 'ALL_ENTITIES', 'ENTITY');
INSERT INTO groupbasket_redirect (group_id, basket_id, action_id, entity_id, keyword, redirect_mode) VALUES ('ELU', 'IndexingBasket', 112, '', 'ALL_ENTITIES', 'ENTITY');
Select setval('groupbasket_redirect_system_id_seq', (select max(system_id)+1 from groupbasket_redirect), false);
------------
--GROUPBASKET_STATUS
------------
TRUNCATE TABLE groupbasket_status;
INSERT INTO groupbasket_status (group_id, basket_id, action_id, status_id) VALUES ('COURRIER', 'IndexingBasket', 112, 'NEW');
INSERT INTO groupbasket_status (group_id, basket_id, action_id, status_id) VALUES ('COURRIER', 'IndexingBasket', 112, 'VAL');

INSERT INTO groupbasket_status (group_id, basket_id, action_id, status_id) VALUES ('AGENT', 'IndexingBasket', 112, 'END');
INSERT INTO groupbasket_status (group_id, basket_id, action_id, status_id) VALUES ('AGENT', 'IndexingBasket', 112, 'NEW');
INSERT INTO groupbasket_status (group_id, basket_id, action_id, status_id) VALUES ('RESP_COURRIER', 'IndexingBasket', 112, 'END');
INSERT INTO groupbasket_status (group_id, basket_id, action_id, status_id) VALUES ('RESP_COURRIER', 'IndexingBasket', 112, 'NEW');
INSERT INTO groupbasket_status (group_id, basket_id, action_id, status_id) VALUES ('RESPONSABLE', 'IndexingBasket', 112, 'END');
INSERT INTO groupbasket_status (group_id, basket_id, action_id, status_id) VALUES ('RESPONSABLE', 'IndexingBasket', 112, 'NEW');
Select setval('groupbasket_status_system_id_seq', (select max(system_id)+1 from groupbasket_status), false);
------------
--FOLDERTYPES
------------
TRUNCATE TABLE foldertypes;
INSERT INTO foldertypes (foldertype_id, foldertype_label, maarch_comment, retention_time, custom_d1, custom_f1, custom_n1, custom_t1, custom_d2, custom_f2, custom_n2, custom_t2, custom_d3, custom_f3, custom_n3, custom_t3, custom_d4, custom_f4, custom_n4, custom_t4, custom_d5, custom_f5, custom_n5, custom_t5, custom_d6, custom_t6, custom_d7, custom_t7, custom_d8, custom_t8, custom_d9, custom_t9, custom_d10, custom_t10, custom_t11, custom_t12, custom_t13, custom_t14, custom_t15, coll_id)
VALUES (1, 'Les courriers', '', NULL, '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', '0000000000', 'letterbox_coll');
Select setval('foldertype_id_id_seq', (select max(foldertype_id)+1 from foldertypes), false);
------------
--TEMPLATES_DOCTYPE_EXT--
------------
TRUNCATE TABLE templates_doctype_ext;
INSERT INTO templates_doctype_ext (template_id, type_id, is_generated) VALUES (3, 1201, 'Y');
INSERT INTO templates_doctype_ext (template_id, type_id, is_generated) VALUES (9, 1202, 'Y');
------------
--FOLDERS
------------
TRUNCATE TABLE folders;
INSERT INTO folders (folders_system_id, folder_id, foldertype_id, parent_id, folder_name, subject, description, author, typist, status, folder_level, creation_date, folder_out_id, video_status, video_user, is_frozen, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_d11, custom_t12, custom_d12, custom_t13, custom_d13, custom_t14, custom_d14, custom_t15, is_complete, is_folder_out, last_modified_date) VALUES (20, 'LITIGE', 1, 0, 'Litiges tous domaines', NULL, NULL, NULL, 'superadmin', 'FOLDNEW', 1, '2012-03-02 18:30:40.311', NULL, NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N', 'N', '2012-03-02 18:30:40.311');
INSERT INTO folders (folders_system_id, folder_id, foldertype_id, parent_id, folder_name, subject, description, author, typist, status, folder_level, creation_date, folder_out_id, video_status, video_user, is_frozen, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_d11, custom_t12, custom_d12, custom_t13, custom_d13, custom_t14, custom_d14, custom_t15, is_complete, is_folder_out, last_modified_date) VALUES (21, 'LF', 1, 20, 'Litige financier', NULL, NULL, NULL, 'superadmin', 'FOLDNEW', 2, '2012-03-02 18:30:58.112', NULL, NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N', 'N', '2012-03-02 18:30:58.112');
INSERT INTO folders (folders_system_id, folder_id, foldertype_id, parent_id, folder_name, subject, description, author, typist, status, folder_level, creation_date, folder_out_id, video_status, video_user, is_frozen, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_d11, custom_t12, custom_d12, custom_t13, custom_d13, custom_t14, custom_d14, custom_t15, is_complete, is_folder_out, last_modified_date) VALUES (22, 'LE', 1, 20, 'Litige enfance', NULL, NULL, NULL, 'superadmin', 'FOLDNEW', 2, '2012-03-02 18:31:13.79', NULL, NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N', 'N', '2012-03-02 18:31:13.79');
INSERT INTO folders (folders_system_id, folder_id, foldertype_id, parent_id, folder_name, subject, description, author, typist, status, folder_level, creation_date, folder_out_id, video_status, video_user, is_frozen, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_d11, custom_t12, custom_d12, custom_t13, custom_d13, custom_t14, custom_d14, custom_t15, is_complete, is_folder_out, last_modified_date) VALUES (23, 'COURRIERS', 1, 0, 'Courriers', NULL, NULL, NULL, 'superadmin', 'FOLDNEW', 1, '2012-03-02 18:31:27.487', NULL, NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N', 'N', '2012-03-02 18:31:27.487');
INSERT INTO folders (folders_system_id, folder_id, foldertype_id, parent_id, folder_name, subject, description, author, typist, status, folder_level, creation_date, folder_out_id, video_status, video_user, is_frozen, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_d11, custom_t12, custom_d12, custom_t13, custom_d13, custom_t14, custom_d14, custom_t15, is_complete, is_folder_out, last_modified_date) VALUES (24, 'JSP', 1, 0, 'Jeunesse et Sport', NULL, NULL, NULL, 'superadmin', 'FOLDNEW', 1, '2012-03-02 18:32:07.22', NULL, NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N', 'N', '2012-03-02 18:32:07.22');
INSERT INTO folders (folders_system_id, folder_id, foldertype_id, parent_id, folder_name, subject, description, author, typist, status, folder_level, creation_date, folder_out_id, video_status, video_user, is_frozen, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_d11, custom_t12, custom_d12, custom_t13, custom_d13, custom_t14, custom_d14, custom_t15, is_complete, is_folder_out, last_modified_date) VALUES (25, 'CLUB', 1, 24, 'Clubs et associations sportives', NULL, NULL, NULL, 'superadmin', 'FOLDNEW', 2, '2012-03-02 18:32:24.602', NULL, NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N', 'N', '2012-03-02 18:32:24.602');
INSERT INTO folders (folders_system_id, folder_id, foldertype_id, parent_id, folder_name, subject, description, author, typist, status, folder_level, creation_date, folder_out_id, video_status, video_user, is_frozen, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_d11, custom_t12, custom_d12, custom_t13, custom_d13, custom_t14, custom_d14, custom_t15, is_complete, is_folder_out, last_modified_date) VALUES (26, 'ETB', 1, 24, 'Etablissements scolaires', NULL, NULL, NULL, 'superadmin', 'FOLDNEW', 2, '2012-03-02 18:32:43.228', NULL, NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N', 'N', '2012-03-02 18:32:43.228');
INSERT INTO folders (folders_system_id, folder_id, foldertype_id, parent_id, folder_name, subject, description, author, typist, status, folder_level, creation_date, folder_out_id, video_status, video_user, is_frozen, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_d11, custom_t12, custom_d12, custom_t13, custom_d13, custom_t14, custom_d14, custom_t15, is_complete, is_folder_out, last_modified_date) VALUES (27, 'PI', 1, 23, 'Partenariat institutionnel', NULL, NULL, NULL, 'superadmin', 'FOLDNEW', 2, '2012-03-02 18:33:01.543', NULL, NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N', 'N', '2012-03-02 18:33:01.543');
INSERT INTO folders (folders_system_id, folder_id, foldertype_id, parent_id, folder_name, subject, description, author, typist, status, folder_level, creation_date, folder_out_id, video_status, video_user, is_frozen, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_d11, custom_t12, custom_d12, custom_t13, custom_d13, custom_t14, custom_d14, custom_t15, is_complete, is_folder_out, last_modified_date) VALUES (28, 'LP', 1, 23, 'LaPoste', NULL, NULL, NULL, 'superadmin', 'FOLDNEW', 2, '2012-03-02 18:33:17.705', NULL, NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N', 'N', '2012-03-02 18:33:17.705');
INSERT INTO folders (folders_system_id, folder_id, foldertype_id, parent_id, folder_name, subject, description, author, typist, status, folder_level, creation_date, folder_out_id, video_status, video_user, is_frozen, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_d11, custom_t12, custom_d12, custom_t13, custom_d13, custom_t14, custom_d14, custom_t15, is_complete, is_folder_out, last_modified_date) VALUES (29, 'PSF', 1, 0, 'POLE SERVICES FONCTIONNELS', NULL, NULL, NULL, 'superadmin', 'FOLDNEW', 1, '2012-03-02 18:32:07.22', NULL, NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N', 'N', '2012-03-02 18:32:07.22');
INSERT INTO folders (folders_system_id, folder_id, foldertype_id, parent_id, folder_name, subject, description, author, typist, status, folder_level, creation_date, folder_out_id, video_status, video_user, is_frozen, custom_t1, custom_n1, custom_f1, custom_d1, custom_t2, custom_n2, custom_f2, custom_d2, custom_t3, custom_n3, custom_f3, custom_d3, custom_t4, custom_n4, custom_f4, custom_d4, custom_t5, custom_n5, custom_f5, custom_d5, custom_t6, custom_d6, custom_t7, custom_d7, custom_t8, custom_d8, custom_t9, custom_d9, custom_t10, custom_d10, custom_t11, custom_d11, custom_t12, custom_d12, custom_t13, custom_d13, custom_t14, custom_d14, custom_t15, is_complete, is_folder_out, last_modified_date) VALUES (30, 'PSF - BUDGET', 1, 29, 'BUDGET', NULL, NULL, NULL, 'superadmin', 'FOLDNEW', 2, '2012-03-02 18:32:07.22', NULL, NULL, NULL, 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N', 'N', '2012-03-02 18:32:07.22');
------------
--KEYWORDS / TAGS
------------
TRUNCATE TABLE tags;
INSERT INTO tags (tag_label, coll_id, entity_id_owner) VALUES ('SEMINAIRE', 'letterbox_coll', NULL);
INSERT INTO tags  (tag_label, coll_id, entity_id_owner) VALUES ('INNOVATION', 'letterbox_coll', NULL);
INSERT INTO tags  (tag_label, coll_id, entity_id_owner) VALUES ('MAARCH', 'letterbox_coll', NULL);
INSERT INTO tags  (tag_label, coll_id, entity_id_owner) VALUES ('ENVIRONNEMENT', 'letterbox_coll', NULL);
INSERT INTO tags  (tag_label, coll_id, entity_id_owner) VALUES ('PARTENARIAT', 'letterbox_coll', NULL);
INSERT INTO tags  (tag_label, coll_id, entity_id_owner) VALUES ('JUMELAGE', 'letterbox_coll', NULL);
INSERT INTO tags  (tag_label, coll_id, entity_id_owner) VALUES ('ECONOMIE', 'letterbox_coll', NULL);
INSERT INTO tags  (tag_label, coll_id, entity_id_owner) VALUES ('ASSOCIATIONS', 'letterbox_coll', NULL);
INSERT INTO tags  (tag_label, coll_id, entity_id_owner) VALUES ('RH', 'letterbox_coll', NULL);
INSERT INTO tags  (tag_label, coll_id, entity_id_owner) VALUES ('BUDGET', 'letterbox_coll', NULL);
INSERT INTO tags  (tag_label, coll_id, entity_id_owner) VALUES ('QUARTIERS', 'letterbox_coll', NULL);
INSERT INTO tags  (tag_label, coll_id, entity_id_owner) VALUES ('LITTORAL', 'letterbox_coll', NULL);
INSERT INTO tags  (tag_label, coll_id, entity_id_owner) VALUES ('SPORT', 'letterbox_coll', NULL);
------------
--TEMPLATES
------------
TRUNCATE TABLE templates;
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (3, 'AppelTel', 'Appel téléphonique', '<h2><span style="color: #000000###"><strong>Appel t&eacute###l&eacute###phonique</strong></span></h2>
<hr />
<p>&nbsp###</p>
<p>Bonjour,</p>
<p>Vous avez re&ccedil###u un appel t&eacute###l&eacute###phonique dont voici les informations :</p>
<table style="height: 61px### border-color: #f0f0f0###" border="1" width="597"><caption>&nbsp###</caption>
<tbody>
<tr>
<td style="text-align: center###"><strong>Date</strong></td>
<td style="text-align: center###"><strong>Heure</strong></td>
<td style="text-align: center###"><strong>Soci&eacute###t&eacute###</strong></td>
<td style="text-align: center###"><strong>Contact</strong></td>
</tr>
<tr>
<td>&nbsp###</td>
<td>&nbsp###</td>
<td>&nbsp###</td>
<td>&nbsp###</td>
</tr>
</tbody>
</table>
<p>&nbsp###</p>
<h4>Notes :</h4>
<p>&nbsp###</p>', 'HTML', NULL, NULL, '', '', 'doctypes', 'all');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target)
VALUES (2, '[notification] Notifications événement', 'Notifications des événements système',
'<p><font face="verdana,geneva" size="1">Bonjour [recipient.firstname] [recipient.lastname],</font></p>
<p><font face="verdana,geneva" size="1"> </font></p>
<p><font face="verdana,geneva" size="1">Voici la liste des &eacute###v&eacute###nements de l''application qui vous sont notifi&eacute###s ([notification.description]) :</font></p>
<table style="width: 800px### height: 36px###" border="0" cellspacing="1" cellpadding="1">
<tbody>
<tr>
<td style="width: 150px### background-color: #0099ff###"><font face="verdana,geneva" size="1"><strong><font color="#FFFFFF">Date</font></strong></font></td>
<td style="width: 150px### background-color: #0099ff###"><font face="verdana,geneva" size="1"><strong><font color="#FFFFFF">Utilisateur </font></strong></font><font face="verdana,geneva" size="1"><strong></strong></font></td>
<td style="width: 500px### background-color: #0099ff###"><font face="verdana,geneva" size="1"><strong><font color="#FFFFFF">Description</font></strong></font></td>
</tr>
<tr>
<td><font face="verdana,geneva" size="1">[events.event_date###block=tr###frm=dd/mm/yyyy hh:nn:ss]</font></td>
<td><font face="verdana,geneva" size="1">[events.user_id]</font></td>
<td><font face="verdana,geneva" size="1">[events.event_info]</font></td>
</tr>
</tbody>
</table>',
'HTML', NULL, NULL, '', 'notif_events', 'notifications');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target)
VALUES (5, '[notification courrier] Alerte 2', '[notification] Alerte 2', '<p><font face="arial,helvetica,sans-serif" size="2">Bonjour [recipient.firstname] [recipient.lastname],</font></p>
<p> </p>
<p><font face="arial,helvetica,sans-serif" size="2">Voici la liste des courriers dont la date limite de traitement est dépassée :n</font></p>
<table style="border: 1pt solid #000000### width: 1582px### height: 77px###" border="1" cellspacing="1" cellpadding="5" frame="box">
<tbody>
<tr>
<td><font face="arial,helvetica,sans-serif"><strong><font size="2">Référence</font></strong></font></td>
<td><font face="arial,helvetica,sans-serif"><strong><font size="2">Origine</font></strong></font></td>
<td><font face="arial,helvetica,sans-serif"><strong><font size="2">Emetteur</font></strong></font></td>
<td><font face="arial,helvetica,sans-serif" size="2" color="#000000"><strong>Date</strong></font></td>
<td><font face="arial,helvetica,sans-serif" size="2" color="#000000"><strong>Objet</strong></font></td>
<td><font face="arial,helvetica,sans-serif" size="2" color="#000000"><strong>Type</strong></font></td>
<td><font face="arial,helvetica,sans-serif" size="2" color="#FFFFFF"><strong>Liens</strong></font></td>
</tr>
<tr>
<td><font face="arial,helvetica,sans-serif" size="2">[res_letterbox.res_id]</font></td>
<td><font face="arial,helvetica,sans-serif" size="2">[res_letterbox.typist]</font></td>
<td>
<p><font face="arial,helvetica,sans-serif" size="2">[res_letterbox.contact_society] [res_letterbox.contact_firstname] [res_letterbox.contact_lastname][res_letterbox.function][res_letterbox.address_num][res_letterbox.address_street][res_letterbox.address_postal_code][res_letterbox.address_town]</font></p>
<p><font face="arial,helvetica,sans-serif" size="2">[res_letterbox.tag_label]</font></p>
</td>
<td><font face="arial,helvetica,sans-serif" size="2">[res_letterbox.doc_date###block=tr###frm=dd/mm/yyyy]</font></td>
<td><font face="arial,helvetica,sans-serif" color="#FF0000"><strong><font size="2">[res_letterbox.subject]</font></strong></font></td>
<td><font face="arial,helvetica,sans-serif" size="2">[res_letterbox.type_label]</font></td>
<td><font face="arial,helvetica,sans-serif"><a href="[res_letterbox.linktoprocess]" name="traiter">traiter</a> <a href="[res_letterbox.linktodoc]" name="doc">Afficher</a></font></td>
</tr>
</tbody>
</table>', 'HTML', NULL, NULL, 'ODP: open_office_presentation', 'letterbox_events', 'notifications');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target)
VALUES (6, '[notification courrier] Alerte 1', '[notification] Alerte 1', '<p><font face="arial,helvetica,sans-serif" size="2">Bonjour [recipient.firstname] [recipient.lastname],</font></p>
<p> </p>
<p><font face="arial,helvetica,sans-serif" size="2"> </font></p>
<p> </p>
<p><font face="arial,helvetica,sans-serif" size="2">Voici la liste des courriers toujours en attente de traitement :</font></p>
<p> </p>
<table style="border: 1pt solid #000000### width: 1582px### height: 77px###" border="1" cellspacing="1" cellpadding="5" frame="box">
<tbody>
<tr>
<td><font face="arial,helvetica,sans-serif"><strong><font size="2">Référence</font></strong></font></td>
<td><font face="arial,helvetica,sans-serif"><strong><font size="2">Origine</font></strong></font></td>
<td><font face="arial,helvetica,sans-serif"><strong><font size="2">Emetteur</font></strong></font></td>
<td><font face="arial,helvetica,sans-serif" size="2" color="#000000"><strong>Date</strong></font></td>
<td><font face="arial,helvetica,sans-serif" size="2" color="#000000"><strong>Objet</strong></font></td>
<td><font face="arial,helvetica,sans-serif" size="2" color="#000000"><strong>Type</strong></font></td>
<td><font face="arial,helvetica,sans-serif" size="2" color="#FFFFFF"><strong>Liens</strong></font></td>
</tr>
<tr>
<td><font face="arial,helvetica,sans-serif" size="2">[res_letterbox.res_id]</font></td>
<td><font face="arial,helvetica,sans-serif" size="2">[res_letterbox.typist]</font></td>
<td>
<p><font face="arial,helvetica,sans-serif" size="2">[res_letterbox.contact_society] [res_letterbox.contact_firstname] [res_letterbox.contact_lastname][res_letterbox.function][res_letterbox.address_num][res_letterbox.address_street][res_letterbox.address_postal_code][res_letterbox.address_town]</font></p>
<p><font face="arial,helvetica,sans-serif" size="2">[res_letterbox.tag_label]</font></p>
</td>
<td><font face="arial,helvetica,sans-serif" size="2">[res_letterbox.doc_date###block=tr###frm=dd/mm/yyyy]</font></td>
<td><font face="arial,helvetica,sans-serif" color="#FF0000"><strong><font size="2">[res_letterbox.subject]</font></strong></font></td>
<td><font face="arial,helvetica,sans-serif" size="2">[res_letterbox.type_label]</font></td>
<td><font face="arial,helvetica,sans-serif"><a href="[res_letterbox.linktoprocess]" name="traiter">traiter</a> <a href="[res_letterbox.linktodoc]" name="doc">Afficher</a></font></td>
</tr>
</tbody>
</table>', 'HTML', NULL, NULL, 'ODP: open_office_presentation', 'letterbox_events', 'notifications');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target)
VALUES (7, '[notification courrier] Diffusion de courrier', 'Alerte de courriers présents dans les bannettes', '<p><font face="arial,helvetica,sans-serif" size="2">Bonjour [recipient.firstname] [recipient.lastname],</font></p>
<p> </p>
<p><font face="arial,helvetica,sans-serif" size="2"> </font></p>
<p> </p>
<p><font face="arial,helvetica,sans-serif" size="2">Voici la liste des nouveaux courriers présents dans cette bannette :</font></p>
<p> </p>
<table style="border: 1pt solid #000000### width: 1582px### height: 77px###" border="1" cellspacing="1" cellpadding="5" frame="box">
<tbody>
<tr>
<td><font face="arial,helvetica,sans-serif"><strong><font size="2">Référence</font></strong></font></td>
<td><font face="arial,helvetica,sans-serif"><strong><font size="2">Origine</font></strong></font></td>
<td><font face="arial,helvetica,sans-serif"><strong><font size="2">Emetteur</font></strong></font></td>
<td><font face="arial,helvetica,sans-serif" size="2" color="#000000"><strong>Date</strong></font></td>
<td><font face="arial,helvetica,sans-serif" size="2" color="#000000"><strong>Objet</strong></font></td>
<td><font face="arial,helvetica,sans-serif" size="2" color="#000000"><strong>Type</strong></font></td>
<td><font face="arial,helvetica,sans-serif" size="2" color="#FFFFFF"><strong>Liens</strong></font></td>
</tr>
<tr>
<td><font face="arial,helvetica,sans-serif" size="2">[res_letterbox.res_id]</font></td>
<td><font face="arial,helvetica,sans-serif" size="2">[res_letterbox.typist]</font></td>
<td>
<p><font face="arial,helvetica,sans-serif" size="2">[res_letterbox.contact_society] [res_letterbox.contact_firstname] [res_letterbox.contact_lastname][res_letterbox.function][res_letterbox.address_num][res_letterbox.address_street][res_letterbox.address_postal_code][res_letterbox.address_town]</font></p>
</td>
<td><font face="arial,helvetica,sans-serif" size="2">[res_letterbox.doc_date###block=tr###frm=dd/mm/yyyy]</font></td>
<td><font face="arial,helvetica,sans-serif" color="#FF0000"><strong><font size="2">[res_letterbox.subject]</font></strong></font></td>
<td><font face="arial,helvetica,sans-serif" size="2">[res_letterbox.type_label]</font></td>
<td><font face="arial,helvetica,sans-serif"><a href="[res_letterbox.linktodetail]" name="detail">detail</a> <a href="[res_letterbox.linktodoc]" name="doc">Afficher</a></font></td>
</tr>
</tbody>
</table>', 'HTML', NULL, NULL, 'ODP: open_office_presentation', 'letterbox_events', 'notifications');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target)
VALUES (8, '[notification courrier] Nouvelle annotation', '[notification] Nouvelle annotation', '<p><font face="verdana,geneva" size="2">Bonjour [recipient.firstname] [recipient.lastname], [recipient.text]</font></p>
<p>&nbsp###</p>
<p><font face="verdana,geneva" size="2"> </font></p>
<p>&nbsp###</p>
<p><font face="verdana,geneva" size="2">Voici la liste des notes pour les courriers suivants :</font></p>
<p>&nbsp###</p>
<table style="width: 982px### height: 77px###" border="1" cellspacing="3" cellpadding="3" frame="box">
<tbody>
<tr>
<td><strong>Reference</strong></td>
<td><strong>Num</strong></td>
<td><strong>Date</strong></td>
<td><strong>Objet</strong></td>
<td><strong>Note</strong></td>
<td><strong>Ajout&eacute### par</strong></td>
<td><strong>Contact</strong></td>
<td><strong>Liens</strong></td>
</tr>
<tr>
<td>[notes.identifier]</td>
<td>[notes.# ###frm=0000]</td>
<td>[notes.doc_date###block=tr###frm=dd/mm/yyyy]</td>
<td>[notes.subject]</td>
<td>[notes.note_text]</td>
<td>[notes.user_id]</td>
<td>[notes.contact_society] [notes.contact_firstname] [notes.contact_lastname]</td>
<td><a href="[notes.linktodetail]" name="detail">d&eacute###tail</a> <a href="[notes.linktodoc]" name="doc">doc</a></td>
</tr>
</tbody>
</table>', 'HTML', NULL, NULL, 'ODP: open_office_presentation', 'notes', 'notifications');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (9, 'Demande - Voirie', 'Demande - Voirie', '<h2>Demande Intervention VOIRIE</h2>
<hr />
<table style="border: 1pt solid #000000### width: 597px### background-color: #f0f0f0### height: 172px###" border="1" cellspacing="1" cellpadding="5"><caption>&nbsp###</caption>
<tbody>
<tr>
<td style="width: 200px### background-color: #ffffff###"><strong>NOM, PRENOM demandeur</strong></td>
<td style="width: 200px### background-color: #ffffff###">&nbsp###</td>
</tr>
<tr style="background-color: #ffffff###">
<td style="width: 200px###">Adresse</td>
<td>&nbsp###</td>
</tr>
<tr style="background-color: #ffffff###">
<td style="width: 200px###"><strong>Contact</strong></td>
<td>&nbsp###</td>
</tr>
<tr style="background-color: #ffffff###">
<td style="width: 200px###"><strong>Intitul&eacute### demande</strong></td>
<td>&nbsp###</td>
</tr>
<tr style="background-color: #ffffff###">
<td style="width: 200px###">Compl&eacute###ment</td>
<td>&nbsp###</td>
</tr>
</tbody>
</table>', 'HTML', NULL, NULL, 'DOCX: demo_document_msoffice', '', 'doctypes', 'all');
INSERT INTO templates VALUES (10, '[maarch mairie] Clôture de demande', '[maarch mairie] Clôture de demande', '<p style="text-align: left###"><span style="font-size: small###">&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###&nbsp###</span><span style="text-decoration: underline###"><span style="font-size: small###">CLOTURE DEMANDE Maarch Mairie - [res_letterbox.type_label] - [res_letterbox.res_id] </span></span></p>
<p style="text-align: center###">&nbsp###</p>
<table style="background-color: #a8c33c### width: 800px### border: #000000 1pt solid###" border="1" cellspacing="1" cellpadding="5">
<tbody>
<tr>
<td style="width: 200px###">CLOTURE&nbsp###DE LA DEMANDE</td>
<td>DATE: [dates]</td>
<td>HEURE: [time]</td>
</tr>
</tbody>
</table>
<table style="width: 800px### border: #000000 1pt solid###" border="1" cellspacing="1" cellpadding="5">
<tbody>
<tr>
<td style="width: 200px### background-color: #a8c33c###">OBJET</td>
<td style="background-color: #e1f787###">&nbsp###[res_letterbox.subject]</td>
</tr>
<tr>
<td style="width: 200px### background-color: #a8c33c###">ACTIONS CONDUITES</td>
<td>&nbsp###</td>
</tr>
<tr>
<td style="width: 200px### background-color: #a8c33c###">DATE DE REMISE EN ETAT / SERVICE</td>
<td style="background-color: #e1f787###">&nbsp###</td>
</tr>
<tr>
<td style="width: 200px### background-color: #a8c33c###">CONSIGNES COMPLEMENTAIRES</td>
<td>&nbsp###</td>
</tr>
<tr>
<td style="width: 200px### background-color: #a8c33c###">AUTRES OBSERVATIONS</td>
<td style="background-color: #e1f787###">&nbsp###</td>
</tr>
<tr>
<td style="width: 200px### background-color: #a8c33c###">&nbsp###</td>
<td>&nbsp###</td>
</tr>
<tr>
<td style="width: 200px### background-color: #a8c33c###">&nbsp###</td>
<td style="background-color: #e1f787###">&nbsp###</td>
</tr>
</tbody>
</table>', 'HTML', NULL, NULL, 'DOCX: demo_document_msoffice', '', 'doctypes');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (20, 'Accompagnement courriel', 'Modèle de courriel d''''accompagnement', '<p>Bonjour,</p>
<p>En r&eacute###ponse &agrave### votre courrier en date du [res_letterbox.doc_date], veuillez trouver notre r&eacute###ponse en pi&egrave###ce-jointe.</p>
<p>Cordialement,</p>
<p><strong>Ville de Maarch-les-Bains</strong><br /><em>[user.firstname] [user.lastname]</em><br /><em>[user.phone]</em></p>', 'HTML', NULL, NULL, 'DOCX: standard_nosign', 'letterbox_attachment', 'sendmail', 'all');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (21, 'AR dérogation LO', 'AR derogation carte scolaire', '', 'OFFICE', '0000#', 'ar_derogation.odt', 'ODT: ARderogation', 'letterbox_attachment', 'attachments', 'response_project');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (22, 'Réponse crèche LO', 'Réponse à une demande de place en crèche', '', 'OFFICE', '0000#', 'rep_creche.odt', 'ODT: Repddeplacecreche', 'letterbox_attachment', 'attachments', 'response_project');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (23, 'Réponse générique LO', 'Modèle de réponse générique', '', 'OFFICE', '0000#', 'rep_standard.odt', 'ODT: standard_sign', 'letterbox_attachment', 'attachments', 'response_project');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (24, 'Réponse générique MS', 'Modèle de réponse MS Office', '', 'OFFICE', '0000#', 'rep_standard.docx', 'DOCX: standard_sign', 'letterbox_attachment', 'attachments', 'response_project');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (25, 'AR SVA LO', 'AR SVA LO', '', 'OFFICE', '0000#', 'ar_sva.odt', 'ODT: ar_sva', 'letterbox_attachment', 'attachments', 'sva');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (26, 'AR SVR LO', 'AR SVR LO', '', 'OFFICE', '0000#', 'ar_svr.odt', 'ODT: ar_svr', 'letterbox_attachment', 'attachments', 'svr');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (27, 'Réponse avec transmission LO', 'Réponse avec transmission LO', '', 'OFFICE', '0000#', 'rep_transmission.odt', 'ODT: rep_transmission', 'letterbox_attachment', 'attachments', 'response_project');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (28, 'Transmission LO', 'Transmission LO', '', 'OFFICE', '0000#', 'transmission.odt', 'ODT: transmission', 'letterbox_attachment', 'attachments', 'transmission');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (29, 'Courrier invitation PME LO', 'Courrier invitation PME LO', '', 'OFFICE', '0000#', 'invitation.odt', 'ODT: invitation', 'letterbox_attachment', 'attachments', 'outgoing_mail');
------------
--TEMPLATES réponses mail SVE
--
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (900, '[TRT] Passer me voir', 'Passer me voir', 'Passer me voir à mon bureau, merci.', 'TXT', NULL, NULL, 'XLSX: demo_spreadsheet_msoffice', '', 'notes', 'all');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (901, '[TRT] Compléter', 'Compléter', 'Le projet de réponse doit être complété/révisé sur les points suivants : \n\n- ', 'TXT', NULL, NULL, 'XLSX: demo_spreadsheet_msoffice', '', 'notes', 'all');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (902, '[AVIS] Demande avis', 'Demande avis', 'Merci de me fournir les éléments de langage pour répondre à ce courrier.', 'TXT', NULL, NULL, 'XLSX: demo_spreadsheet_msoffice', '', 'notes', 'all');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (904, '[AVIS] Avis favorable', 'Avis favorable', 'Merci de répondre favorablement à la demande inscrite dans ce courrier', 'TXT', NULL, NULL, 'XLSX: demo_spreadsheet_msoffice', '', 'notes', 'all');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (905, '[CLOTURE] Clôture pour REJET', 'Clôture pour REJET', 'Clôture pour REJET', 'TXT', NULL, NULL, 'XLSX: demo_spreadsheet_msoffice', '', 'notes', 'all');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (906, '[CLOTURE] Clôture pour ABANDON', 'Clôture pour ABANDON', 'Clôture pour ABANDON', 'TXT', NULL, NULL, 'XLSX: demo_spreadsheet_msoffice', '', 'notes', 'all');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (907, '[CLOTURE] Clôture RAS', 'Clôture RAS', 'Clôture NORMALE', 'TXT', NULL, NULL, 'XLSX: demo_spreadsheet_msoffice', '', 'notes', 'all');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (908, '[CLOTURE] Clôture AUTRE', 'Clôture AUTRE', 'Clôture pour ce motif : ', 'TXT', NULL, NULL, 'XLSX: demo_spreadsheet_msoffice', '', 'notes', 'all');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (909, '[REJET] Erreur affectation', 'Erreur affectation', 'Ce courrier ne semble pas concerner mon service', 'TXT', NULL, NULL, 'XLSX: demo_spreadsheet_msoffice', '', 'notes', 'all');
INSERT INTO templates (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (910, '[REJET] Anomalie de numérisation', 'Anomalie de numérisation', 'Le courrier présente des anomalies de numérisation', 'TXT', NULL, NULL, 'XLSX: demo_spreadsheet_msoffice', '', 'notes', 'all');
--
INSERT INTO templates  (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (1000, '[MAIL] AR TYPE Réorientation d’une saisine électronique vers l’autorité compétente', '[MAIL] AR TYPE Réorientation d’une saisine électronique vers l’autorité compétente', '<h2>Ville de Maarch-les-Bains</h2>
<p><em>[entities.adrs_1]</em><br /><em>[entities.adrs_2]</em><br /><em>[entities.zipcode] [entities.city]<br /></em></p>
<p>(Veuillez renseigner le numero de telephone de votre service)</p>
<p>Le [res_letterbox.doc_date], vous avez transmis par voie &eacute###lectronique &agrave### la Ville une demande qui ne rel&egrave###ve pas de sa comp&eacute###tence.</p>
<p>Votre demande concerne [res_letterbox.subject].</p>
<p>Cette demande a &eacute###t&eacute### transmise &agrave### (veuillez renseigner le nom de l''AUTORITE COMPETENTE).</p>', 'HTML', NULL, NULL, 'ODP: open_office_presentation', 'letterbox_attachment', 'sendmail', 'all');
--
INSERT INTO templates  (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (1020, '[MAIL] AR TYPE dans le cas d’une décision implicite de rejet', '[MAIL] AR TYPE dans le cas d’une décision implicite de rejet', '<h2>Ville de Maarch-les-Bains</h2>
<p><em>[entities.adrs_1]</em><br /><em>[entities.adrs_2]</em><br /><em>[entities.zipcode] [entities.city]<br /></em></p>
<p>(Veuillez renseigner le numero de telephone de votre service)</p>
<p>Le [res_letterbox.doc_date], vous avez transmis par voie &eacute###lectronique &agrave### la Ville une demande qui rel&egrave###ve de sa comp&eacute###tence.</p>
<p>Votre demande concerne : [res_letterbox.subject].</p>
<p>Le pr&eacute###sent accus&eacute### r&eacute###ception atteste la r&eacute###ception de votre demande, il ne pr&eacute###juge pas de la conformit&eacute### de son contenu qui d&eacute###pend entre autres de l''&eacute###tude des pi&egrave###ces fournies. Pour tout renseignement concernant votre dossier, vous pouvez contacter le service charg&eacute### du dossier par t&eacute###l&eacute###phone [users.phone] ou par messagerie [users.mail].</p>
<p>Votre demande est susceptible de faire l''objet d''une d&eacute###cision implicite de rejet en l''absence de r&eacute###ponse dans les (XX) jours suivant sa r&eacute###ception, soit le [res_letterbox.process_limit_date].</p>
<p>Si l''instruction de votre demande n&eacute###cessite des informations ou pi&egrave###ces compl&eacute###mentaires, la Ville vous contactera afin de les fournir, dans un d&eacute###lai de production qui sera fix&eacute###.</p>
<p>Dans ce cas, le d&eacute###lai de d&eacute###cision implicite de rejet serait alors suspendu le temps de produire les pi&egrave###ces demand&eacute###es.</p>
<p>Si vous estimez que la d&eacute###cision qui sera prise par l''administration est contestable, vous pourrez formuler :</p>
<p>- Soit un recours gracieux devant l''auteur de la d&eacute###cision</p>
<p>- Soit un recours hi&eacute###rarchique devant le Maire</p>
<p>- Soit un recours contentieux devant le Tribunal Administratif territorialement comp&eacute###tent.</p>
<p>Le recours gracieux ou le recours hi&eacute###rarchique peuvent &ecirc###tre faits sans condition de d&eacute###lais.</p>
<p>Le recours contentieux doit intervenir dans un d&eacute###lai de deux mois &agrave### compter de la notification de la d&eacute###cision.</p>
<p>Toutefois, si vous souhaitez en cas de rejet du recours gracieux ou du recours hi&eacute###rarchique former un recours contentieux, ce recours gracieux ou hi&eacute###rarchique devra avoir &eacute###t&eacute### introduit dans le d&eacute###lai sus-indiqu&eacute### du recours contentieux.</p>
<p>Vous conserverez ainsi la possibilit&eacute### de former un recours contentieux, dans un d&eacute###lai de deux mois &agrave### compter de la d&eacute###cision intervenue sur ledit recours gracieux ou hi&eacute###rarchique.</p>', 'HTML', NULL, NULL, 'ODP: open_office_presentation', 'letterbox_attachment', 'sendmail', 'all');
--
INSERT INTO templates  (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (1010, '[MAIL] AR TYPE dans le cas d’une décision implicite d’acceptation', '[MAIL] AR TYPE dans le cas d’une décision implicite d’acceptation', '<h2>Ville de Maarch-les-Bains</h2>
<p><em>[entities.adrs_1]</em><br /><em>[entities.adrs_2]</em><br /><em>[entities.zipcode] [entities.city]<br /></em></p>
<p>(Veuillez renseigner le numero de telephone de votre service)</p>
<p>Le [res_letterbox.doc_date], vous avez transmis par voie &eacute###lectronique &agrave### la Ville une demande qui rel&egrave###ve de sa comp&eacute###tence.</p>
<p>Votre demande concerne [res_letterbox.subject].</p>
<p>Le pr&eacute###sent accus&eacute### de r&eacute###ception atteste de la r&eacute###ception de votre demande. il ne pr&eacute###juge pas de la conformit&eacute### de son contenu qui d&eacute###pend entre autres de l''''&eacute###tude des pi&egrave###ces fournies.</p>
<p>Pour tout renseignement concernant votre dossier, vous pouvez contacter le service charg&eacute### du dossier par t&eacute###l&eacute###phone [users.phone] ou par messagerie [users.mail].</p>
<p>Votre demande est susceptible de faire l''objet d''''une d&eacute###cision implicite d''''acceptation en l''absence de r&eacute###ponse dans les (XX) jours suivant sa r&eacute###ception, soit le [res_letterbox.process_limit_date].</p>
<p>Si l''instruction de votre demande n&eacute###cessite des informations ou pi&egrave###ces compl&eacute###mentaires, la Ville vous contactera afin de les fournir, dans un d&eacute###lai de production qui sera fix&eacute###.</p>
<p>Le cas &eacute###ch&eacute###ant, le d&eacute###lai de d&eacute###cision implicite d''acceptation ne d&eacute###butera qu''''apr&egrave###s la production des pi&egrave###ces demand&eacute###es.</p>
<p>En cas de d&eacute###cision implicite d''''acceptation vous avez la possibilit&eacute### de demander au service charg&eacute### du dossier une attestation conform&eacute###ment aux dispositions de l''article 22 de la loi n&deg### 2000-321 du 12 avril 2000 relative aux droits des citoyens dans leurs relations avec les administrations modifi&eacute###e.</p>', 'HTML', NULL, NULL, 'ODP: open_office_presentation', 'letterbox_attachment', 'sendmail', 'all');
--
INSERT INTO templates  (template_id, template_label, template_comment, template_content, template_type, template_path, template_file_name, template_style, template_datasource, template_target, template_attachment_type) VALUES (1030, '[MAIL] AR TYPE dans le cas d’une demande n’impliquant pas de décision implicite de l’administration', '[MAIL] AR TYPE dans le cas d’une demande n’impliquant pas de décision implicite de l’administration', '<h2>Ville de Maarch-les-Bains</h2>
<p><em>[entities.adrs_1]</em><br /><em>[entities.adrs_2]</em><br /><em>[entities.zipcode] [entities.city]<br /></em></p>
<p>(Veuillez renseigner le numero de telephone de votre service)</p>
<p>Le [res_letterbox.doc_date], vous avez transmis par voie &eacute###lectronique &agrave### la Ville une demande qui rel&egrave###ve de sa comp&eacute###tence.</p>
<p>Votre demande concerne [res_letterbox.subject].</p>
<p>Le pr&eacute###sent accus&eacute### de r&eacute###ception atteste de la r&eacute###ception de votre demande. Il ne pr&eacute###juge pas de la conformit&eacute### de son contenu qui d&eacute###pend entre autres de l''&eacute###tude des pi&egrave###ces fournies.</p>
<p>Si l''instruction de votre demande n&eacute###cessite des informations ou pi&egrave###ces compl&eacute###mentaires, la Ville vous contactera afin de les fournir, dans un d&eacute###lai de production qui sera fix&eacute###.</p>
<p>Pour tout renseignement concernant votre dossier, vous pouvez contacter le service charg&eacute### du dossier par t&eacute###l&eacute###phone [users.phone] ou par messagerie [users.mail].</p>', 'HTML', NULL, NULL, 'TXT: document_texte', 'letterbox_attachment', 'sendmail', 'all');
------------
Select setval('templates_seq', (select max(template_id)+1 from templates), false);

------------
--NOTIFICATIONS
------------
TRUNCATE TABLE notifications;
INSERT INTO notifications (notification_sid, notification_id, description, event_id, notification_mode, template_id, diffusion_type, diffusion_properties, attachfor_type, attachfor_properties, rss_url_template)
VALUES (1, 'USERS', '[administration] Actions sur les utilisateurs de l''application', 'users%', 'EMAIL', 2, 'user', 'superadmin', '', '', 'http://localhost/maarch_entreprise');
INSERT INTO notifications (notification_sid, notification_id, description, event_id, notification_mode, template_id, rss_url_template, diffusion_type, diffusion_properties, attachfor_type, attachfor_properties, is_enabled)
VALUES (2, 'RET2', '2ie alerte sur courriers en retard', 'alert2', 'EMAIL', 5, '', 'dest_user', '', '', '', 'Y');
INSERT INTO notifications (notification_sid, notification_id, description, event_id, notification_mode, template_id, rss_url_template, diffusion_type, diffusion_properties, attachfor_type, attachfor_properties, is_enabled)
VALUES (3, 'RET1', '1ère alerte sur courriers en retard', 'alert1', 'EMAIL', 6, '', 'dest_user', '', '', '', 'Y');
INSERT INTO notifications (notification_sid, notification_id, description, event_id, notification_mode, template_id, rss_url_template, diffusion_type, diffusion_properties, attachfor_type, attachfor_properties, is_enabled)
VALUES (4, 'BASKETS', 'Notification de bannettes', '', 'EMAIL', 7, '', 'dest_user', '', '', '', 'Y');
INSERT INTO notifications (notification_sid, notification_id, description, event_id, notification_mode, template_id, rss_url_template, diffusion_type, diffusion_properties, attachfor_type, attachfor_properties, is_enabled)
VALUES (5, 'ANC', 'Nouvelle annotation sur courrier en copie', 'noteadd', 'EMAIL', 8, '', 'copy_list', '', '', '', 'Y');
INSERT INTO notifications (notification_sid, notification_id, description, event_id, notification_mode, template_id, rss_url_template, diffusion_type, diffusion_properties, attachfor_type, attachfor_properties, is_enabled)
VALUES (6, 'AND', 'Nouvelle annotation sur courrier destinataire', 'noteadd', 'EMAIL', 8, '', 'dest_user', '', '', '', 'Y');
INSERT INTO notifications (notification_sid, notification_id, description, event_id, notification_mode, template_id, rss_url_template, diffusion_type, diffusion_properties, attachfor_type, attachfor_properties, is_enabled)
VALUES (7, 'RED', 'Redirection de courrier', '1', 'EMAIL', 7, '', 'dest_user', '', '', '', 'Y');
Select setval('notifications_seq', (select max(notification_sid)+1 from notifications), false);

------------
--TEMPLATES_ASSOCIATION
------------
--Rebuild template association for OFFICE documents
------------
TRUNCATE TABLE templates_association;
INSERT INTO templates_association
(template_id, what, value_field, maarch_module)
SELECT template_id, 'destination', entity_id, 'entities'
FROM templates, entities
WHERE template_type in ('OFFICE','TXT');
Select setval('templates_association_seq', (select max(system_id)+1 from templates_association), false);
-----
-- Archive identifiers
-----
UPDATE entities SET business_id = 'org_987654321_Versant';
UPDATE entities SET archival_agency = 'org_123456789_Archives';
UPDATE entities SET archival_agreement = 'MAARCH_LES_BAINS_ACTES';

UPDATE doctypes SET retention_final_disposition = 'destruction';
UPDATE doctypes SET retention_rule = 'compta_3_03';
UPDATE doctypes SET duration_current_use = 12;



--Inscrire ici les clauses de conversion spécifiques en cas de reprise
--Update res_letterbox set status='VAL' where res_id=108;
