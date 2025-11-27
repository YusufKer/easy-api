# Integration Test Setup

## Prerequisites

1. **Create a test database:**
   ```sql
   CREATE DATABASE your_test_database;
   ```

2. **Configure test environment:**
   - Copy `.env.test` and update with your actual test database credentials:
     ```
     DB_HOST=localhost
     DB_NAME=your_test_database
     DB_USER=your_username
     DB_PASSWORD=your_password
     ```

3. **Make sure your test database has the same schema as your production database.**
   Run your database migrations/schema on the test database.

## Running Tests

**Run all tests:**
```bash
./vendor/bin/phpunit
```

**Run only unit tests:**
```bash
./vendor/bin/phpunit --testsuite Unit
```

**Run only integration tests:**
```bash
./vendor/bin/phpunit --testsuite Integration
```

**Run a specific test file:**
```bash
./vendor/bin/phpunit tests/Integration/ProteinApiTest.php
```

## What's Tested

The integration tests verify:
- ✅ Creating proteins via POST /api/protein
- ✅ Retrieving all proteins via GET /api/protein
- ✅ Retrieving protein by ID via GET /api/protein/{id}
- ✅ Deleting proteins via DELETE /api/protein/{id}
- ✅ Validation errors for empty protein names
- ✅ 404 responses for non-existent proteins
- ✅ Actual database reads and writes

## Important Notes

- Integration tests use a **separate test database** (never your production database!)
- Tests clean up after themselves (delete data in `setUp()` and `tearDown()`)
- Auth middleware is disabled for these tests to simplify testing
- Each test is independent and can run in any order
