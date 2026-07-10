# Guia de Inicialização com Docker & WSL2 (Laravel Sail)

Este documento descreve detalhadamente o passo a passo para configurar e rodar este projeto em um ambiente de desenvolvimento local usando **Docker Desktop** (com integração WSL2) ou **Docker Engine** diretamente em uma distribuição **WSL2** (ex: Ubuntu).

---

## 1. Pré-requisitos

Antes de iniciar, certifique-se de possuir instalado em sua máquina:
1. **WSL2** (Windows Subsystem for Linux), preferencialmente com a distribuição **Ubuntu**.
2. **Docker Desktop** para Windows com a opção de integração com WSL2 habilitada (nas configurações do Docker Desktop: *Settings -> Resources -> WSL Integration*).
   * *Alternativa:* Docker Engine instalado diretamente dentro da sua distribuição WSL2.
3. **Git** configurado dentro da sua distribuição WSL2.

> [!TIP]
> Caso ainda não possua o WSL2 + Docker configurados em sua máquina Windows, recomendamos seguir o excelente guia de instalação rápida: [wsl2-docker-quickstart (por Wesley Willians)](https://github.com/codeedu/wsl2-docker-quickstart).

---

## 2. Performance do Sistema de Arquivos (WSL2)
> [!IMPORTANT]
> **NÃO** clone o repositório na partição do Windows (ex: `/mnt/c/Users/...`). O acesso a arquivos entre sistemas de arquivos cruzados é extremamente lento no WSL2.
>
> Sempre clone o projeto dentro do sistema de arquivos nativo do Linux do WSL2.
> * Exemplo de diretório recomendado: `/home/seu-usuario/projects/sistema-pnet` ou apenas `~/projects/sistema-pnet`.

---

## 3. Instalacao e Inicialização Passo a Passo

Siga os passos abaixo no terminal do seu WSL2 para subir a aplicação:

### Passo 3.1. Clonar o Repositório e Acessar o Diretório
```bash
git clone <url-do-repositorio> sistema-pnet
cd sistema-pnet
```

### Passo 3.2. Configurar Variáveis de Ambiente
Copie o arquivo `.env.example` para `.env`:
```bash
cp .env.example .env
```

Abra o arquivo `.env` gerado e ajuste as configurações para que a aplicação se conecte corretamente aos containers do Docker Sail em vez do SQLite e `127.0.0.1`:

```env
# Banco de Dados (Alterar de SQLite para MySQL e usar o host do container)
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

# Redis (Mudar o host local para o host do container)
REDIS_HOST=redis

# Mailpit (Configurar para capturar os e-mails enviados no dashboard local)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025

# MinIO (Armazenamento S3 Local - Já vêm pré-configuradas no .env.example)
AWS_ACCESS_KEY_ID=sail
AWS_SECRET_ACCESS_KEY=password
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=sistema-pnet
AWS_ENDPOINT=http://minio:9000
AWS_URL=http://localhost:9000/sistema-pnet
AWS_USE_PATH_STYLE_ENDPOINT=true
BUCKET_DISK=minio

# Conflito de Portas (Opcional - Caso já possua MySQL/Web local no host Windows/WSL)
# Defina portas alternativas para expor os containers do Sail
APP_PORT=8005
FORWARD_DB_PORT=3307
```

### Passo 3.3. Instalar Dependências do Composer (PHP)
Como a aplicação utiliza o PHP 8.5 e você pode não ter o PHP instalado localmente no seu WSL2, você pode usar um container Docker temporário para instalar as dependências do Laravel:
```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php85-composer:latest \
    composer install --ignore-platform-reqs
```

### Passo 3.4. Inicializar os Containers com Laravel Sail
Suba a infraestrutura da aplicação em segundo plano (detached mode):
```bash
./vendor/bin/sail up -d
```
> [!TIP]
> Caso queira forçar a compilação/reconstrução da imagem do ambiente PHP (especialmente útil na primeira execução ou se houver alterações no Dockerfile da runtime do Sail), utilize a flag `--build`:
> ```bash
> ./vendor/bin/sail up -d --build
> ```

> [!NOTE]
> Este comando subirá 5 containers:
> - `laravel.test` (Servidor PHP 8.5/Web)
> - `mysql` (Banco de dados)
> - `redis` (Cache/Fila)
> - `mailpit` (Captura de e-mails locais)
> - `minio` (Armazenamento S3 local)

### Passo 3.5. Configurar o Alias do Sail no Terminal (Opcional, mas Altamente Recomendado)
Em vez de digitar `./vendor/bin/sail` em todos os comandos, você pode configurar um atalho (`alias`).

Para tornar isso definitivo, adicione a linha no final do arquivo `~/.bashrc` (ou `~/.zshrc` se usar Zsh):
```bash
echo "alias sail='sh \$([ -f sail ] && echo sail || echo vendor/bin/sail)'" >> ~/.bashrc
source ~/.bashrc
```
*A partir de agora, você poderá usar apenas `sail` au invés de `./vendor/bin/sail`.*

### Passo 3.6. Gerar a Chave da Aplicação
```bash
sail artisan key:generate
```

### Passo 3.7. Executar as Migrações e Seeders
Crie as tabelas no banco de dados MySQL e insira os dados iniciais:
```bash
sail artisan migrate --seed
```

> [!IMPORTANT]
> **Problema com Permissões no MySQL (Acesso Negado):**
> Se ao rodar as migrações você se deparar com erros de permissão de acesso para o usuário `sail` (ex: `Access denied for user 'sail'@'%'`), execute o seguinte comando para conceder as permissões necessárias ao usuário no banco de dados do container:
> ```bash
> docker compose exec mysql mysql -u root -ppassword -e "GRANT ALL PRIVILEGES ON *.* TO 'sail'@'%'; FLUSH PRIVILEGES;"
> ```
> *(Alternativamente, caso já tenha configurado o alias do Sail, você pode rodar: `sail mysql -u root -ppassword -e "GRANT ALL PRIVILEGES ON *.* TO 'sail'@'%'; FLUSH PRIVILEGES;"`)*

### Passo 3.8. Garantir a Existência do Bucket S3 no MinIO
Esta aplicação utiliza o MinIO como serviço local compatível com S3. Execute o comando customizado para criar o bucket configurado:
```bash
sail artisan bucket:ensure
```

### Passo 3.9. Executar o Servidor de Desenvolvimento e Frontend (Vite)
No Laravel 13, você pode rodar o servidor de desenvolvimento integrado e o compilador de assets do Vite simultaneamente usando o comando `sail artisan dev`.

1. Primeiro, instale as dependências de Node.js do projeto (necessário apenas na primeira inicialização):
   ```bash
   sail npm install
   ```

2. Em seguida, inicie o ambiente de desenvolvimento unificado:
   ```bash
   sail artisan dev
   ```
   *Este comando gerencia simultaneamente a execução do servidor e o hot reload do Vite no mesmo terminal de forma consolidada.*

---

#### Alternativa: Executar o Vite diretamente no host WSL2 usando NVM (Opcional)
Se você preferir rodar o Node.js/Vite diretamente no terminal do WSL2 (fora do container) por preferência de performance de I/O ou integração com IDE, siga os passos abaixo:

> [!WARNING]
> **Atenção sobre conflitos com o Node do Windows:**
> Por padrão, o WSL2 herda a variável `PATH` do Windows. Se você tiver o Node.js instalado no Windows, o WSL2 tentará executar o `node` do Windows quando você digitar comandos no terminal Linux caso não haja uma versão nativa do Linux instalada.
>
> Isso causa erros graves de compilação de pacotes e caminhos inválidos. Instalar o Node.js nativo dentro do Linux usando o **NVM** corrige este problema, garantindo que o WSL2 priorize o executável nativo do Linux.

1. Instale o NVM no WSL2:
   ```bash
   curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.1/install.sh | bash
   ```
2. Recarregue as configurações do terminal:
   ```bash
   source ~/.bashrc
   ```
3. Instale e use a versão recomendada do Node.js (ex: versão 22 LTS):
   ```bash
   nvm install 22
   nvm use 22
   ```
4. Instale as dependências do Node.js:
   ```bash
   npm install
   ```
5. Execute o Vite no host:
   ```bash
   npm run dev
   ```

---

## 4. Portas e URLs de Acesso

Depois de subir a aplicação, você pode acessar as seguintes ferramentas no seu navegador:

| Serviço | URL Local | Descrição |
|---|---|---|
| **Aplicação (Web)** | [http://localhost](http://localhost) | Rota principal da aplicação rodando via Laravel |
| **Mailpit Dashboard** | [http://localhost:8025](http://localhost:8025) | Console web para visualizar e-mails disparados pela aplicação |
| **MinIO Console** | [http://localhost:8900](http://localhost:8900) | Dashboard de armazenamento local (Usuário: `sail` / Senha: `password`) |

### 4.1 Customização de Portas (Resolução de Conflitos)

Se você já tiver outros serviços rodando localmente na sua máquina Windows ou no WSL2 (como um servidor Apache/Nginx na porta `80` ou MySQL na porta `3306`), o Docker Sail falhará ao iniciar devido a conflitos.

Você pode resolver isso facilmente definindo portas alternativas diretamente no seu arquivo `.env` (evitando alterar o arquivo `compose.yaml` do repositório).

Adicione ou edite as seguintes variáveis no seu arquivo `.env` de acordo com a sua necessidade:

```env
# Porta da aplicação Web (ex: http://localhost:8080)
APP_PORT=8080

# Porta do Vite para Hot Reload do frontend
VITE_PORT=5174

# Porta de acesso externo do Banco de Dados MySQL
FORWARD_DB_PORT=3307

# Porta de acesso do Redis
FORWARD_REDIS_PORT=6380

# Portas do Mailpit
FORWARD_MAILPIT_PORT=1026
FORWARD_MAILPIT_DASHBOARD_PORT=8026

# Portas do MinIO (S3)
FORWARD_MINIO_PORT=9001
FORWARD_MINIO_CONSOLE_PORT=8901
```

*Após salvar o `.env`, reinicie os containers com `sail down` e depois `sail up -d` para aplicar as novas portas.*

---

## 5. Comandos Úteis do Sail

Aqui estão alguns comandos frequentes para facilitar o desenvolvimento:

* **Parar os containers:**
  ```bash
  sail stop
  ```
* **Parar os containers e remover os volumes (limpa o banco de dados):**
  ```bash
  sail down -v
  ```
* **Executar testes automatizados (Pest):**
  ```bash
  sail artisan test
  ```
* **Entrar no terminal interativo (Tinker) do Laravel:**
  ```bash
  sail artisan tinker
  ```
* **Executar um comando bash dentro do container do app:**
  ```bash
  sail bash
  ```
