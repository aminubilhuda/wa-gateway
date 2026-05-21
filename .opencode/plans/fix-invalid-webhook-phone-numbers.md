# Fix: Reject Invalid Long Phone Numbers in Webhook

## Problem

The webhook auto-reply system in `WebhookController.php` automatically creates contacts from every incoming WhatsApp message. The phone number validation only checks **minimum 5 digits** with **no maximum limit**, allowing invalid long numbers like `62134217862242352` (17 digits) to be saved as contacts.

These numbers are typically:
- WhatsApp LID (Local ID) numbers from business/automation accounts
- Malformed sender identifiers from the gateway

## Root Cause

**File:** `app/Http/Controllers/WebhookController.php`
**Method:** `message()`
**Lines:** 90-97

Current validation:
```php
if (empty($cleanNumber) || strlen($cleanNumber) < 5) {
```

This allows any number with 5+ digits, including 17-digit garbage numbers.

## Solution

### Change 1: Add Maximum Length Validation (ITU-T E.164 Standard)

**Location:** `app/Http/Controllers/WebhookController.php`, lines 90-97

**Replace:**
```php
        // Normalize phone number - strip non-digits first
        $cleanNumber = preg_replace('/[^0-9]/', '', $sender);

        // Handle edge cases for LID numbers or malformed input
        if (empty($cleanNumber) || strlen($cleanNumber) < 5) {
            Log::warning('Invalid sender number received', ['sender' => $sender, 'raw_sender' => $rawSender]);

            return response()->json(['success' => false, 'message' => 'Invalid sender number.']);
        }
```

**With:**
```php
        // Normalize phone number - strip non-digits first
        $cleanNumber = preg_replace('/[^0-9]/', '', $sender);

        // Handle edge cases for LID numbers or malformed input
        // Valid phone numbers are 9-15 digits (ITU-T E.164 standard)
        if (empty($cleanNumber) || strlen($cleanNumber) < 9 || strlen($cleanNumber) > 15) {
            Log::warning('Invalid sender number received', ['sender' => $sender, 'raw_sender' => $rawSender, 'cleaned_length' => strlen($cleanNumber)]);

            return response()->json(['success' => false, 'message' => 'Invalid sender number.']);
        }
```

### Why These Numbers?

| Constraint | Value | Reason |
|------------|-------|--------|
| Minimum | 9 digits | Smallest valid Indonesian number: `6281234567` (10 digits). 9 is the absolute minimum for any country code + number. |
| Maximum | 15 digits | ITU-T E.164 international standard maximum. No valid phone number exceeds 15 digits. |

### Validation Examples

| Input | Cleaned | Length | Result |
|-------|---------|--------|--------|
| `6281234567890` | `6281234567890` | 13 | PASS |
| `081234567890` | `081234567890` | 12 | PASS (normalized to 628... later) |
| `+6281234567890` | `6281234567890` | 13 | PASS |
| `62134217862242352` | `62134217862242352` | 17 | **REJECTED** |
| `12345` | `12345` | 5 | **REJECTED** |
| `lid_abc123` | `` | 0 | **REJECTED** |

### Impact

- Numbers like `62134217862242352` (17 digits) will be **rejected** and logged
- All valid Indonesian numbers (10-13 digits) will continue to work
- The webhook will return `success: false` for invalid numbers instead of creating bogus contacts
- Log entries will include `cleaned_length` for easier debugging

### Testing

After applying the fix, verify with:
```bash
composer run test
```

All 22 existing tests should pass (they use valid phone numbers like `6281234567890`).

### Cleanup (Optional)

After deploying this fix, you may want to delete the existing invalid contact from the database:
```sql
-- In SQLite:
DELETE FROM contacts WHERE LENGTH(phone_number) > 15;
```

Or via artisan tinker:
```bash
php artisan tinker
>>> \App\Models\Contact::whereRaw('LENGTH(phone_number) > 15')->delete();
```
