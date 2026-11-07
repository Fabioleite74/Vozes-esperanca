# Coral Vozes da Esperança — site de áudios e partituras

Site para disponibilizar aos coralistas os **áudios por naipe** (soprano, contralto, tenor e baixo)
e as **partituras em PDF** de cada música do repertório, com uma **área administrativa** para
cadastrar e atualizar os arquivos.

## Funcionalidades

**Site público**
- Lista do repertório com busca por título ou autor.
- Página de cada música com:
  - player para ouvir o áudio de cada naipe direto no navegador (um toca por vez);
  - botão para baixar cada áudio (com nome amigável, ex.: `ave-maria-soprano.mp3`);
  - partitura exibida na própria página, com opções de abrir em nova aba ou baixar.
- Layout responsivo (funciona bem no celular) e com modo escuro automático.

**Área administrativa** (`/admin`)
- Primeiro acesso: o site pede para criar o usuário administrador.
- Cadastrar, editar e excluir músicas (título, autor/arranjo, observações).
- Enviar, substituir ou remover o áudio de cada naipe e a partitura em PDF.
- Tabela com o status de cada música (quais naipes/partitura já foram enviados).
- Troca de senha.

## Requisitos

- PHP 8.1 ou superior com as extensões `pdo_sqlite` e `fileinfo` (padrão na maioria das hospedagens).
- Servidor Apache (as proteções vêm nos arquivos `.htaccess`) ou Nginx (veja abaixo).
- Não precisa de MySQL: os dados ficam num arquivo SQLite em `data/`.

## Instalação na hospedagem

1. Envie todos os arquivos do projeto para a pasta pública do site (ex.: `public_html/`).
2. Garanta que as pastas `data/` e `uploads/` tenham permissão de escrita para o PHP (ex.: `775`).
3. Acesse `https://seusite/admin/` e crie o usuário administrador.
4. Pronto: cadastre as músicas em **+ Nova música**.

### Tamanho máximo dos arquivos

Os arquivos `.htaccess` e `.user.ini` já pedem ao PHP limite de **64 MB por arquivo** e
**300 MB por envio**. Algumas hospedagens ignoram esses arquivos — nesse caso ajuste
`upload_max_filesize` e `post_max_size` no painel da hospedagem (cPanel → *Selecionar versão do PHP*
→ *Opções*). O limite em vigor aparece na tela de edição da música.

### Nginx

Se o servidor for Nginx, adicione ao bloco do site:

```nginx
location ~ ^/(data|inc)/ { deny all; }
location ~ ^/uploads/.*\.(php\d?|phtml|phar|html?|svg|js)$ { deny all; }
client_max_body_size 300M;
```

## Rodar localmente

```bash
php -S localhost:8000 router.php
```

Abra http://localhost:8000 (site) e http://localhost:8000/admin (administração).

## Estrutura

```
index.php          Repertório (lista + busca)
musica.php         Página da música: players, downloads e partitura
admin/             Área administrativa (login, lista, edição, exclusão, senha)
inc/bootstrap.php  Configuração, banco, autenticação, upload
inc/layout.php     Cabeçalho e rodapé
assets/            CSS e JS
uploads/           Arquivos enviados (não versionados)
data/              Banco SQLite (não versionado)
```

### Personalização

- **Nome do site:** constante `SITE_NOME` em `inc/bootstrap.php`.
- **Naipes:** constante `VOZES` em `inc/bootstrap.php` (ex.: para incluir "Todas as vozes",
  adicione `'tutti' => 'Todas as vozes'`).
- **Cores:** variáveis no topo de `assets/style.css`.

## Backup

Basta copiar as pastas `data/` (banco) e `uploads/` (arquivos).

## Segurança

- Senhas guardadas com `password_hash` (bcrypt); proteção CSRF em todos os formulários.
- Uploads validados pelo conteúdo real do arquivo (não apenas pela extensão) e gravados com nomes aleatórios.
- A pasta `uploads/` não executa scripts; `data/` e `inc/` são inacessíveis pela web.
