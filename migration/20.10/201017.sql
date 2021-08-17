-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 20.10.16 to 20.10.17                              --
--                                                                          --
--                                                                          --
-- *************************************************************************--
--DATABASE_BACKUP|res_mark_as_read
ALTER TABLE res_attachments ADD COLUMN original_filename CHARACTER VARYING(255);
ALTER TABLE res_letterbox ADD COLUMN original_filename CHARACTER VARYING(255);
UPDATE baskets SET  basket_id = 'MyBasket' WHERE  basket_id = 'ParafBasket';

--refresh function fOR main entity
CREATE OR REPLACE FUNCTION entity_by_res_id(id BIGINT) RETURNS VARCHAR AS $BODY$
DECLARE
	entity VARCHAR;
    typist BIGINT;
	res VARCHAR;
BEGIN
	SELECT r.typist INTO typist FROM res_view_letterbox r WHERE res_id = id;
	SELECT entity_id  INTO entity FROM users_entities WHERE user_id = typist limit 1;
	SELECT entity_tree(entity) INTO res;
	RETURN res;
END;
$BODY$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION entity_tree(entity VARCHAR) RETURNS VARCHAR AS $BODY$
DECLARE
	parentid VARCHAR;
	res VARCHAR;
BEGIN
	SELECT parent_entity_id INTO parentid FROM entities WHERE entity_id = $1;
	IF parentid IS NULL OR parentid = '' THEN
		RETURN $1;
	ELSE
		SELECT entity_tree(parentid) INTO res;
		RETURN res;
	END IF;
END;
$BODY$ LANGUAGE plpgsql;

UPDATE parameters SET param_value_string = '20.10.17' WHERE id = 'database_version';
