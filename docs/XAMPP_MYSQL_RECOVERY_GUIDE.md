# XAMPP MariaDB Recovery Guide for MCARE

This guide is for the Windows/XAMPP database used by MCARE at `C:\xampp\mysql\data` and Laravel database `mcare_db`.

## Current healthy state (August 19, 2026)

- MariaDB is running normally on `127.0.0.1:3306`.
- `mcare_db` was exported, rebuilt on clean storage, and verified.
- All 28 pre-recovery MCARE tables passed `mysqlcheck` before the latest migrations.
- All 34 Laravel migrations now show as `Ran`.
- Raw pre-repair backup: `C:\xampp\mysql\data-backup-20260819-1150`
- Retained damaged data directory: `C:\xampp\mysql\data-corrupt-20260819`
- Logical SQL backup: `C:\xampp\mysql\recovery-backups\mcare_db-recovery-20260819.sql`

Keep those recovery copies until the team has used the restored system successfully for several days and has made a newer verified backup.

## Important rules

1. Stop MySQL before copying or repairing its raw data files.
2. Never delete `ibdata1`, `ib_logfile0`, `ib_logfile1`, or the whole `data` directory as a quick fix.
3. Never overwrite `mcare_db` before making both a raw data-folder backup and a logical SQL dump when possible.
4. Use `innodb_force_recovery` only temporarily to export data. It is not a repair mode and must not remain enabled.
5. Read `mysql_error.log` first. A blocked port, crashed Aria table, and damaged InnoDB storage require different fixes.

## Step 1: Identify the failure

Open PowerShell as Administrator and run:

```powershell
Get-Process mysqld -ErrorAction SilentlyContinue
Get-NetTCPConnection -LocalPort 3306 -ErrorAction SilentlyContinue |
    Select-Object LocalAddress, LocalPort, State, OwningProcess
Get-Content C:\xampp\mysql\data\mysql_error.log -Tail 100
```

- If another process owns port `3306`, identify it before stopping or reconfiguring it.
- If no process owns `3306`, the error log is the main source of truth.
- Do not repeatedly click Start while MariaDB is crashing.

## Step 2: Stop MySQL and create a raw backup

Use XAMPP Control Panel to stop MySQL. If it is responsive, this command also performs a clean shutdown:

```powershell
C:\xampp\mysql\bin\mysqladmin.exe -u root shutdown
```

Confirm that `mysqld` is gone, then copy the data directory:

```powershell
Get-Process mysqld -ErrorAction SilentlyContinue

$stamp = Get-Date -Format 'yyyyMMdd-HHmm'
Copy-Item C:\xampp\mysql\data `
    "C:\xampp\mysql\data-backup-$stamp" `
    -Recurse
```

Do not continue if the backup fails.

## Step 3A: Fix a crashed Aria table

Use this branch only when the log says a table such as `mysql.db` is marked as crashed. MariaDB must be stopped.

```powershell
Set-Location C:\xampp\mysql\data
C:\xampp\mysql\bin\aria_chk.exe --check C:\xampp\mysql\data\mysql\db.MAI
C:\xampp\mysql\bin\aria_chk.exe --recover --backup C:\xampp\mysql\data\mysql\db.MAI
C:\xampp\mysql\bin\aria_chk.exe --check C:\xampp\mysql\data\mysql\db.MAI
```

Replace `db.MAI` only with the `.MAI` file named in the error log.

If `aria_chk` reports that the file does not have the correct index definition, stop. Do not force it. A matching clean system table or a full logical rebuild is required. In the August 19 incident, XAMPP's built-in `backup\mysql\db.*` had an identical `db.frm` schema and was validated before only that system table was replaced.

## Step 3B: Handle future LSN or InnoDB page errors

Messages such as `log sequence number is in the future`, `page corruption`, or `doesn't exist in engine` mean the raw tablespace and redo logs are inconsistent. Do not delete the redo logs.

Start with MariaDB's lowest recovery level and disable stale replication startup:

```powershell
C:\xampp\mysql\bin\mysqld.exe `
    --defaults-file=C:\xampp\mysql\bin\my.ini `
    --standalone `
    --console `
    --skip-slave-start `
    --innodb-force-recovery=1
```

In a second Administrator PowerShell window, export MCARE:

```powershell
New-Item -ItemType Directory -Force C:\xampp\mysql\recovery-backups

C:\xampp\mysql\bin\mysqldump.exe -u root `
    --single-transaction `
    --quick `
    --routines `
    --triggers `
    --events `
    --hex-blob `
    --force `
    --result-file=C:\xampp\mysql\recovery-backups\mcare_db-recovery.sql `
    mcare_db
```

Confirm that the dump is non-empty and ends with `Dump completed`, then stop recovery mode:

```powershell
Get-Item C:\xampp\mysql\recovery-backups\mcare_db-recovery.sql
Get-Content C:\xampp\mysql\recovery-backups\mcare_db-recovery.sql -Tail 10
C:\xampp\mysql\bin\mysqladmin.exe -u root shutdown
```

If recovery level `1` cannot export the database, do not jump directly to level `6`. Higher levels increase risk and should be used only for data extraction with a current raw backup.

The durable fix is to initialize clean MariaDB storage, import the verified SQL dump, test every table on an isolated port, and only then promote it to `C:\xampp\mysql\data`. Keep the damaged directory for rollback.

## Step 4: Verify the repaired database

Start MySQL from XAMPP and run:

```powershell
C:\xampp\mysql\bin\mysqladmin.exe -u root ping
C:\xampp\mysql\bin\mysqlcheck.exe -u root --databases mcare_db

Set-Location D:\Mcare-hub\mcare-hub-dev
php artisan migrate
php artisan migrate:status
```

Expected results:

- `mysqld is alive`
- Every MCARE table reports `OK`
- Every Laravel migration reports `Ran`
- `http://127.0.0.1:8000/` loads without a database exception

## Prevention

- Stop MySQL from XAMPP before shutting down Windows.
- Avoid ending `mysqld.exe` from Task Manager unless it is completely unresponsive.
- Keep regular logical dumps outside the Git repository.
- Do not copy only `ibdata1` or only the redo logs between data directories.
- After adding migrations, run `php artisan migrate` and `php artisan migrate:status` before sharing the branch.

Official references:

- [MariaDB Aria table checker](https://mariadb.com/docs/server/clients-and-utilities/aria-clients-and-utilities/aria_chk)
- [MariaDB InnoDB recovery modes](https://mariadb.com/docs/server/server-usage/storage-engines/innodb/innodb-troubleshooting/innodb-recovery-modes)
- [MariaDB InnoDB redo log](https://mariadb.com/docs/server/server-usage/storage-engines/innodb/innodb-redo-log)
