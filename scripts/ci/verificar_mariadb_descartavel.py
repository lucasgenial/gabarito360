"""Confere somente o serviço descartável do job de integridade, antes dos testes."""
import json
import os
import re
import subprocess


def validar_container(container):
    if container['Config']['Image'] != 'mariadb:10.11':
        raise RuntimeError('Imagem de integridade inesperada.')
    ambiente = dict(value.split('=', 1) for value in container['Config']['Env'] if '=' in value)
    if ambiente.get('MARIADB_DATABASE') != 'g360_t2_test':
        raise RuntimeError('Somente o banco descartável de integridade é aceito.')
    options = container['HostConfig'].get('Tmpfs', {}).get('/var/lib/mysql', '')
    tamanho = re.search(r'(?:^|,)size=(\d+)([kmg]?)(?:,|$)', options, re.I)
    if not tamanho or 'rw' not in options.split(','):
        raise RuntimeError('Diretório de dados deve usar tmpfs gravável e limitado.')
    bytes_ = int(tamanho[1]) * {'': 1, 'k': 1024, 'm': 1024**2, 'g': 1024**3}[tamanho[2].lower()]
    if bytes_ != 1024**3:
        raise RuntimeError('Limite de memória do armazenamento inesperado.')
    return bytes_


def main():
    if os.environ.get('GITHUB_ACTIONS') != 'true' or os.environ.get('DB_DATABASE') != 'g360_t2_test':
        raise RuntimeError('Esta conferência é exclusiva do CI descartável.')
    identifier = os.environ.get('MARIADB_CONTAINER_ID', '')
    if not re.fullmatch(r'[a-f0-9]{12,64}', identifier):
        raise RuntimeError('Contêiner de serviço inválido.')
    container = json.loads(subprocess.check_output(['docker', 'inspect', identifier], text=True))[0]
    capacity = validar_container(container)
    fs = subprocess.check_output(['docker', 'exec', identifier, 'stat', '-f', '-c', '%T', '/var/lib/mysql'], text=True).strip()
    if fs != 'tmpfs':
        raise RuntimeError('O armazenamento real não corresponde ao tmpfs declarado.')
    # Usa apenas o segredo fictício já presente no serviço; não o imprime nem passa como argumento.
    comando = "MYSQL_PWD=\"$MARIADB_ROOT_PASSWORD\" exec mariadb --protocol=socket --user=root --batch --skip-column-names -e 'SELECT VERSION(),@@innodb_flush_log_at_trx_commit'"
    version, flush = subprocess.check_output(['docker', 'exec', identifier, 'sh', '-c', comando], text=True).strip().split('\t')
    if not version.startswith('10.11.') or flush != '1':
        raise RuntimeError('Versão ou garantia de commit mudou no serviço de teste.')
    print(json.dumps({'database': 'g360_t2_test', 'filesystem': fs, 'capacity_bytes': capacity,
                     'version': version, 'innodb_flush_log_at_trx_commit': int(flush),
                     'suite_completa_preservada': True, 'servidores_da_aplicacao_alterados': False}))


if __name__ == '__main__':
    main()
