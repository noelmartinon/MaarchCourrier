-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 21.03.5 to 21.03.13                             --
--                                                                          --
--                                                                          --
-- *************************************************************************--
--DATABASE_BACKUP|shippings|shipping_templates

ALTER TABLE shippings DROP COLUMN IF EXISTS history;
ALTER TABLE shippings ADD COLUMN history jsonb DEFAULT '[]'::jsonb;
ALTER TABLE shippings DROP COLUMN IF EXISTS sending_id;
ALTER TABLE shippings ADD COLUMN sending_id CHARACTER VARYING(40);
ALTER TABLE shippings DROP COLUMN IF EXISTS attachments;
ALTER TABLE shippings ADD COLUMN attachments jsonb DEFAULT '[]'::jsonb;
ALTER TABLE shippings DROP COLUMN IF EXISTS action_id;
ALTER TABLE shippings ADD COLUMN action_id INTEGER;

ALTER TABLE shipping_templates DROP COLUMN IF EXISTS subscriptions;
ALTER TABLE shipping_templates ADD COLUMN subscriptions jsonb DEFAULT '[]'::jsonb;
ALTER TABLE shipping_templates DROP COLUMN IF EXISTS token_min_iat;
ALTER TABLE shipping_templates ADD COLUMN token_min_iat TIMESTAMP WITHOUT TIME ZONE DEFAULT now();

UPDATE parameters SET param_value_string = '21.03.13' WHERE id = 'database_version';
