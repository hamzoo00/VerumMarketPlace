#!/bin/bash
# Creates the dedicated test database on first container init,
# so `make test` works without manual setup.
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    SELECT 'CREATE DATABASE verum_testing OWNER $POSTGRES_USER'
    WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'verum_testing')\gexec
EOSQL
