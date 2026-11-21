---
name: add-crud-resource
description: Add a new CRUD resource (model, migration, factory, seeder, form request, resource controller, routes, Blade views, Pest tests) following the conventions of the existing constituents, captains, taxes and criminal records. Use when the user asks for a new record type or screen.
---

# Adding a CRUD resource

Model it on the closest existing resource: `Constituent` for a top-level record, `Tax` or
`CriminalRecord` for a record that belongs to a constituent. Read that resource's model,
request, controller, views and test before writing.

## 1. Scaffold (in `make shell`)

```bash
php artisan make:model BusinessPermit -mfsc --requests --no-interaction
```

That creates the model, migration, factory, seeder, resource controller and Store/Update
requests. Keep one request class if store and update share rules (as `ConstituentRequest` does);
keep two only when they differ.

## 2. Migration

- Plural snake_case table; `foreignId('constituent_id')->constrained()->cascadeOnDelete()` for a
  child record (or `nullOnDelete()` when the parent must survive, as captains do).
- Money `decimal(12, 2)`; months `unsignedTinyInteger` 1 to 12; enums as strings backed by a PHP enum.
- Every uniqueness or integrity rule as an index or constraint, named, then mirrored in the request.

## 3. Model

- `#[Fillable([...])]` listing exactly the writable columns; `casts()` for decimals, dates and enums.
- Typed relations with `@return BelongsTo<Parent, $this>` PHPDoc; the inverse `HasMany` on the parent.
- Filters as `#[Scope]` methods; derived values as accessors, never stored columns.
- `@property` PHPDoc block like the existing models (Larastan reads it).

## 4. Request

Rules for every field; `Rule::enum()` for enums, `Rule::exists()` for foreign keys,
`Rule::unique(...)->ignore($this->route('<param>'))` for uniqueness on update, and a `messages()`
entry that turns each database constraint into a sentence.

## 5. Controller and routes

Thin, typed methods (`View` / `RedirectResponse`), route-model binding, `$request->validated()`
only, redirect with a `status` flash. Register inside the `['auth', 'verified']` group in
`routes/web.php`:

```php
Route::resource('constituents.business-permits', BusinessPermitController::class)
    ->shallow()->except(['index', 'show']);
```

## 6. Views

`resources/views/<resource>/` with `create`, `edit` (and `index`/`show` if top-level) sharing a
`_form.blade.php`. Use `<x-layouts.app>`, `<x-ui.page-header>`, `<x-ui.card>`, `<x-form.input|select|textarea>`
(they handle `old()` and errors), `<x-ui.delete-button>`. `@csrf` on every form; `@method('PUT')`/`@method('DELETE')`.
Link to the new screens from the parent's `show` page.

## 7. Data

Factory with realistic Philippine data (reuse `database/factories/Support/PhilippineAddress.php`);
extend `BarangaySeeder` with chunked bulk inserts if the dashboard or demo should show it.

## 8. Tests

A `tests/Feature/<Resource>Test.php` using `asAdmin()`: guest redirect, create, validation
rejection per rule, update, delete (and what it cascades to), and the parent relationship. If the
resource appears on the listing or dashboard, the existing query-count tests must stay green.

## 9. Finish

Run the `verify` skill.
