import pexpect
import sys

def run_remote_cmd(cmd, timeout=60):
    print(f"\n==========================================")
    print(f"Executing Remote: {cmd}")
    print(f"==========================================")
    ssh_cmd = f'ssh -o StrictHostKeyChecking=no root@206.81.14.81 "cd /var/www/capital_gestor && {cmd}"'
    child = pexpect.spawn(ssh_cmd, timeout=timeout)
    idx = child.expect(['[pP]assword:', pexpect.EOF, pexpect.TIMEOUT])
    if idx == 0:
        child.sendline('C@rlosAr4iza')
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

    # Step 2: Storage link & Migrations
    run_remote_cmd("php artisan storage:link")
    run_remote_cmd("php artisan migrate --force")
    run_remote_cmd("php artisan storage:migrate-base64")

    # Step 3: Clear and cache configs
    run_remote_cmd("php artisan config:clear")
    run_remote_cmd("php artisan config:cache")
    run_remote_cmd("php artisan route:cache")
    run_remote_cmd("php artisan view:cache")

    # Step 4: Run Pest tests on production server
    run_remote_cmd("vendor/bin/pest")
