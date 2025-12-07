# Audit Logging Guide

## Overview

The API now includes comprehensive audit logging for all critical operations. This creates an audit trail for compliance, debugging, and security monitoring.

## What Gets Logged

### Price Changes

- **Flavour price updates** - Tracks old and new prices
- **Cut price updates** - Tracks old and new prices

### Data Modifications

- **Protein creation** - Who created what
- **Protein deletion** - Who deleted what
- **Flavour associations** - When flavours are added to proteins
- **Cut associations** - When cuts are added to proteins

### Security Events

- Login attempts (success/failure)
- Token refresh
- API key generation
- Unauthorized access attempts

## Log Format

Audit logs are stored in JSON format in `/logs/app-YYYY-MM-DD.log`:

```json
{
  "message": "AUDIT: Flavour price updated",
  "context": {
    "audit_type": "data_change",
    "action": "update_flavour_price",
    "protein_id": "123",
    "flavour_id": "456",
    "old_price": "29.99",
    "new_price": "34.99",
    "user_id": "789",
    "timestamp": "2025-12-05 14:23:45"
  },
  "level": 200,
  "level_name": "INFO",
  "channel": "app",
  "datetime": "2025-12-05T14:23:45.123456+00:00"
}
```

## Viewing Audit Logs

### Via API Endpoint

```bash
# Get latest audit logs
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  "http://your-domain/api/logs?type=app&lines=100"

# Filter by date
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  "http://your-domain/api/logs?type=app&date=2025-12-05&lines=50"
```

### Via Command Line

```bash
# View all audit entries
grep "AUDIT:" logs/app-2025-12-05.log

# View price changes only
grep "price updated" logs/app-2025-12-05.log

# View actions by specific user
grep "user_id\":\"789" logs/app-2025-12-05.log
```

### Via Dashboard

Use the log viewer API endpoints to build dashboard widgets:

- Recent price changes
- User activity timeline
- Critical operations log
- Security events

## Audit Actions Reference

| Action                   | Description               | Context Fields                                        |
| ------------------------ | ------------------------- | ----------------------------------------------------- |
| `create_protein`         | New protein added         | protein_id, protein_name, user_id                     |
| `delete_protein`         | Protein removed           | protein_id, protein_name, user_id                     |
| `update_flavour_price`   | Flavour price changed     | protein_id, flavour_id, old_price, new_price, user_id |
| `update_cut_price`       | Cut price changed         | protein_id, cut_id, old_price, new_price, user_id     |
| `add_flavour_to_protein` | Flavour linked to protein | protein_id, flavour_id, price, user_id                |
| `add_cut_to_protein`     | Cut linked to protein     | protein_id, cut_id, price, user_id                    |

## Best Practices

1. **Review regularly** - Check audit logs for unexpected changes
2. **Set up alerts** - Monitor for suspicious patterns
3. **Retain logs** - Keep logs for compliance (current: 14 days)
4. **Include user_id** - Always track who made changes
5. **Log before/after** - Capture old and new values for changes

## Performance Considerations

- Audit logging is **asynchronous** - doesn't slow down API responses
- Logs are rotated daily (configurable in Logger.php)
- JSON format enables easy parsing and analysis
- Consider log aggregation tools for production (ELK, Datadog, etc.)

## Adding Audit Logging to New Operations

```php
// In your controller method
$this->logger->audit('Your action description', [
    'action' => 'action_identifier',
    'resource_id' => $id,
    'old_value' => $oldValue,
    'new_value' => $newValue,
    'user_id' => $request->getAttribute('user_id') ?? null
]);
```

## Compliance Notes

Audit logs help meet requirements for:

- **SOC 2** - Access control and data modification tracking
- **GDPR** - Data processing records
- **PCI DSS** - Transaction logging
- **HIPAA** - Access audit controls (if applicable)

## Query Examples

### Find all price changes in the last hour

```bash
grep -A 2 "AUDIT.*price updated" logs/app-$(date +%Y-%m-%d).log | \
  tail -n 100
```

### Export audit trail for specific user

```bash
grep "user_id\":\"123" logs/app-*.log > user_123_audit.json
```

### Count operations by type

```bash
grep "AUDIT:" logs/app-*.log | \
  grep -o '"action":"[^"]*"' | \
  sort | uniq -c | sort -rn
```
