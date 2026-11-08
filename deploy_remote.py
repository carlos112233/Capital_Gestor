import pexpect
import sys
import os

def run_remote_cmd(cmd, timeout=60):
    server_ip = os.getenv("DEPLOY_SERVER_IP", "206.81.14.81")
    ssh_user = os.getenv("DEPLOY_SSH_USER", "root")
    ssh_password = os.getenv("DEPLOY_SSH_PASSWORD")

    if not ssh_password:
        print("ERROR: Variable de entorno DEPLOY_SSH_PASSWORD no configurada.")
        sys.exit(1)

    print(f"\n==========================================")
    print(f"Executing Remote: {cmd}")
    print(f"==========================================")
    ssh_cmd = f'ssh -o StrictHostKeyChecking=no {ssh_user}@{server_ip} "cd /var/www/capital_gestor && {cmd}"'
    child = pexpect.spawn(ssh_cmd, timeout=timeout)
    idx = child.expect(['[pP]assword:', pexpect.EOF, pexpect.TIMEOUT])
    if idx == 0:
        child.sendline(ssh_password)
        child.expect(pexpect.EOF)
        output = child.before.decode('utf-8', errors='ignore')
        print(output)
        return output
    else:
        output = child.before.decode('utf-8', errors='ignore')
        print(f"Error/Timeout: {output}")
        return output

if __name__ == '__main__':
    # Step 1: Pull latest changes
    run_remote_cmd("git pull origin main")

    # Step 2: Storage link, Backup & Migrations
    run_remote_cmd("php artisan storage:link")
    run_remote_cmd("mkdir -p /var/backups && mysqldump --no-tablespaces -u root gestor_capital_db > /var/backups/pre_migration_backup_$(date +%Y%m%d_%H%M%S).sql")
    run_remote_cmd("php artisan migrate --force")
    run_remote_cmd("php artisan storage:migrate-base64")

    # Step 3: Clear and cache configs
    run_remote_cmd("php artisan config:clear")
    run_remote_cmd("php artisan config:cache")
    run_remote_cmd("php artisan route:cache")
    run_remote_cmd("php artisan view:cache")
    print("\n✅ ¡Despliegue a producción completado con éxito!")
