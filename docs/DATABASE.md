# Database Migrations

This directory contains SQL migration files for managing database schema changes.

## Directory Structure

```
db/
├── sql/           # SQL migration files
└── seeds/         # Database seeder files (optional)
```

## Quick Start

### Run All Migrations

```bash
./run-migrations.sh
# or
./setup-database.sh
```

### Check Migration Status

```bash
./migrate.sh status
```

This will list all available SQL migration files in `db/sql/`.

## Migration Files

SQL migrations are located in `db/sql/` and are executed in alphabetical/numerical order:

- `001_create_user_table.sql`
- `002_create_protein_table.sql`
- `003_create_cut_table.sql`
- `004_create_flavour_table.sql`
- etc.

See `db/sql/README.md` for the complete list and detailed documentation.

## Creating New Migrations

To add a new migration:

1. Create a new `.sql` file in `db/sql/` with sequential numbering:

   ```bash
   # Example: db/sql/011_add_new_table.sql
   ```

2. Write idempotent SQL using `IF NOT EXISTS`:

   ```sql
   CREATE TABLE IF NOT EXISTS my_new_table (
       id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
       name VARCHAR(255) NOT NULL,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
   ```

3. Run migrations:
   ```bash
   ./run-migrations.sh
   ```

## Best Practices

1. **Always test migrations** - Run on development/testing before production
2. **Use IF NOT EXISTS** - Makes migrations idempotent and safe to re-run
3. **Sequential numbering** - Use 001, 002, 003... for proper ordering
4. **One logical change per file** - Easier to debug and track changes
5. **Never modify existing migrations** - Create new ones for changes
6. **Run migrations as part of deployment** - Automate in CI/CD pipeline
7. **Backup before production** - Always backup your database first

## Environments

Environment settings are read from your `.env` file:

- `DB_HOST` - Database host
- `DB_NAME` - Database name
- `DB_USER` - Database user
- `DB_PASSWORD` - Database password
- `DB_PORT` - Database port (default: 3306)

## Learn More

For detailed information about SQL migrations, see `db/sql/README.md`.
