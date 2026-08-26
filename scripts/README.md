# scripts/

Operational scripts for backup and disaster recovery.

| Script | Purpose | Runs as | Schedule |
|---|---|---|---|
| `backup.sh` | mysqldump → tar uploads → tar nginx config → rsync to backup server | root | cron `0 2 * * *` |
| `restore.sh` | 7-step automated recovery (detect → find backup → restore DB/files/config → restart → verify) | root | on-demand |

## Install

```bash
sudo cp backup.sh restore.sh /usr/local/bin/
sudo chmod 700 /usr/local/bin/backup.sh /usr/local/bin/restore.sh
sudo chown root:root /usr/local/bin/backup.sh /usr/local/bin/restore.sh

# Schedule the backup
echo '0 2 * * * /usr/local/bin/backup.sh >> /var/log/backup.log 2>&1' | sudo tee /etc/cron.d/researchportal-backup
sudo chmod 644 /etc/cron.d/researchportal-backup
```

## Restore — the 7 steps

```text
STEP 0  Detect whether recovery is needed (DB, files, services)
STEP 1  Find the latest backup (local staging → backup server fallback)
STEP 2  Stop services (nginx, php8.3-fpm)
STEP 3  Restore database + re-grant least-privilege user
STEP 4  Restore uploaded files
STEP 5  Restore Nginx config
STEP 6  Restart services (mariadb → php8.3-fpm → nginx)
STEP 7  Verify portal is back (HTTP 200)
```

## Live DR test (validated)

```text
$ curl -Ik https://<server-ip>                    # Step A — portal healthy (HTTP 200)
$ sudo mysql -u root -e "DROP DATABASE researchportal;"
$ curl https://<server-ip>                        # Step B — confirmed down
$ sudo /usr/local/bin/restore.sh                  # Steps 0–7 run automatically
$ curl -Ik https://<server-ip>                    # Step C — HTTP 200 restored, RTO <5 min
```

Recovery time observed: **under 5 minutes** (RTO target: 30 minutes).

## Placeholders to replace

- `<backup-server-ip>` — your isolated backup host
- `<server-ip>` — the web server IP used for the recovery health check
