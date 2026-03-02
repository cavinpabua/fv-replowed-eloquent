# FV Replowed (Modified)

## Changes

- Uses Laravel's Eloquent ORM
- Realtime chat powered by Laravel Reverb
- Artisan commands:
  - `quest:parse` - Parse quest XML and populate quests table
  - `chat:cleanup` - Delete chat messages older than 7 days
  - `db:backup` - Backup database to Backblaze B2
  - `discord:status` - Update Discord server status message
  - `world:cleanup-deleted` - Hard delete soft-deleted world objects

## Installation

Run the installer script:
```bash
./installer.sh
```

**Important:** Change the admin password in `app/Http/Controllers/AdminController.php` (line 21).

## Apache2 Configuration

An example Apache2 configuration is included at `apache2-config`.

## Credits

This project is based on [FV-Replowed](https://github.com/FV-Replowed/fv-replowed).

Original credits:
- kehayeah: PHP work and reverse engineering
- puccamite.tech: Dehasher development
- rabbetsbigday: Additional technical advising
