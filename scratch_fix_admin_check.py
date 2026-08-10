import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect("206.81.14.81", username="root", password="C@rlosAr4iza", timeout=30)
stdin, stdout, stderr = ssh.exec_command("cat /var/www/capital_gestor/app/Http/Controllers/PedidoController.php | grep -n 'if (!Auth::user()->hasRole(\\'admin\\'))'")
print(stdout.read().decode())
ssh.close()
