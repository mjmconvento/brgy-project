-- Runs once, on the first initialisation of the postgres-data volume
-- (postgres:18 executes every *.sql in /docker-entrypoint-initdb.d as the
-- POSTGRES_USER superuser, connected to POSTGRES_DB).
--
-- The primary application database is created by the image itself from
-- POSTGRES_DB / POSTGRES_USER / POSTGRES_PASSWORD. This file only adds the
-- parallel database that phpunit.xml points at, so feature tests exercise real
-- PostgreSQL foreign keys and ON DELETE CASCADE / SET NULL behaviour rather
-- than an in-memory SQLite approximation.
--
-- IMPORTANT: an init script cannot read compose environment variables, so the
-- owner below is hard-coded to `brgy`. Keep POSTGRES_USER=brgy (i.e.
-- DB_USERNAME=brgy) in compose.yaml / .env, or this will fail.
--
-- CREATE DATABASE has no IF NOT EXISTS in PostgreSQL, but this script only ever
-- runs on a fresh data directory, so an unconditional statement is correct.

CREATE DATABASE brgy_testing OWNER brgy;
