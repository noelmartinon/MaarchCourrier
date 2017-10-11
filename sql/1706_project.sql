ALTER TABLE baskets DROP COLUMN IF EXISTS color;
ALTER TABLE baskets ADD color character varying(16);
ALTER TABLE entities ADD entity_full_name text;
