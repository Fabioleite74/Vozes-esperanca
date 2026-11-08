# Guia: trabalhar no VS Code e publicar o site

Este guia vale para qualquer hospedagem com **PHP 8.1+** e **acesso FTP** (cPanel, InfinityFree, Hostinger, HostGator, Locaweb…).

---

## 1. Preparar o computador (uma vez só)

1. Instale o **VS Code**: https://code.visualstudio.com
2. Instale o **Git**: https://git-scm.com
3. Instale o **PHP 8.1 ou mais novo**:
   - **Windows:** baixe em https://windows.php.net/download (versão *Thread Safe*, zip), extraia em `C:\php`, renomeie `php.ini-development` para `php.ini`, abra-o e tire o `;` do começo das linhas `extension=pdo_sqlite`, `extension=sqlite3`, `extension=fileinfo` e `extension=mbstring`. Depois adicione `C:\php` ao PATH do Windows.
   - **Mac:** `brew install php`
   - **Linux:** `sudo apt install php-cli php-sqlite3 php-mbstring`
   - Confira no terminal: `php -v`
4. Baixe o projeto. No VS Code: **Ctrl+Shift+P → "Git: Clone"** → cole `https://github.com/fabioleite74/vozes-esperanca.git`.
5. Ao abrir a pasta, o VS Code sugere as **extensões recomendadas**. Clique em *Instalar todas*:
   - **PHP Intelephense**: ajuda a escrever PHP;
   - **SFTP**: envia arquivos para a hospedagem;
   - **EditorConfig**: mantém a formatação;
   - **SQLite Viewer**: abre o banco `data/coral.sqlite`.

## 2. Rodar o site no seu computador

- Pressione **Ctrl+Shift+B** (ou *Terminal → Run Build Task*) → **"Rodar site local"**.
- Abra http://localhost:8000 (site) e http://localhost:8000/admin (administração).
- No primeiro acesso ao admin, crie seu usuário local.
- Para ter uma música de teste: *Terminal → Run Task → "Criar música de exemplo"*.

O banco e os arquivos enviados ficam em `data/` e `uploads/` e **não vão para o Git**. O site local e o site no ar têm dados separados.

## 3. Onde mexer

| Quero mudar… | Arquivo |
| --- | --- |
| Cores, fontes, espaçamentos | `assets/style.css` (variáveis no topo) |
| Nome do coral, instituição, naipes | `inc/bootstrap.php` (`SITE_NOME`, `SITE_INSTITUICAO`, `VOZES`) |
| Cabeçalho, rodapé, ícones | `inc/layout.php` |
| Página inicial | `index.php` |
| Página da música | `musica.php` |
| Área administrativa | pasta `admin/` |
| Imagem do coral | `assets/img/arte-coral.webp` |

---

## 4. Publicar o site: escolha UM dos dois jeitos

### Opção A: automático pelo GitHub (recomendado)

Toda vez que você enviar alterações para a branch `main`, o GitHub publica o site sozinho por FTP.

**Configurar uma vez:**
1. Pegue os dados de FTP no painel da hospedagem: servidor, usuário e senha.
   - cPanel: *Contas FTP*. InfinityFree: *FTP Details*.
2. No GitHub, abra o repositório → **Settings → Secrets and variables → Actions**.
3. Na aba **Secrets**, crie:
   - `FTP_SERVER`: ex. `ftp.seudominio.com.br` ou `ftpupload.net`
   - `FTP_USERNAME`
   - `FTP_PASSWORD`
4. Na aba **Variables**, crie `FTP_SERVER_DIR` com a pasta do site no servidor, terminando em `/`:
   - cPanel: `/public_html/`
   - InfinityFree: `/htdocs/`

**No dia a dia (pelo VS Code):**
1. Edite os arquivos.
2. Na barra lateral, abra **Controle do Código-Fonte** (Ctrl+Shift+G).
3. Escreva uma mensagem (ex.: "Muda cor do botão") e clique em **Commit**.
4. Clique em **Sync / Push**.
5. Acompanhe em GitHub → **Actions → Publicar site**. Em 1 ou 2 minutos o site está atualizado.

Antes de publicar, o GitHub verifica se há erro de sintaxe no PHP. Se houver, nada é enviado.

### Opção B: direto do VS Code por FTP

1. Copie `.vscode/sftp.example.json` para `.vscode/sftp.json` e preencha servidor, usuário, senha e `remotePath`. Esse arquivo **não vai para o Git**, porque contém a senha.
2. Para enviar tudo: **Ctrl+Shift+P → "SFTP: Sync Local → Remote"**.
3. Para enviar só um arquivo: clique com o botão direito nele → **Upload File**.

> Não use as duas opções ao mesmo tempo, para não haver versões diferentes no servidor.

---

## 5. Primeira publicação

1. Publique pelo jeito escolhido acima.
2. Acesse `https://seusite/admin/` e crie o usuário administrador **do site no ar**.
3. Cadastre as músicas.

As pastas `data/` e `uploads/` são criadas automaticamente no servidor, já protegidas. Nenhum dos dois métodos de publicação envia ou apaga essas pastas, então os áudios e partituras cadastrados no site no ar ficam a salvo.

## 6. Backup

Pelo gerenciador de arquivos ou FTP, baixe de vez em quando as pastas `data/` e `uploads/` do servidor.

## 7. Problemas comuns

| Sintoma | Solução |
| --- | --- |
| "Erro 500" depois de publicar | Algumas hospedagens não aceitam `php_value` no `.htaccess`. Apague o bloco `<IfModule mod_php.c>` do `.htaccess` da raiz e ajuste os limites no painel (*Selecionar versão do PHP → Opções*). |
| Áudio grande não é aceito | Aumente `upload_max_filesize`/`post_max_size` no painel, ou exporte em MP3 de 128 kbps. Na InfinityFree o limite é fixo (cerca de 10 MB por arquivo). |
| "Falha ao gravar no servidor" | Dê permissão de escrita (775) às pastas `data/` e `uploads/`. |
| Deploy do GitHub falha no login | Confira os secrets `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD` (sem `ftp://` no servidor). |
