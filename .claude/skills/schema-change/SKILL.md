---
name: schema-change
description: Add, change or remove a database column or constraint safely in this Laravel app (new migration, model fillable/casts, validation, factory, seeder, derived accessors, query budgets, Neon rollout). Use for any change under database/migrations or to a model's attributes.
---

# Changing the schema

## Rules

- **Never edit a committed migration.** It may have run on Neon already. Add a new one
  (`php artisan make:migration add_x_to_y_table` in `make shell`). A PreToolUse hook enforces this.
- **Don't store what you can derive.** If the value follows from other rows, add an accessor and
  an aggregate in a scope instead (see `Constituent::withListingAggregates()`).
- **Constraints in the database, sentences in validation.** Each new unique index, foreign key or
  check gets a matching rule and message in the FormRequest.
- **Portable SQL.** The suite must pass on PostgreSQL and SQLite; avoid raw DDL and engine-only functions.

## Checklist

1. Migration with a working `down()`. Foreign keys via `constrained()` with an explicit
   `cascadeOnDelete()` / `nullOnDelete()`. Name indexes you will refer to.
2. Model: add to `#[Fillable([...])]`, `casts()`, and the `@property` PHPDoc.
3. FormRequest rules and messages; form fields in the `_form.blade.php` partial.
4. Factory and `BarangaySeeder` so `make fresh` still produces realistic data.
5. Tests for the new rule, including the database constraint and its validation message.
6. If the listing or dashboard reads the column, keep the query-count tests green.
7. `make migrate`, then the `verify` skill.

## Rolling out

Production does not migrate on deploy (`RUN_MIGRATIONS=false` in `render.yaml`): the service uses
Neon's pooled endpoint, which breaks `artisan migrate`. Before deploying code that needs the new
schema, run the migrations against Neon's **direct** endpoint. Order matters for destructive changes:
add a column, deploy code that writes both, backfill, deploy code that stops reading the old one,
then drop it in a later migration.
