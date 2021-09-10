-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 20.03.22 to 20.03.23                            --
--                                                                          --
--                                                                          --
-- *************************************************************************--

--- SGAMI-SO DEBUT TCKT_18164

-- Status
INSERT INTO public.status(
	 id, label_status, is_system, img_filename, maarch_module, can_be_searched, can_be_modified)
	VALUES ('INDEX','Indexation', 'N', 'fm-letter-status-new', 'apps', 'Y', 'Y');

-- actions
INSERT INTO public.actions(
	keyword, label_action, id_status, is_system, action_page, component, history, parameters)
	VALUES ( '', 'Indexation courrier', 'INDEX', 'N', 'confirm_status', 'confirmAction', 'Y', '{}');

-- indexBasket
INSERT INTO public.baskets(
	 coll_id, basket_id, basket_name, basket_desc, basket_clause, is_visible, enabled, basket_order, color, basket_res_order, flag_notif)
	VALUES ('letterbox_coll', 'IndexBasket', 'Corbeille Indexation courrier', 'Corbeille Indexation courrier', 'status=''INDEX''', 'Y', 'Y', null, null, 'res_id desc', 'N');

-- groupbasket
INSERT INTO groupbasket (group_id, basket_id, list_display, list_event, list_event_data) VALUES ('AGENT', 'IndexBasket', '{"templateColumns":7,"subInfos":[{"value":"getPriority","cssClasses":[],"icon":"fa-traffic-light"},{"value":"getCategory","cssClasses":[],"icon":"fa-exchange-alt"},{"value":"getDoctype","cssClasses":[],"icon":"fa-suitcase"},{"value":"getAssignee","cssClasses":[],"icon":"fa-sitemap"},{"value":"getRecipients","cssClasses":[],"icon":"fa-user"},{"value":"getSenders","cssClasses":[],"icon":"fa-book"},{"value":"getCreationAndProcessLimitDates","cssClasses":["align_rightData"],"icon":"fa-calendar"}]}', 'processDocument', '{"defaultTab":"dashboard"}');

-- actions_groupbasket
--INSERT INTO actions_groupbaskets ( where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES ( '', 'AGENT', 'IndexBasket', 'Y', 'Y', 'N');
-- actions_groupbasket
INSERT INTO actions_groupbaskets ( id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES ( '19'  ,'', 'AGENT', 'IndexBasket', 'Y', 'Y', 'Y');
INSERT INTO actions_groupbaskets ( id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES ( '20'  ,'', 'AGENT', 'IndexBasket', 'Y', 'Y', 'N');
INSERT INTO actions_groupbaskets ( id_action, where_clause, group_id, basket_id, used_in_basketlist, used_in_action_page, default_action_list) VALUES ( '21'  ,'', 'AGENT', 'IndexBasket', 'Y', 'Y', 'N');

DO $$
DECLARE varIndex int;
BEGIN
  varIndex := (SELECT id FROM actions WHERE id_status = 'INDEX');
  UPDATE usergroups	SET indexation_parameters=('{"actions": ["' || varIndex || '"], "entities": [], "keywords": ["ALL_ENTITIES"]}')::jsonb	WHERE group_id='AGENT';
END;
$$;

-- users_baskets_preferences

INSERT INTO users_baskets_preferences (user_serial_id, group_serial_id, basket_id, display)
select distinct usergroup_content.user_id, usergroups.id, 'IndexBasket', TRUE FROM usergroups, usergroup_content, groupbasket 
WHERE groupbasket.group_id = usergroups.group_id 
AND usergroups.id = usergroup_content.group_id 
and usergroup_content.group_id = (select distinct  id FROM usergroups WHERE group_id = 'AGENT');


UPDATE parameters SET param_value_string = '20.03.23_TMA1' WHERE id = 'database_version';


	
--- SGAMI-SO FIN 