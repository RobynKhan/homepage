-- ============================================================================
-- breakout_scores.sql — Breakout Game Scores Table
-- ============================================================================
--
-- Run this migration on the Supabase PostgreSQL database before enabling the
-- Breakout panel.  One row per admin username; upserted automatically by
-- breakout_api.php on first score submission.
-- ============================================================================

CREATE TABLE IF NOT EXISTS breakout_scores (
    username        text        PRIMARY KEY,
    total_score     bigint      NOT NULL DEFAULT 0,
    best_run_score  bigint      NOT NULL DEFAULT 0,
    best_run_at     timestamptz,
    updated_at      timestamptz NOT NULL DEFAULT now()
);
