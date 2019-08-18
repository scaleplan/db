CREATE OR REPLACE FUNCTION execute_multiple(queries text[], pool_size int, conn_str text) RETURNS varchar AS $$
DECLARE
  idx int;
  active_connections int;
  new_connections int;
  i int;
  c text;
  result varchar;
BEGIN
  idx := 0;
  result := '';
  WHILE true LOOP
    FOR c IN SELECT n
             FROM unnest(dblink_get_connections()) n
             WHERE n LIKE '%' LOOP
      IF dblink_is_busy(c) = 0 THEN
        BEGIN
          PERFORM *
          FROM dblink_get_result(c)AS tmp(status text);
          EXCEPTION WHEN datatype_mismatch THEN NULL;
                    WHEN others THEN result := CONCAT(result, c,  ',');
        END;
        PERFORM dblink_disconnect(c);
      END IF;
    END LOOP;

    SELECT INTO active_connections COUNT(n)
    FROM unnest(dblink_get_connections()) n
    WHERE n LIKE '%';

    new_connections := pool_size - active_connections;
    FOR i IN 1..new_connections LOOP
      idx := idx + 1;
      EXIT WHEN idx > array_upper(queries, 1);
      PERFORM dblink_connect('' || idx, conn_str);
      PERFORM dblink_send_query('' || idx, queries[idx]);
    END LOOP;

    EXIT WHEN active_connections = 0
              AND idx > array_upper(queries, 1);
  END LOOP;

  RETURN rtrim(result, ',');
END;
$$ LANGUAGE plpgsql;
