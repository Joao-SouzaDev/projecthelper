# ProjectHelper GLPI Plugin - AI Coding Guide

## Project Overview

A GLPI plugin that adds project management helpers: automatic followup replication between project tickets and project progress bars. Built for GLPI 10.0.0+ using PHP 7.4+.

## Architecture & Structure

### GLPI Plugin Lifecycle

- **Entry point**: `hook.php` defines version, prerequisites, and registers hooks
- **Installation**: `setup.php` delegates to `src/Install.php` for backward compatibility
- **Namespace**: `GlpiPlugin\Projecthelper` (autoloaded from `src/`)
- **Config page**: `front/config.form.php` (registered via `$PLUGIN_HOOKS['config_page']`)

### Core Components

```
hook.php              → Plugin registration & hook definitions
src/
  Install.php         → DB schema setup (glpi_plugin_projecthelper_configs table)
  Config.php          → Configuration model (extends CommonDBTM)
  FollowupHandler.php → Followup replication logic (hook: item_add on ITILFollowup)
  TaskHandler.php     → Task replication logic (hook: item_add on TicketTask)
front/
  config.form.php     → Admin UI for plugin settings
```

## Critical Patterns

### Hook Registration Pattern

All hooks are defined in `plugin_init_projecthelper()` in `hook.php`:

```php
$PLUGIN_HOOKS['item_add']['projecthelper'] = [
    'ITILFollowup' => [\GlpiPlugin\Projecthelper\FollowupHandler::class, 'afterAddFollowup'],
    'TicketTask' => [\GlpiPlugin\Projecthelper\TaskHandler::class, 'afterAddTask']
];
```

### Database Access

- Use `global $DB;` - GLPI's database abstraction
- Direct SQL queries (no ORM) - see `FollowupHandler.php` methods
- Table names: prefix with `glpi_` (standard tables) or `glpi_plugin_projecthelper_` (plugin tables)
- Key tables:

  - `glpi_itils_projects`: links tickets to projects (mode 1)
  - `glpi_tickets_tickets`: parent/child relationships (modes 2 & 3)
    - `tickets_id_1`: child ticket
    - `tickets_id_2`: parent ticket
    - `link = 3`: parent/child relationship type
  - `glpi_itilfollowups`: stores followups
  - `glpi_tickettasks`: stores ticket tasks### Configuration Storage

- Single row (id=1) in `glpi_plugin_projecthelper_configs`
- Fields:
  - `show_progress_bar` (boolean)
  - `replicate_followups` (VARCHAR(50), comma-separated modes: e.g., "1,2,4")
  - `replicate_tasks` (VARCHAR(50), comma-separated modes: e.g., "1,2,4")
- Modes: 0=No, 1=All project tickets, 2=Parent to children, 3=Child to parent, 4=Related tickets
- Multiple selections supported (v1.3.0+): Users can combine multiple modes
- Access via `Config::getFromDB(1)`

### Recursion Protection

`FollowupHandler` uses static flag `$is_replicating` to prevent infinite loops when replicating followups triggers more `item_add` hooks.

## Followup Replication Logic

**Three replication modes**:

1. **Mode 1 (value=1)**: Replicate to all project tickets

   - Flow: User adds followup → checks `glpi_itils_projects` → finds all tickets in same project → replicates to each
   - Only for tickets linked to projects

2. **Mode 2 (value=2)**: Replicate from parent to children

   - Flow: User adds followup to parent → checks `glpi_tickets_tickets` → finds all children (tickets_id_2 = parent, link = 3) → replicates to each
   - Independent of project links

3. **Mode 3 (value=3)**: Replicate from child to parent

   - Flow: User adds followup to child → checks `glpi_tickets_tickets` → finds parent (tickets_id_1 = child, link = 3) → replicates to parent
   - Independent of project links

4. **Mode 4 (value=4)**: Replicate to related tickets ✨ **NEW (v1.3.0)**
   - Flow: User adds followup → checks `glpi_tickets_tickets` → finds all related tickets (link = 2, bidirectional) → replicates to each
   - Independent of project links or hierarchy
   - Bidirectional: works both ways in the relationship

**Multiple selections (v1.3.0+)**:

- Users can select multiple modes simultaneously (e.g., "1,4" for project + related)
- Stored as comma-separated string in database
- Code processes each mode and removes duplicates via `array_unique()`
- Example: `explode(',', '1,2,4')` → `[1, 2, 4]` → loop processes each mode

**Key constraints**:

- Only for `ITILFollowup` with `itemtype='Ticket'`
- Maintains original: author (`users_id`), timestamp (`date`), privacy (`is_private`)
- Recursion protection via `$is_replicating` static flag

## Development Workflows

### Testing

- Test scripts in `tests/` (manual CLI execution, not PHPUnit yet)
- Run: `php tests/test_followup_replication.php` (adjust GLPI path in script)
- Tests check: config access, DB structure, manual replication simulation

### Debugging

- Debug logs in `FollowupHandler::logDebug()` are commented out by default
- Uncomment `error_log()` line to enable file logging

### Building/Release

- Uses GLPI standard `PluginsMakefile.mk` (included from GLPI root)
- Version defined in `hook.php` `plugin_version_projecthelper()`
- Metadata in `projecthelper.xml` (often outdated - trust `hook.php`)

## Followup Replication Logic

**Flow**: User adds followup → `item_add` hook fires → checks config → finds project via `glpi_itils_projects` → gets all project tickets → replicates followup to each

**Key constraints**:

- Only replicates when `replicate_followups == 1`
- Only for `ITILFollowup` with `itemtype='Ticket'`
- Maintains original: author (`users_id`), timestamp (`date`), privacy (`is_private`)
- Excludes source ticket from replication targets

## Code Conventions

### Naming

- Classes: PascalCase (e.g., `FollowupHandler`)
- Methods: camelCase (e.g., `afterAddFollowup`)
- DB fields: snake_case (e.g., `replicate_followups`)
- Plugin key: lowercase, no separators (`projecthelper`)

### File Headers

All PHP files require:

```php
<?php
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}
```

Namespace declarations after the security check.

### GLPI Integration

- Extend `CommonDBTM` for models that map to DB tables
- Override `getTable()` to specify custom table names
- Use `Session::checkRight('config', UPDATE)` for permission checks
- Use `Html::header()` and `Html::footer()` for admin pages
- Use `Dropdown::showYesNo()` and `Dropdown::showFromArray()` for form fields

## Migration & Updates

Schema changes handled in `Install::update()`:

- Check version with `version_compare($old_version->getVersion(), 'X.Y.Z', '<')`
- Use `Migration` class for ALTER TABLE operations
- Example: v1.0.1 migrated `replicate_followups` from VARCHAR to TINYINT

## Important Files to Reference

- `hook.php` - All plugin hooks and version info
- `src/FollowupHandler.php` - Complete replication implementation
- `FOLLOWUP_REPLICATION.md` - Detailed feature documentation
- `front/config.form.php` - Example of GLPI admin form patterns
