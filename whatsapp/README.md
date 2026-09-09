# 📲 Sanruth ERP — WhatsApp Notification Module

This directory contains the complete **Meta WhatsApp Business Cloud API** integration and notification engine for Sanruth ERP.

---

## 📁 Directory Structure

```text
whatsapp/
├── config.php            # Main Meta API Helper & Notification Engine (Credentials, Transport & Event Wrappers)
├── setup_schema.php      # Database Migration script for `whatsapp_notification_log` table
├── test_admin_events.php # Interactive Admin Web Test Suite & Live Meta Template Approval Inspector
├── test_api.php          # Connection & API test runner
└── README.md             # Module Documentation & API Reference
```

---

## ⚙️ Configuration

Credentials and configuration settings are defined in [`whatsapp/config.php`](file:///c:/handover/billing_sanruth-main/whatsapp/config.php):

- **`META_PHONE_NUMBER_ID`**: Meta WhatsApp Phone Number ID
- **`META_ACCESS_TOKEN`**: Meta Graph API Permanent System User Token
- **`ADMIN_WHATSAPP_NUMBER`**: Recipient phone number for admin notifications (`917418735076`)
- **`META_API_VERSION`**: Meta Graph API version (`v20.0`)

---

## 🔔 Event-Specific Notification Wrappers

| Function | Template Name | Language | Parameters |
| :--- | :--- | :--- | :--- |
| `send_job_card_notification()` | `job_card_created` | `en_US` | `{{1}}` CardNo, `{{2}}` Name, `{{3}}` Phone, `{{4}}` City |
| `send_stock_reorder_notification()` | `stock_reorder_alert` | `en_US` | `{{1}}` Item, `{{2}}` Barcode, `{{3}}` Stock, `{{4}}` Reorder Level |
| `send_sales_order_notification()` | `sales_order_created` | `en_US` | `{{1}}` OrderID, `{{2}}` Name, `{{3}}` Phone, `{{4}}` WhatsApp, `{{5}}` Amount |
| `send_purchase_notification()` | `purchase_order_created` | `en_US` | `{{1}}` OrderID, `{{2}}` Supplier, `{{3}}` Product Summary, `{{4}}` Amount |
| `send_daily_sales_report_notification()` | `daily_sales_report` | `en` | `{{1}}` Date, `{{2}}` Orders, `{{3}}` Total Sales, `{{4}}` Cost, `{{5}}` Profit |

---

## 🚀 Key Features

1. **Parameter Sanitization**:
   Automatically converts tabs (`\t`) and line breaks (`\r\n`, `\n`) into clean inline separators (` • `) to prevent Meta API Error 132018 (`Param text cannot have new-line/tab characters`).

2. **Automatic Language Fallback**:
   If a template request receives Meta Error 132001 (language code mismatch), the engine automatically retries with alternate code (`en_US` ↔ `en`).

3. **Idempotency & Duplicate Suppression**:
   Prevents duplicate notifications for the same event and document by checking `idempotency_key` against `whatsapp_notification_log`.

4. **Non-Blocking Execution**:
   Isolated HTTP transport with cURL, stream_context, and system `curl.exe` fallbacks. Failures in WhatsApp delivery will never roll back database transactions in ERP.

---

## 🧪 Testing & Administration

- **Live Test Suite**: Open `http://localhost:1000/whatsapp/test_admin_events.php` in your browser.
- **Database Logs**: Inspect delivery status and errors in the `whatsapp_notification_log` MySQL table.
