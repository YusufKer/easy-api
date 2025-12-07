# Migration System - Now Using Plain SQL! ✅

## ⚠️ IMPORTANT: Phinx Has Been Removed

This project has been migrated from Phinx to plain SQL migration files for simplicity and better control.

## What Has Changed

### 1. **Plain SQL Migration Files**

All migrations are now simple SQL files in `db/sql/`:

```
db/sql/
├── 001_create_user_table.sql
├── 002_create_protein_table.sql
├── 003_create_cut_table.sql
├── 004_create_flavour_table.sql
├── 005_create_protein_cut_table.sql
├── 006_create_protein_flavour_table.sql
├── 007_create_refresh_token_table.sql
├── 008_create_order_table.sql
├── 009_create_order_item_table.sql
└── 010_create_order_status_history_table.sql
```

### 2. **Simple Migration Runner**

- `setup-database.sh` - Main migration script (runs all SQL files in order)
- `migrate.sh` - Compatibility wrapper (redirects to setup-database.sh)
- `run-migrations.sh` - Alternative simple runner

### 3. **Phinx Removed**

- `robmorgan/phinx` removed from composer.json
- 17 dependencies removed (CakePHP, Symfony packages, etc.)
- Old Phinx migration files remain in `db/migrations/` for reference

### 4. **Documentation**

- `db/sql/README.md` - Complete guide to SQL migrations

## 📋 How to Use

### Run All Migrations

```bash
./setup-database.sh
# or
./migrate.sh
```

### Check Migration Status

```bash
./migrate.sh status
# or manually
ls -1 db/sql/*.sql
```

### Create a New Migration

1. Create a new SQL file with sequential numbering:
   ```bash
   nano db/sql/011_add_new_feature.sql
   INSERT INTO phinxlog (version, migration_name, start_time, end_time, breakpoint) VALUES
   (20251205000001, 'CreateUserTable', NOW(), NOW(), 0),
   (20251205000002, 'CreateProteinTable', NOW(), NOW(), 0),
   (20251205000003, 'CreateCutTable', NOW(), NOW(), 0),
   (20251205000004, 'CreateFlavourTable', NOW(), NOW(), 0),
   (20251205000005, 'CreateProteinCutTable', NOW(), NOW(), 0),
   (20251205000006, 'CreateProteinFlavourTable', NOW(), NOW(), 0),
   (20251205000007, 'CreateRefreshTokenTable', NOW(), NOW(), 0);
   EOF
   ```

````

Or run this PHP script:

```bash
php -r "
require 'vendor/autoload.php';
\$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
\$dotenv->load();
\$pdo = new PDO(
    'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME'),
    getenv('DB_USER'),
    getenv('DB_PASSWORD')
);
\$migrations = [
    ['version' => '20251205000001', 'name' => 'CreateUserTable'],
    ['version' => '20251205000002', 'name' => 'CreateProteinTable'],
    ['version' => '20251205000003', 'name' => 'CreateCutTable'],
    ['version' => '20251205000004', 'name' => 'CreateFlavourTable'],
    ['version' => '20251205000005', 'name' => 'CreateProteinCutTable'],
    ['version' => '20251205000006', 'name' => 'CreateProteinFlavourTable'],
    ['version' => '20251205000007', 'name' => 'CreateRefreshTokenTable'],
];
foreach (\$migrations as \$m) {
    \$stmt = \$pdo->prepare('INSERT INTO phinxlog (version, migration_name, start_time, end_time, breakpoint) VALUES (?, ?, NOW(), NOW(), 0)');
    \$stmt->execute([\$m['version'], \$m['name']]);
    echo \"Marked {$m['name']} as complete\n\";
}
echo \"All migrations marked as complete!\n\";
"
````

### Option B: Fresh Start (Development Only)

⚠️ **WARNING: This will delete all data!**

```bash
# Drop all tables
mysql -u your_user -p easybraai -e "DROP DATABASE easybraai; CREATE DATABASE easybraai;"

# Run migrations
./migrate.sh migrate
```

## 🚀 Using the Migration System

### Check Status

```bash
./migrate.sh status
```

### Create New Migration

```bash
./migrate.sh create AddEmailVerifiedToUsers
```

This creates a new file like: `db/migrations/20251205120000_add_email_verified_to_users.php`

Edit it:

```php
<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddEmailVerifiedToUsers extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('user');
        $table->addColumn('email_verified_at', 'timestamp', ['null' => true])
              ->update();
    }
}
```

Then run:

```bash
./migrate.sh migrate
```

### Rollback Last Migration

```bash
./migrate.sh rollback
```

### Specify Environment

```bash
./migrate.sh migrate -e production
./migrate.sh status -e testing
```

## 📝 Next Steps for Production Readiness

1. **Mark existing migrations as complete** (see Option A above)
2. **Test the system** by creating a new migration
3. **Add to deployment process**:
   ```bash
   git pull
   composer install --no-dev
   ./migrate.sh migrate -e production
   ```
4. **Update `.gitignore`** to exclude old migrations folder if desired
5. **Document for your team** in README.md

## 🎯 Benefits You Now Have

✅ **Version Control** - Track which migrations have run  
✅ **Rollback Capability** - Undo changes safely  
✅ **Team Collaboration** - Everyone stays in sync  
✅ **Automated Deployment** - Migrations run automatically  
✅ **Multiple Environments** - Dev, testing, production configs  
✅ **Audit Trail** - Know exactly what changed and when

## 📚 Migration Examples for Your TODO Items

### For Hashed Refresh Tokens (#6)

```bash
./migrate.sh create AddHashedTokenToRefreshTokens
```

### For Email Verification (#36)

```bash
./migrate.sh create AddEmailVerifiedAtToUsers
```

### For Soft Deletes (#31)

```bash
./migrate.sh create AddDeletedAtToAllTables
```

## 🔍 Troubleshooting

### "Table already exists" error

Your tables exist from old SQL files. Use Option A to mark migrations as complete.

### Check what's in phinxlog

```bash
mysql -u your_user -p easybraai -e "SELECT * FROM phinxlog;"
```

### Reset everything (dev only)

```bash
./migrate.sh rollback-all
./migrate.sh migrate
```

## 📖 Learn More

- Run `./migrate.sh help` for all commands
- See `db/README.md` for detailed documentation
- [Phinx Documentation](https://book.cakephp.org/phinx/0/en/index.html)

---

**Migration system is now fully implemented and ready to use!** 🎉
