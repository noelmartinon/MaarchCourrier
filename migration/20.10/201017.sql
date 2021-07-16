-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 20.10.16 to 20.10.17                              --
--                                                                          --
--                                                                          --
-- *************************************************************************--
--DATABASE_BACKUP|res_mark_as_read

ALTER TABLE res_letterbox ADD COLUMN IF NOT EXISTS original_filename VARCHAR;
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

--function to solve issue : 17014
CREATE OR REPLACE FUNCTION migrate_expeditor_data() RETURNS VARCHAR AS $BODY$
DECLARE
	dest JSONB;
	expe JSONB;
	res VARCHAR;
	r RECORD;
BEGIN
	FOR r IN SELECT * FROM res_letterbox
    LOOP
    	IF r.dest_user IS NULL THEN
    		raise notice 'resid % do nothing',r.res_id;
		ELSE
			SELECT ('"' || lastname || ' ' || firstname || '"')::JSONB INTO expe FROM users WHERE id = r.dest_user;
			SELECT ('"' || entity_by_res_id(r.res_id) || '"')::JSONB INTO dest;
			UPDATE res_letterbox SET custom_fields = jsonb_set(custom_fields,'{"20"}', expe) WHERE res_id = r.res_id RETURNING custom_fields INTO res;
			UPDATE res_letterbox SET custom_fields = jsonb_set(custom_fields,'{"18"}', dest) WHERE res_id = r.res_id RETURNING custom_fields INTO res;
			raise notice  '%', res::TEXT;
		END IF;
    END LOOP;
    RETURN 'END';
END;
$BODY$ LANGUAGE plpgsql;


SELECT migrate_expeditor_data();
DROP FUNCTION migrate_expeditor_data;

UPDATE parameters SET param_value_string = '20.10.17' WHERE id = 'database_version';
