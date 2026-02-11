-- Initial database schema for Anniversary Reminder
-- Run: psql -d anniversary_reminder -f init.sql

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    zalo_uid VARCHAR(64) UNIQUE NOT NULL,
    display_name VARCHAR(128),
    avatar_url TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS anniversary_groups (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(256) NOT NULL,
    share_code VARCHAR(8) UNIQUE,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS group_subscriptions (
    id SERIAL PRIMARY KEY,
    group_id INTEGER NOT NULL REFERENCES anniversary_groups(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(group_id, user_id)
);

CREATE TABLE IF NOT EXISTS anniversaries (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    person_name VARCHAR(256) NOT NULL,
    relationship VARCHAR(128),
    lunar_day SMALLINT NOT NULL CHECK (lunar_day BETWEEN 1 AND 30),
    lunar_month SMALLINT NOT NULL CHECK (lunar_month BETWEEN 1 AND 12),
    lunar_year SMALLINT,
    notes TEXT,
    is_recurring BOOLEAN DEFAULT TRUE,
    source VARCHAR(16) DEFAULT 'manual',
    group_id INTEGER REFERENCES anniversary_groups(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS default_events (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    event_type VARCHAR(16) NOT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    UNIQUE(user_id, event_type)
);

CREATE TABLE IF NOT EXISTS reminders (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    anniversary_id INTEGER REFERENCES anniversaries(id) ON DELETE CASCADE,
    event_type VARCHAR(16),
    solar_date DATE NOT NULL,
    remind_date DATE NOT NULL,
    days_before SMALLINT NOT NULL,
    status VARCHAR(16) DEFAULT 'pending',
    sent_at TIMESTAMPTZ
);
CREATE INDEX IF NOT EXISTS idx_reminders_date ON reminders(remind_date, status);

CREATE TABLE IF NOT EXISTS chat_messages (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role VARCHAR(16) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Migration for existing data: create default groups and assign anniversaries
-- Run this after adding the new tables to an existing database:
--
-- INSERT INTO anniversary_groups (user_id, name, is_default)
-- SELECT id, 'Chung', TRUE FROM users
-- WHERE id NOT IN (SELECT user_id FROM anniversary_groups WHERE is_default = TRUE);
--
-- UPDATE anniversaries SET group_id = (
--     SELECT ag.id FROM anniversary_groups ag
--     WHERE ag.user_id = anniversaries.user_id AND ag.is_default = TRUE
-- ) WHERE group_id IS NULL;
