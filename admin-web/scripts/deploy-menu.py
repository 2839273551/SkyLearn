import argparse
import datetime
import hashlib
import io
import json
import os
import posixpath
import re
import sys
from pathlib import Path

if os.environ.get('PARAMIKO_PATH'):
    sys.path.insert(0, os.environ['PARAMIKO_PATH'])
import paramiko

parser = argparse.ArgumentParser()
parser.add_argument('--server-info', required=True)
parser.add_argument('--deploy', action='store_true')
args = parser.parse_args()
raw = Path(args.server_info).read_text(encoding='utf-8-sig')
lines = [line.strip() for line in raw.splitlines() if line.strip()]
host, port, username = lines[0], int(lines[1]), lines[2]
key_text = re.search(r'-----BEGIN [^-]*PRIVATE KEY-----[\s\S]*?-----END [^-]*PRIVATE KEY-----', raw).group(0)
key = None
for key_class in (paramiko.Ed25519Key, paramiko.RSAKey, paramiko.ECDSAKey):
    try:
        key = key_class.from_private_key(io.StringIO(key_text))
        break
    except (paramiko.SSHException, ValueError):
        continue
if key is None:
    raise RuntimeError('Unsupported private key format')

site = '/www/wwwroot/sk.yunxnet.cn'
client = paramiko.SSHClient()
client.load_system_host_keys()
known_hosts = Path.home() / '.ssh' / 'known_hosts'
if known_hosts.exists():
    client.load_host_keys(str(known_hosts))
client.connect(hostname=host, port=port, username=username, pkey=key, allow_agent=False, look_for_keys=False, timeout=20)

def command(text):
    _, stdout, stderr = client.exec_command(text, timeout=30)
    code = stdout.channel.recv_exit_status()
    output = stdout.read().decode('utf-8', 'replace')
    error = stderr.read().decode('utf-8', 'replace')
    if code:
        raise RuntimeError(f'Remote command failed ({code}): {error}')
    return output.strip()

try:
    with client.open_sftp() as sftp:
        nginx_path = '/www/server/panel/vhost/nginx/sk.yunxnet.cn.conf'
        with sftp.open(nginx_path) as file:
            nginx = file.read().decode('utf-8')
        print(json.dumps({'nginxRoutes': [line.strip() for line in nginx.splitlines() if re.search(r'\b(root|alias|try_files)\b', line)]}, ensure_ascii=False))
        print(json.dumps({'indexFiles': sftp.listdir(site + '/index')[:20]}, ensure_ascii=False))
        if not args.deploy:
            sys.exit(0)
        dist = Path(__file__).resolve().parents[1] / 'dist'
        if not (dist / 'index.html').exists():
            raise RuntimeError('Build output is missing')
        backup = '/www/backups/sk.yunxnet.cn/' + datetime.datetime.now().strftime('%Y%m%dT%H%M%S') + '-legacy-menu'
        sftp.mkdir(backup)
        remote_api = site + '/admin-api/v1/index.php'
        with sftp.open(remote_api) as file:
            api = file.read()
        with sftp.open(backup + '/index.php', 'wb') as file:
            file.write(api)
        with sftp.open(site + '/index/index.html') as file:
            old_entry = file.read()
        with sftp.open(backup + '/index.html', 'wb') as file:
            file.write(old_entry)
        marker = b"        'csrfToken' => api_csrf_token(),"
        addition = b"        'canMigrateSuperior' => isset($conf['sjqykg']) && intval($conf['sjqykg']) === 1,\n"
        if b"'canMigrateSuperior'" not in api:
            if api.count(marker) != 1:
                raise RuntimeError('The production session contract changed')
            updated_api = api.replace(marker, addition + marker)
            temp_api = site + '/admin-api/v1/.menu-session.php'
            with sftp.open(temp_api, 'wb') as file:
                file.write(updated_api)
            print(command('/www/server/php/74/bin/php -l ' + temp_api))
            sftp.posix_rename(temp_api, remote_api)
        files = [file for file in dist.rglob('*') if file.is_file()]
        files.sort(key=lambda file: file.name == 'index.html')
        for file in files:
            relative = file.relative_to(dist).as_posix()
            target = site + '/index/' + relative
            parent = posixpath.dirname(target)
            try:
                sftp.stat(parent)
            except FileNotFoundError:
                sftp.mkdir(parent)
            temporary = target + '.menu-upload'
            sftp.put(str(file), temporary)
            sftp.chmod(temporary, 0o644)
            sftp.posix_rename(temporary, target)
        for file in files:
            with sftp.open(site + '/index/' + file.relative_to(dist).as_posix()) as remote:
                if hashlib.sha256(file.read_bytes()).digest() != hashlib.sha256(remote.read()).digest():
                    raise RuntimeError('Uploaded asset checksum mismatch')
        print(json.dumps({'deployed': True, 'backup': backup, 'files': len(files), 'checksums': 'matched'}, ensure_ascii=False))
finally:
    client.close()
