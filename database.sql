CREATE DATABASE IF NOT EXISTS page_analyzer;
USE page_analyzer;

CREATE TABLE IF NOT EXISTS urls (
    id bigint PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    name varchar(255) NOT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS url_checks (
    id bigint PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    url_id bigint REFERENCES urls (id) ON DELETE CASCADE,
    status_code integer,
    h1 varchar(255),
    title varchar(255),
    description text,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE url_checks DROP COLUMN IF EXISTS keywords;
