-- Fix Rental Dates & Status - Run on Production Database
-- Date: 2026-02-24
-- Purpose: 
--   1. Restore DATETIME for start_date/end_date (was reverted to DATE, causing time loss)
--   2. Add 'partial_return' back to status ENUM (was accidentally removed)

-- Fix 1: Restore DATETIME columns to preserve pickup/return times
ALTER TABLE rentals MODIFY COLUMN start_date DATETIME NULL;
ALTER TABLE rentals MODIFY COLUMN end_date DATETIME NULL;

-- Fix 2: Add 'partial_return' to status ENUM
ALTER TABLE rentals MODIFY COLUMN status ENUM('quotation', 'confirmed', 'active', 'completed', 'cancelled', 'late_pickup', 'late_return', 'partial_return') NOT NULL DEFAULT 'quotation';

-- Record migration
INSERT INTO migrations (migration, batch) VALUES ('2026_02_24_140300_fix_rentals_datetime_and_status', (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) as m));
