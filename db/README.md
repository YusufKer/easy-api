# Database Migrations

This directory contains Phinx migration files for managing database schema changes.

## Directory Structure

```
db/
├── migrations/     # Migration files
└── seeds/         # Database seeder files
```

## Quick Start

### Check Migration Status

```bash
./migrate.sh status
```

### Run All Pending Migrations

```bash
./migrate.sh migrate
```

### Rollback Last Migration

```bash
./migrate.sh rollback
```

### Create New Migration

```bash
./migrate.sh create AddEmailVerifiedToUsers
```

## Using Phinx Directly

You can also use Phinx commands directly:

```bash
# Check status
vendor/bin/phinx status

# Run migrations
vendor/bin/phinx migrate

# Rollback
vendor/bin/phinx rollback

# Create migration
vendor/bin/phinx create MyNewMigration

# Specify environment
vendor/bin/phinx migrate -e production
```

## Environments

- `development` - Local development (default)
- `testing` - Test database
- `production` - Production database

Environment settings are configured in `phinx.php` and read from your `.env` file.

## Migration Files

Migrations are named with timestamps to ensure proper ordering:

- `20251205000001_create_user_table.php`
- `20251205000002_create_protein_table.php`
- etc.

## Creating Custom Migrations

To create a new migration:

```bash
./migrate.sh create AddColumnToTable
```

This generates a file in `db/migrations/` with the current timestamp. Edit the file to define your schema changes:

```php
<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddColumnToTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('your_table');
        $table->addColumn('new_column', 'string', ['limit' => 255])
              ->update();
    }
}
```

## Best Practices

1. **Always test migrations** - Run on development/testing before production
2. **Use `change()` method** - Phinx can automatically reverse most operations
3. **Make migrations reversible** - If using `up()`/`down()`, ensure rollback works
4. **One logical change per migration** - Easier to debug and rollback
5. **Never modify existing migrations** - Create new ones for changes
6. **Run migrations as part of deployment** - Automate in CI/CD pipeline

## Rollback Safety

Before rolling back in production:

1. Backup your database
2. Test rollback in staging environment
3. Verify data integrity after rollback

## Troubleshooting

### Migration Already Exists

If you see "migration already exists", check the `phinxlog` table:

```sql
SELECT * FROM phinxlog;
```

### Reset Migrations (Development Only)

⚠️ **WARNING: This will drop all tables!**

```bash
./migrate.sh rollback-all
./migrate.sh migrate
```

## Learn More

- [Phinx Documentation](https://book.cakephp.org/phinx/0/en/index.html)
- [Writing Migrations](https://book.cakephp.org/phinx/0/en/migrations.html)
- [Database Seeding](https://book.cakephp.org/phinx/0/en/seeding.html)
