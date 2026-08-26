#!/bin/bash
# ═══════════════════════════════════════════════════════════
# Secure Research Portal — Automated backup
# Runs as root via cron:  0 2 * * *  /usr/local/bin/backup.sh
#
# Pipeline: mysqldump → tar uploads → tar nginx config →
#           rsync to isolated backup server → cleanup
# Self-logging to /var/log/backup.log
#
# RPO: 24 hours (daily 02:00 run)
# ═══════════════════════════════════════════════════════════

DATE=$(date +%F-%H%M)
BACKUP_DIR="/backups/staging"

# --- Backup server connection details ---------------------
# Dedicated key, dedicated account, no sudo on the receiving end.
# Replace <backup-server-ip> with your backup host's address.
REMOTE_USER="backupuser"
REMOTE_HOST="<backup-server-ip>"
REMOTE_DIR="/received-backups"
SSH_OPTS="-p 2222 -i /root/.ssh/backup_key"

LOG="/var/log/backup.log"

mkdir -p "$BACKUP_DIR"

echo "[$DATE] Starting backup..." >> "$LOG"

# 1. Backup Database
mysqldump -u root researchportal > "$BACKUP_DIR/db_$DATE.sql"
echo "[$DATE] DB backup done" >> "$LOG"

# 2. Backup uploaded files
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" \
    /var/www/researchportal/uploads/
echo "[$DATE] Files backup done" >> "$LOG"

# 3. Backup Nginx config
tar -czf "$BACKUP_DIR/config_$DATE.tar.gz" \
    /etc/nginx/
echo "[$DATE] Config backup done" >> "$LOG"

# 4. Send to the isolated backup server via rsync over SSH
rsync -avz -e "ssh $SSH_OPTS" \
    "$BACKUP_DIR/" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_DIR/"
echo "[$DATE] Rsync to backup server done" >> "$LOG"

# 5. Clean local staging older than 7 days
find "$BACKUP_DIR" -type f -mtime +7 -delete
echo "[$DATE] Backup complete" >> "$LOG"
