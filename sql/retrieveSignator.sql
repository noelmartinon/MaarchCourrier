CREATE TYPE emp as
(
    user_migrated int,
    detected_line int
);

create or replace function update_listinstance_delegate() returns emp AS $BODY$
DECLARE 
    rec record;
    id_user int;
    i int;
    a int;
    nb_user int;
    contenu varchar;
    nom varchar;
    prenom varchar;
begin
    a = 0;
    i = 0;
    create temp table x as select listinstance_id,btrim(split_part(ltrim(process_comment,'Action de:'),'--',1),' ')as col from listinstance where process_comment ilike 'Action de%';
    update x set col = btrim(substr(col,14),' ') where col ilike '%Action de%';
    FOR rec in SELECT listinstance_id,col from x
    loop
        a = a + 1;
        select split_part(rec.col,' ',2), split_part(rec.col,' ',1) into nom,prenom from x;
        if nom = '' or prenom = '' then
            raise INFO 'LAKE OF INFORMATION FOR listinstance_id : %',rec.listinstance_id ;
            continue;
        end if;
        select count(user_id) into nb_user from users where lastname = nom and firstname = prenom;
        if nb_user > 1 then
            raise DEBUG 'USER % % WAS DOUBLE CHECKED',nom, prenom using HINT = '--> MANUAL ACTION NEEDED FOR listinstance_id : %' || rec.listinstance_id;
            continue;
        end if;
        select user_id into id_user from users where lastname = nom and firstname = prenom;
        if id_user isnull then
            raise WARNING 'USER % % NOT FOUND listinstance_id : %',nom, prenom,rec.listinstance_id;
            continue;
        end if;
        --raise notice 'user id : %' , id_user;
        i = i + 1;
        update listinstance set delegate = id_user where listinstance_id = rec.listinstance_id;
    END LOOP;
    return (i,a);
END;
$BODY$ LANGUAGE plpgsql;
