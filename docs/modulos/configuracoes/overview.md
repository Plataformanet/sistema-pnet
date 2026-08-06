# Épico: Configurações da Empresa e Perfil do Usuário

---

## 1. Visão Geral do Recurso
*   **Nome do Épico:** Configurações da Empresa e Perfil do Usuário
*   **Status:** Aprovado / Homologado (Em Produção)
*   **Autor/Responsável:** Equipe PNET
*   **Módulo Associado:** Configurações e Segurança

### 1.1. Contexto de Negócio (Por que estamos fazendo isso?)
Cada empresa inquilina (tenant) no PNET necessita personalizar seus dados cadastrais jurídicos, endereço comercial e logotipo corporativo para exibição no painel, relatórios e telas do sistema (incluindo a tela de login). Além disso, os usuários autenticados necessitam de um painel de perfil para personalizar suas informações pessoais (nome), atualizar a foto de avatar e alterar a senha de acesso com segurança.

### 1.2. Atores Envolvidos (Quem usa?)
*   **Administrador do Tenant:** Pode visualizar e editar as configurações cadastrais e o logotipo da empresa (exige permissão `settings.company.edit`).
*   **Usuário Autenticado do Tenant:** Pode visualizar a página da empresa (se tiver permissão `settings.company.view`) e pode editar seu próprio perfil e senha (acesso nativo por login).
*   **Visitante / Usuário na Tela de Login:** Visualiza o logotipo corporativo e nome da empresa no banner da tela de login do tenant antes de autenticar.

---

## 2. Regras de Negócio e Requisitos Funcionais

1.  **Chaves Dinâmicas de Configuração:** As configurações da empresa são armazenadas dinamicamente na tabela `tenant_settings` sob a chave `company.*` (ex: `company.name`, `company.cnpj`, `company.logo_path`), permitindo adição de novos campos sem alterações de schema.
2.  **Upload e Stream de Logotipo (MinIO):** O logotipo pode ser enviado nos formatos vetoriais (SVG) e bitmaps (PNG, JPG, WebP) de até 2MB. O arquivo é armazenado no bucket MinIO sob o caminho `tenant<id>/company/` e transmitido por uma rota de stream com suporte a cache buster (`?v=timestamp`).
3.  **Preenchimento Automático de Endereço via CEP:** Ao digitar os 8 dígitos do CEP no formulário de endereço da empresa, o sistema consulta a API pública do ViaCEP e preenche automaticamente os campos de Logradouro (`street`), Bairro (`neighborhood`), Cidade (`city`) e UF (`state`).
4.  **E-mail Readonly no Perfil:** No formulário de Perfil do Usuário, o campo de e-mail é exibido em modo somente leitura (`readonly`) para evitar alterações indevidas de credencial.
5.  **Upload de Foto de Avatar (MinIO):** A foto do perfil do usuário é armazenada no MinIO sob o caminho `tenant<id>/avatars/`. Ao atualizar ou remover a foto, a antiga é deletada do storage.
6.  **Alteração de Senha com Visibilidade ("Olhinho"):** A alteração de senha exige a validação da senha atual (`current_password`) e confirmação da nova senha, com botões de alternância para exibir/ocultar o texto digitado.
7.  **Isolamento e Invalidação de Cache:** Alterações em configurações limpam com segurança o cache com tratamento de exceções de tagging caso o driver de cache local (ex: `file`, `database`) não suporte tags nativas.

---

## 3. Especificação Técnica e Modelagem

### 3.1. Dicionário de Dados do Banco de Dados (Tenant)

*   **Tabela:** `tenant_settings`
    *   `id`: BigInt (PK, Auto-increment)
    *   `key`: String (Unique index no tenant, ex: `company.name`, `company.logo_path`)
    *   `value`: Text (Valor armazenado em string)
    *   `module`: String (Módulo associado, ex: `company`)
    *   `type`: String (Tipo de dado, default: `string`)
    *   `user_id`: BigInt (FK para `users`, nullable, onDelete Cascade)
    *   `created_at`, `updated_at`: Timestamp

*   **Tabela:** `users` (Campos ajustados)
    *   `name`: String
    *   `email`: String (Unique)
    *   `photo`: String (Caminho relativo do avatar no MinIO, nullable)
    *   `password`: String (Hash da senha)

### 3.2. Estrutura de Código
*   **Controllers:**
    *   `App\Http\Controllers\TenantCompanySettingController`
    *   `App\Http\Controllers\TenantProfileController`
*   **Services:**
    *   `App\Services\CompanySettingService`
    *   `App\Services\UserProfileService`
*   **Form Requests:**
    *   `App\Http\Requests\UpdateCompanySettingRequest` (Com `prepareForValidation` para booleanos FormData)
    *   `App\Http\Requests\UpdateUserProfileRequest`
    *   `App\Http\Requests\UpdateUserPasswordRequest`
*   **Componentes Vue / Frontend:**
    *   `resources/js/pages/tenant/settings/company/Edit.vue`
    *   `resources/js/pages/tenant/profile/Edit.vue`
    *   `resources/js/layouts/AuthLayout.vue` (Banner da tela de login)
    *   `resources/js/layouts/tenant-layout/TenantSidebar.vue` (Logo da sidebar)
    *   `resources/js/layouts/tenant-layout/TenantLayout.vue` (Logo/Nome no header)
*   **Rotas:**
    *   `GET /settings/company` -> `tenant.settings.company.edit` (`permission:settings.company.view`)
    *   `POST /settings/company` -> `tenant.settings.company.update` (`permission:settings.company.edit`)
    *   `GET /settings/company/logo` -> `tenant.settings.company.logo` (*Pública no Tenant*)
    *   `GET /profile` -> `tenant.profile.edit` (*Autenticado*)
    *   `POST /profile` -> `tenant.profile.update` (*Autenticado*)
    *   `GET /profile/avatar` -> `tenant.profile.avatar` (*Autenticado*)
    *   `PUT /profile/password` -> `tenant.profile.password.update` (*Autenticado*)

---

## 4. Referência de API (Exemplos de Request/Response)

### 4.1. Atualizar Configurações da Empresa `POST /settings/company`
*   **Headers:** `Content-Type: multipart/form-data`
*   **Request Payload (FormData):**
```
name: Empresa Exemplo S.A.
trade_name: Empresa Exemplo
cnpj: 12.345.678/0001-90
email: contato@empresa.com.br
phone: (11) 99999-9999
zip_code: 01001-000
street: Praça da Sé
number: 100
neighborhood: Sé
city: São Paulo
state: SP
logo: (binary file - PNG/JPG/SVG)
remove_logo: false
```
*   **Response (302 Redirect Back):**
    Redireciona com mensagem flash: `success: "Configurações da empresa salvas com sucesso!"`

---

## 5. Critérios de Aceite e Cenários de Teste (BDD)

### Cenário 1: Atualização de empresa com upload de logotipo SVG/PNG
*   **Dado que** o usuário administrador possui a permissão `settings.company.edit`
*   **E** acessa a tela de configurações da empresa (`/settings/company`)
*   **Quando** digita o CEP "01001-000" e o sistema preenche o endereço automaticamente
*   **E** seleciona um arquivo de logotipo no formato SVG ou PNG e clica em "Salvar Alterações"
*   **Então** o arquivo deve ser gravado no MinIO sob o diretório do tenant
*   **E** o registro `company.logo_path` deve ser salvo na tabela `tenant_settings`
*   **E** o logotipo deve ser exibido imediatamente na página, no header, na sidebar e na tela de login.

### Cenário 2: Atualização de perfil do usuário e foto de avatar
*   **Dado que** um usuário está autenticado no tenant
*   **Quando** ele acessa a página `/profile`, altera seu nome e envia uma foto de avatar
*   **Então** a imagem é salva no MinIO no diretório de avatares do tenant
*   **E** o campo `photo` da tabela `users` é atualizado com o caminho da imagem
*   **E** a nova foto é refletida no componente de Avatar no topo da aplicação.

### Cenário 3: Alteração de senha com senha atual inválida
*   **Dado que** o usuário está na seção de alteração de senha em `/profile`
*   **Quando** digita uma senha atual incorreta e tenta salvar
*   **Então** o sistema não altera a senha no banco de dados
*   **E** exibe uma mensagem de erro de validação sob o campo "Senha Atual".

---

## 6. Integrações e Dependências Externas

*   **Storage (MinIO / S3):** Armazenamento isolado por inquilino em buckets do MinIO gerenciado via `FilesystemTenancyBootstrapper`.
*   **API Externa ViaCEP:** Integrado via composable `useCepLookup` para consulta síncrona de CEP (`https://viacep.com.br/ws/{cep}/json/`).
*   **Spatie Laravel Permission:** Controle de acesso baseado nas permissões `settings.company.view` e `settings.company.edit`.
