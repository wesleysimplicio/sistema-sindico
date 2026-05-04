# HostGator Deploy

Pipeline objetivo para subir o Sistema Sindico via GitHub Actions + FTP em hospedagem compartilhada.

## Pipeline

- Workflow: `.github/workflows/deploy-hostgator.yml`
- Gatilho: push na branch `main` (ou `workflow_dispatch` manual)
- Etapas: checkout, PHP 8.2, lint `php -l`, build, verify, upload FTP, purge Cloudflare opcional

## Secrets necessarios no repositorio

Settings -> Secrets and variables -> Actions -> New repository secret:

| Nome                    | Conteudo                                                              |
|-------------------------|-----------------------------------------------------------------------|
| `FTP_HOST`              | host FTP da HostGator (ex: `ftp.seudominio.com`)                      |
| `FTP_USERNAME`          | usuario FTP                                                            |
| `FTP_PASSWORD`          | senha FTP                                                              |
| `FTP_REMOTE_DIR`        | diretorio remoto, ex: `/sistema-sindico/` ou `/public_html/sindico/`  |
| `CLOUDFLARE_API_TOKEN`  | opcional, token com permissao de purge na zona                         |
| `CLOUDFLARE_ZONE_ID`    | opcional, id da zona                                                   |

Sem `CLOUDFLARE_*`, o passo de purge e ignorado.

## O que sobe por FTP

Apenas o runtime da aplicacao:

- `src/`
- `routes/`
- `templates/`
- `config/app.php`
- `public/index.php`
- `public/assets/`
- `public/.htaccess`
- `.htaccess` (raiz, redireciona para `public/` quando docroot nao aponta direto pra la)

Diretorios criados vazios no pacote (precisam existir no servidor):

- `storage/logs/`

## O que NAO sobe por FTP

- `.env` e `.env.example`
- `config/config.php` (operacional, criar no servidor fora do Git)
- `database/` (schema/seed sao aplicados via phpMyAdmin)
- `docs/`
- `README.md`, `README.pt-BR.md`, `CHANGELOG.md`, `CLAUDE.md`, `VERSION`
- `.github/`, `scripts/`
- `tests/`

Lista enforcada por `scripts/verify-hostgator-release.sh`. Build aborta se algum item proibido vazar.

## Layout no servidor

Duas opcoes:

1. **Docroot na raiz do projeto:** o `.htaccess` da raiz reescreve tudo para `public/`. Ok com hospedagem que serve `~/<dominio>/` como docroot.
2. **Docroot direto em `public/`:** preferido quando o painel da HostGator permite apontar o docroot do subdominio para `<dir>/public`. Nesse caso, o `.htaccess` da raiz e inofensivo.

## Setup inicial no servidor (uma vez)

1. Criar banco MySQL no cPanel.
2. Importar `database/schema.sql` e `database/seed.sql` via phpMyAdmin.
3. Criar `.env` no diretorio remoto (mesmo nivel de `src/`):

```
APP_NAME="Sistema Sindico"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seudominio.com/sindico
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=usuario_sindico
DB_USERNAME=usuario_sindico
DB_PASSWORD=senha_forte
APP_KEY=
JWT_SECRET=gere_com_openssl_rand_-hex_64
```

4. Garantir que `storage/logs/` existe e e gravavel pelo PHP (`chmod 775`).

## Deploy automatico

Apos os secrets configurados, qualquer push em `main` dispara o workflow. Acompanhe em:

```
https://github.com/<owner>/sistema-sindico/actions
```

`workflow_dispatch` permite executar manualmente pela aba Actions.

## Smoke pos-deploy

```
curl -s https://seudominio.com/sindico/api/health
```

Deve retornar `{"success":true,"data":{"status":"ok",...},"meta":{"version":"x.y.z"}}`.

Validar tambem:

- `GET /login` retorna HTML de login
- `GET /assets/app.css` retorna CSS
- Login com `admin@sistemasindico.local` / `senha123` (apenas se seed foi importado)

## Rollback

`SamKirkland/FTP-Deploy-Action` mantem state em `.ftp-deploy-sync-state.json`. Para forcar reupload completo:

1. Renomear/remover `.ftp-deploy-sync-state.json` no servidor.
2. Acionar `workflow_dispatch` novamente.

Para reverter codigo:

```bash
git revert <sha>
git push origin main
```

O workflow rebuilda e sobe a versao revertida.
