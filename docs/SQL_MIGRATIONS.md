# SQL Migrations

This project uses plain SQL files for database migrations instead of an ORM migration tool.

## Migration Files

All migration files are located in `db/sql/` and are executed in alphabetical order.

**Current migrations:**

- `001_create_user_table.sql` - User accounts
- `002_create_protein_table.sql` - Protein types (beef, chicken, etc.)
- `003_create_cut_table.sql` - Meat cuts (ribeye, drumstick, etc.)
- `004_create_flavour_table.sql` - Flavours/seasonings
- `005_create_protein_cut_table.sql` - Junction table for protein-cut relationships with pricing
- `006_create_protein_flavour_table.sql` - Junction table for protein-flavour relationships with pricing
- `007_create_refresh_token_table.sql` - JWT refresh tokens
- `008_create_order_table.sql` - Customer orders
- `009_create_order_item_table.sql` - Order line items with price snapshots
- `010_create_order_status_history_table.sql` - Order status change audit trail
- `011_create_user_details_table.sql` - User personal details (first name, last name, phone)
- `012_create_user_address.sql` - User addresses with support for multiple address types (billing/shipping)

## Running Migrations

### Using the migration script:

```bash
./run-migrations.sh
```

This will:

1. Load database credentials from `.env` or `.env.development`
2. Execute all SQL files in `db/sql/` in order
3. Report success/failure for each migration

### Manual execution:

```bash
mysql -u your_user -p your_database < db/sql/001_create_user_table.sql
mysql -u your_user -p your_database < db/sql/002_create_protein_table.sql
# ... etc
```

Or run all at once:

```bash
cat db/sql/*.sql | mysql -u your_user -p your_database
```

## Creating New Migrations

1. Create a new `.sql` file in `db/sql/` with a sequential number prefix:

   ```
   013_add_new_feature.sql
   ```

2. Write your SQL with `CREATE TABLE IF NOT EXISTS` or `ALTER TABLE` statements

3. Run `./run-migrations.sh` to apply changes

## Important Notes

- All tables use `INT UNSIGNED` for primary keys
- Foreign key columns must also be `INT UNSIGNED` to match
- Tables use `utf8mb4_unicode_ci` collation for Unicode support
- Timestamps default to `CURRENT_TIMESTAMP` with auto-update on modification
- Use `IF NOT EXISTS` to make migrations idempotent (safe to run multiple times)
