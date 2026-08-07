# Guia de Arquitetura e Padrões de Tema Escuro (Dark Mode)

Este documento estabelece a arquitetura, padrões de desenvolvimento e decisões de engenharia para o suporte ao **Modo Escuro (Dark Mode)** no **Sistema PNET**.

---

## 1. Visão Geral e Arquitetura de Cores

O Modo Escuro no PNET é implementado através do **Tailwind CSS v4** em conjunto com as variáveis de tema **Shadcn / OKLCH** definidas no arquivo `resources/css/app.css`.

*   **Variáveis Semânticas:** A aplicação utiliza tokens semânticos baseados no estado do documento (`.dark` na tag `<html>`).
*   **Persistência:** A preferência de tema do usuário é salva no `localStorage` do navegador e lida tanto de forma síncrona no HTML pelo servidor quanto reativamente pelo Vue.
*   **Gerenciador de Estado:** Pacote `@vueuse/core` com os composables `useDark()` e `useToggle()`.

---

## 2. Inicialização e Prevenção de FOUC (Flash of Unstyled Content)

Para evitar que a tela "pisque" em branco durante o carregamento inicial (refresh/F5 ou navegação direta entre telas de login e sistema), a resolução do tema ocorre em duas camadas:

### 2.1. Camada 1: Servidor / HTML Inicial (`resources/views/app.blade.php`)
Antes do carregamento ou hidratação dos pacotes JavaScript, um script inline síncrono é executado no `<head>` do arquivo Blade principal:

```html
<script>
    (function () {
        try {
            const storedTheme = localStorage.getItem('vueuse-color-scheme') || localStorage.getItem('color-scheme') || localStorage.getItem('theme');
            const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (storedTheme === 'dark' || (storedTheme === 'auto' && systemDark) || (!storedTheme && systemDark)) {
                document.documentElement.classList.add('dark');
            } else if (storedTheme === 'light') {
                document.documentElement.classList.remove('dark');
            }
        } catch (e) {}
    })();
</script>
```

### 2.2. Camada 2: Aplicação SPA / Vue 3 (`resources/js/app.ts`)
No ponto de entrada da aplicação Vue/Inertia, o `useDark()` é instanciado globalmente. Isso garante que a reatividade da biblioteca `@vueuse/core` fique ativa durante toda a navegação SPA:

```typescript
import { useDark } from "@vueuse/core";

// Inicializa a sincronização global do modo escuro com localStorage no app SPA Inertia
useDark();
```

---

## 3. Padrões de Estilização em Componentes Vue

### 3.1. Uso Obrigatório de Tokens Semânticos
Fica estritamente **proibido** utilizar classes fixas de cores claras para fundos, bordas ou textos em qualquer página ou componente.

| ❌ Proibido (Estático) | ✅ Obrigatório (Semântico) | Função no Tema |
| :--- | :--- | :--- |
| `bg-white` | `bg-card` ou `bg-background` | Fundo de páginas, tabelas, modais e cards. |
| `bg-slate-50`, `bg-slate-100` | `bg-muted` ou `bg-muted/50` | Fundo de cabeçalhos de tabela, barras e hover. |
| `border-slate-100`, `border-gray-200` | `border-border` | Bordas e divisores. |
| `text-slate-800`, `text-slate-900` | `text-foreground` | Texto principal com contraste dinâmico. |
| `text-slate-500`, `text-slate-400` | `text-muted-foreground` | Rótulos, descrições secundárias e datas. |

### 3.2. Botão de Alternância de Tema (`TenantLayout.vue`)
O cabeçalho principal exibe um botão para alternar entre os temas Sol (☀️) e Lua (🌙):

```typescript
import { useDark, useToggle } from "@vueuse/core";

const isDark = useDark();
const toggleDark = useToggle(isDark);
```

```html
<Button
    variant="ghost"
    size="icon"
    class="h-9 w-9 cursor-pointer"
    @click="toggleDark()"
    title="Alternar tema"
>
    <Sun v-if="isDark" class="h-4 w-4 text-amber-400" />
    <Moon v-else class="h-4 w-4 text-slate-600" />
</Button>
```

---

## 4. Padrões de Elementos e Componentes de UI

### 4.1. Checkboxes em Tabelas e Formulários
Não utilize a tag nativa `<input type="checkbox" />`, pois o elemento HTML puro mantém um fundo branco estático no navegador.

*   **Padrão:** Sempre utilize o componente `<Checkbox>` do Shadcn Vue (`@/components/ui/checkbox`).
*   **Props de Vincular:** Deve-se passar tanto `:modelValue` quanto `:checked` e tratar o evento `@update:modelValue`.

```html
<Checkbox
    :modelValue="isSelected(item.id)"
    :checked="isSelected(item.id)"
    @update:modelValue="(val: any) => toggleDriveSelection(item.id, Boolean(val))"
    class="cursor-pointer"
/>
```

### 4.2. Switches e Alternadores de Filtros (Estilo Alto Contraste)
Para componentes de alternância de modo (como em *Contas a Pagar*, *Contas a Receber* e *Fluxo de Caixa*):

*   O container do switch utiliza `bg-muted` (`flex rounded-lg border border-border bg-muted p-1`).
*   O botão ativo deve aplicar estilo de **alto contraste invertido** e proibir alterações indesejadas no hover:

```html
<Button
    variant="ghost"
    size="sm"
    class="h-8 cursor-pointer rounded-md text-xs font-medium transition-all"
    :class="
        filterMode === 'monthly'
            ? 'bg-slate-900 text-white hover:bg-slate-900 hover:text-white dark:bg-white dark:text-slate-900 dark:hover:bg-white dark:hover:text-slate-900 shadow-sm font-bold'
            : 'text-muted-foreground hover:bg-background/60 hover:text-foreground'
    "
    @click="filterMode = 'monthly'"
>
    Filtro Mensal
</Button>
```

### 4.3. Cards Métricos e Destaques Financeiros
Para cards com destaques de status ou totais (ex: *Vencidos*, *Pagos*, *Total do Período*):

*   Não utilize fundos sólidos claros como `bg-rose-50/30` ou `bg-blue-50/30`.
*   Utilize opacidades percentuais em relação à cor base e anéis de contorno sutil:

```html
<div
    class="cursor-pointer rounded-xl border p-5 shadow-xs transition select-none hover:shadow-sm"
    :class="
        props.status === 'vencidos'
            ? 'border-rose-500 bg-rose-500/10 ring-2 ring-rose-500/30'
            : 'border-border bg-card'
    "
>
```

---

## 5. Exceções e Logotipos de Empresas

### 5.1. Painel de Logotipo na Tela de Autenticação (`AuthLayout.vue`)
Empresas cadastradas podem fazer upload de logotipos corporativos contendo tipografia ou detalhes em cores escuras (ex: marcas em preto/vermelho).

*   **Regra de Preservação:** O container direito que envolve a logomarca da empresa na tela de login permanece com fundo **branco fixo** (`bg-white`), enquanto o formulário de login à esquerda adapta-se normalmente ao tema escuro (`bg-card`).
*   **Logotipo da Plataforma (Fallback):** Para o logotipo padrão PlataformaNet, utiliza-se a classe utilitária `dark:invert` na imagem.

---

## 6. Lista de Verificação (Checklist para Code Review)

Ao criar ou revisar telas no PNET, certifique-se de:

1. [ ] Não existir nenhuma ocorrência de `bg-white`, `border-slate-100` ou `text-slate-800` em arquivos Vue (exceto o container do logo no login).
2. [ ] Substituir todo `<input type="checkbox">` pelo componente `<Checkbox>` do Shadcn.
3. [ ] Testar a tela nos modos Claro (☀️) e Escuro (🌙), garantindo a legibilidade de textos, modais, cabeçalhos de tabela e menus suspensos.
