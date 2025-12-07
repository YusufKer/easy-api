# Database Seeds

This directory contains SQL seed files to populate the database with initial data.

## Seed Files

- `001_seed_proteins.sql` - Protein types (Beef, Chicken, Lamb, etc.)
- `002_seed_cuts.sql` - Meat cuts (Fillet, Ribeye, Leg, Thigh, etc.)
- `003_seed_flavours.sql` - Flavours (Lemon and Herb, Peri-Peri, Spicy BBQ, etc.)

## Running Seeds

To populate your database with seed data:

```bash
./seed-database.sh
```

This will execute all seed files in numerical order.

## Features

- **Idempotent**: Uses `ON DUPLICATE KEY UPDATE` so seeds can be run multiple times safely
- **Ordered**: Files are executed in alphabetical/numerical order
- **Safe**: Won't create duplicates or cause errors if data already exists

## Adding New Seeds

1. Create a new `.sql` file in `db/seeds/` with sequential numbering:

   ```
   004_seed_new_data.sql
   ```

2. Use `INSERT ... ON DUPLICATE KEY UPDATE` for idempotency:

   ```sql
   INSERT INTO `table_name` (`column`) VALUES
       ('Value 1'),
       ('Value 2')
   ON DUPLICATE KEY UPDATE `column` = VALUES(`column`);
   ```

3. Run the seed script:
   ```bash
   ./seed-database.sh
   ```
