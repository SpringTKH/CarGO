-- =====================================================================
-- CarGO Payment Data Remediation (Phase 2.1)
-- Safe, non-destructive migration script to mask legacy card numbers
-- and bank account numbers in the `orders` table.
--
-- INSTRUCTIONS FOR DBA / USER:
-- 1. Review the statements below before manual execution.
-- 2. This script is idempotent: running it multiple times is completely safe.
-- 3. No tables or columns are dropped. No non-empty data is deleted.
-- =====================================================================

-- Step 1: Mask unmasked card numbers for card payments
-- Preserves the last 4 digits for customer reference while redacting the rest.
-- Skips records that are already in the standard masked format.
UPDATE orders 
SET card_number = CONCAT('**** **** **** ', RIGHT(TRIM(card_number), 4))
WHERE payment_method = 'card'
  AND card_number IS NOT NULL
  AND TRIM(card_number) != ''
  AND card_number NOT LIKE '**** **** **** %'
  AND LENGTH(TRIM(card_number)) >= 4;

-- Step 2: Mask unmasked bank account numbers for bank transfers
-- Preserves the last 4 digits for customer reference while redacting the rest.
-- Skips records that are already in the standard masked format.
UPDATE orders 
SET bank_account = CONCAT('******', RIGHT(TRIM(bank_account), 4))
WHERE payment_method = 'bank'
  AND bank_account IS NOT NULL
  AND TRIM(bank_account) != ''
  AND bank_account NOT LIKE '******%'
  AND LENGTH(TRIM(bank_account)) >= 4;

-- Step 3: Normalize empty strings to NULL on an individual column basis
-- Each column is updated independently when its own value is empty string ('').
-- Avoids compound OR conditions to guarantee that no unrelated non-empty
-- values are ever cleared or modified.
UPDATE orders SET card_number = NULL WHERE card_number = '';
UPDATE orders SET cardholder_name = NULL WHERE cardholder_name = '';
UPDATE orders SET bank_name = NULL WHERE bank_name = '';
UPDATE orders SET bank_account = NULL WHERE bank_account = '';
