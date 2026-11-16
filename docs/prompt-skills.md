# Tarefa: criar/ajustar skills customizadas compatíveis com o Elephant-Goldfish Model (EGM)

## Contexto
Este projeto usa o plugin EGM (google/elephant-goldfish-model) para spec-driven development:
- **Elephant**: sessão com todo o contexto. Faz brainstorm, draft, resolve e implementação.
- **Goldfish**: subagentes com contexto ZERO. Revisam a spec lendo só o documento e o código
  que ele referencia. O isolamento deles é o que dá valor à revisão.
- Skills do EGM: egm-brainstorm, egm-draft, egm-review, egm-review-loop, egm-resolve,
  egm-bootstrap, egm-drift-report.
- Artefatos do EGM: concepts_brief.md, design_draft.md, docs/designs/*.md e os relatórios
  de review (*_report.md).

Skills a criar ou ajustar:
1. laravel-development-senior (construtora: backend)
2. frontend-senior (construtora: Vue/Inertia/Blade/Tailwind)
3. laravel-code-review (avaliadora: revisa CÓDIGO)
4. laravel-code-refactoring (construtora: refatoração)
5. qa-senior (dois modos: escrever critérios de aceite e planejar/executar testes)

## Antes de escrever qualquer coisa
1. Leia o CLAUDE.md do projeto e as skills existentes em .claude/skills/ e ~/.claude/skills/.
2. Leia os SKILL.md do plugin EGM instalado, para conhecer os nomes e as etapas reais.
3. Me mostre um resumo do que encontrou e o plano de mudanças por skill. **Espere minha aprovação.**

## Regras obrigatórias para todas as skills

### R1. A description não pode competir com o EGM
- Gatilhos focados em CÓDIGO Laravel/front: "implementar", "criar controller", "refatorar",
  "revisar este código/diff/PR", "gerar testes Pest", "plano de testes".
- NUNCA usar como gatilho: "spec", "especificação", "design doc", "brainstorm", "plano",
  "revisar documento", "revisar spec", "drift".
- Manter gatilhos bilíngues (PT + EN), como nas minhas skills atuais.

### R2. Declarar o papel no início do SKILL.md
Incluir uma seção "Papel no fluxo EGM" dizendo se a skill é CONSTRUTORA (roda na sessão
Elephant) ou AVALIADORA (roda em sessão nova), e em quais etapas pode ser usada.

### R3. Nunca contaminar os Goldfish
Incluir no corpo de toda skill:
> Se você estiver atuando como revisor Goldfish do EGM (subagente de egm-review ou
> egm-review-loop), NÃO aplique esta skill. Avalie apenas o documento e as fontes
> referenciadas por ele.

### R4. Respeitar o fluxo e as pausas do EGM
- Durante egm-brainstorm, egm-draft ou egm-review: não gerar código, não criar arquivos
  fora dos artefatos do EGM, não pular as pausas por seção.
- Só editar docs/designs/*.md durante egm-resolve, e só com a minha aprovação.
- Não escrever nos arquivos do EGM (concepts_brief.md, design_draft.md, *_report.md).

### R5. A spec aprovada é a fonte da verdade
- Antes de implementar, ler a spec em docs/designs/ (ou a que eu indicar).
- Se uma convenção da skill conflitar com a spec aprovada: PARAR, explicar o conflito e
  sugerir corrigi-lo via egm-resolve. Nunca desviar da spec em silêncio.
- Não implementar nada que não esteja em uma task da spec (sem escopo extra).

### R6. Não duplicar o EGM
- Nenhuma skill deve ter template de spec, checklist de revisão de spec ou fluxo próprio
  de design. Isso é papel do EGM.
- Skills avaliadoras revisam CÓDIGO, usando a spec aprovada como critério.

### R7. Só conhecimento específico do projeto
- Não incluir conselhos genéricos que o modelo já sabe ("evite N+1", "use Form Requests"),
  a não ser que exista uma regra concreta do projeto ligada a eles.
- Priorizar: decisões de arquitetura, isolamento de tenants, estrutura de pastas,
  nomenclatura, padrões de filas/falhas, o que é proibido.
- Regras que valem SEMPRE (versões, stack, multi-tenancy) vão para o CLAUDE.md, não para
  a skill. Proponha essa mudança separadamente.

### R8. Severidade alinhada com o EGM
Skills avaliadoras classificam achados como **Blocker / Major / Nit**, com referência ao
item da spec ou ao arquivo:linha.

## Regras específicas por skill

| Skill | Papel | Pode usar em | Não deve |
|---|---|---|---|
| laravel-development-senior | Construtora | brainstorm/draft (quando eu pedir), resolve, implementação | gerar código durante draft/review |
| frontend-senior | Construtora | seção "Interface" do draft, resolve, implementação | redesenhar UI fora da spec |
| laravel-code-review | Avaliadora | sessão nova, após implementação | revisar specs, carregar o histórico da conversa |
| laravel-code-refactoring | Construtora | após bootstrap/drift; exige um design doc de refactor aprovado antes de mexer no código | refatorar sem testes passando antes e depois |
| qa-senior | Construtora (critérios de aceite no draft) e Avaliadora (executar testes em sessão nova) | draft, resolve, pós-spec (testes Pest/TDD), pós-implementação | alterar a spec fora do egm-resolve |

## Validação (obrigatória antes de concluir)
Para cada skill:
1. Confira as regras R1 a R8 e mostre o checklist marcado.
2. Liste 3 prompts que DEVEM disparar a skill e 3 que NÃO devem (ex.: "/egm-review
   docs/designs/x.md", "revise esta spec", "faça um brainstorm sobre...").
3. Aponte qualquer instrução que possa conflitar com outra skill ou com o EGM.

## Entrega
- Mostre o diff de cada SKILL.md antes de salvar.
- Proponha as mudanças no CLAUDE.md em um bloco separado.
- Não salve nada sem a minha aprovação.
