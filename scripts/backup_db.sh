#!/bin/bash

# Configuration
BACKUP_DIR="/home/armando/capital_gestor/storage/backups"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/gestor_capital_db_${DATE}.sql.gz"
DB_USER="root"
DB_PASS="Carlosaraiza_91"
DB_NAME="gestor_capital_db"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Perform backup
echo "[$(date)] Starting MySQL backup for database '${DB_NAME}'..."
mysqldump -u "$DB_USER" -p"$DB_PASS" --single-transaction --routines --triggers "$DB_NAME" | gzip > "$BACKUP_FILE"

if [ $? -eq 0 ]; then
    echo "[$(date)] Backup completed successfully: ${BACKUP_FILE}"
    
    # Remove backups older than 14 days
    find "$BACKUP_DIR" -type f -name "*.sql.gz" -mtime +14 -delete
    echo "[$(date)] Cleaned up backups older than 14 days."
else
    echo "[$(date)] ERROR: Backup failed!" >&2
    exit 1
fi
