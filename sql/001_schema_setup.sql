-- Cotizador de Construcción — aislamiento en la base compartida "door_to_door"
-- Correr conectado con la External Connection String de la base (el usuario admin
-- de Render, NO el rol dedicado que se crea aquí). Reemplaza 'CAMBIA_ESTA_PASSWORD'
-- por una contraseña fuerte antes de ejecutar.

-- 1. Rol dedicado, solo para este proyecto.
CREATE ROLE cotizador_user WITH LOGIN PASSWORD 'CAMBIA_ESTA_PASSWORD';

-- 2. Permiso para conectarse a la base compartida.
GRANT CONNECT ON DATABASE door_to_door TO cotizador_user;

-- 3. Permiso para crear objetos nuevos en la base (su propio schema, y lo que
--    sus migraciones necesiten más adelante). NO da acceso a los schemas de
--    Door To Door ni de MyBarberShop.
GRANT CREATE ON DATABASE door_to_door TO cotizador_user;

-- 4. Crear el schema del proyecto (sin AUTHORIZATION: evita el error
--    "must be able to SET ROLE" si el usuario admin no tiene membership sobre
--    cotizador_user) y darle todos los privilegios al rol dedicado sobre él.
CREATE SCHEMA cotizador;
GRANT ALL PRIVILEGES ON SCHEMA cotizador TO cotizador_user;

-- 5. search_path por defecto del rol: prioriza el schema propio, cae a "public"
--    después (para funciones/extensiones ya instaladas ahí por otra app, p. ej.
--    pgcrypto, sin tener que reinstalarlas).
ALTER ROLE cotizador_user IN DATABASE door_to_door SET search_path TO cotizador, public;

-- ---------------------------------------------------------------------------
-- Verificación de aislamiento (correr esto conectado YA como cotizador_user,
-- no como admin):
--
--   SELECT current_schemas(true);
--   -- debe mostrar: {cotizador,public}
--
--   SELECT * FROM public.<alguna_tabla_de_door_to_door_o_mybarbershop> LIMIT 1;
--   -- debe fallar con "permission denied for table ..." — si NO falla, algo
--   -- quedó mal configurado y hay que revisar los GRANT anteriores.
-- ---------------------------------------------------------------------------
