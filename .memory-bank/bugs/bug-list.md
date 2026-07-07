# Bug Tracking List

This document logs active and historical bugs in the GNN SMTPMail repository.

## Active Bugs
*No active bugs reported.*

## Resolved Bugs

### 1. Unescaped Table Names in Logger Queries (v1.5.2)
- **Status:** Resolved
- **Confidence:** Verified
- **Issue:** SQL query structures like `CREATE TABLE`, `TRUNCATE TABLE`, and `DROP TABLE` inserted raw table strings without `esc_sql()` and backticks, posing SQL injection risks.
- **Resolution:** Added `esc_sql()` escaping and backtick delimiters to all logger queries.

### 2. File Path Traversal in Email Attachments (v1.5.2)
- **Status:** Resolved
- **Confidence:** Verified
- **Issue:** `file_get_contents()` read email attachments directly without validation, making it vulnerable to local file inclusion (LFI).
- **Resolution:** Used `realpath()` and verified that the file path starts within `ABSPATH`.

### 3. Special Characters Stripped from SMTP Password (v1.5.2)
- **Status:** Resolved
- **Confidence:** Verified
- **Issue:** SMTP password used `sanitize_text_field()` which stripped critical special characters, breaking valid credentials.
- **Resolution:** Replaced sanitization with `wp_unslash` and string type casting.

### 4. Leftover catch Block Syntax Error (v1.5.1)
- **Status:** Resolved
- **Confidence:** Verified
- **Issue:** A leftover `catch` block without a matching `try` block caused a fatal syntax crash upon loading.
- **Resolution:** Removed the orphaned block and restored linear checking.
