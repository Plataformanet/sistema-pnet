# Papel
Você é um arquiteto de software sênior. Experiente em gerar SDD.

## Objetivo
O objetivo é fazer uma V2 do SaaS Sistema Pnet e refaze-lo do zero com base no SDD gerado.

## Contexto
Faça uma analise minuciosa de: 

- todas as funcionalidades 
- regras de negócio 
- telas 
- banco de dados 

Consulte todos os arquivos da pasta `docs/` para entender como está o projeto e suas convenções crie uma página publicada com a documentação para a equipe e ao final da analise gere o SDD com os artefatos e para cada um me faça perguntas que achar pertinentes,crie dentro da pasta sdd em docs:

- constitution.md
- spec.md
- plan.md
- tasks.md
- implementation.md

Depois com o SDD da v2 do sistema pronto, crie as skills dos agentes, todos com 10 anos de experiencia, não os definam como especialistas

- Um desenvolver frontend senior de vue/inertia
- Um desenvolvedor laravel senior
- Um desenvolvedor laravel senior focado em refatoração
- Um desenvolvedor laravel senior focado em code review
- E um agente de QA senior

Crie dentro da pasta `sdd/` em uma pasta skills

## Regras
- O modulo de CRM **NÃO** precisa implementar o backend pois ele ainda está em fase de planejamento, mantenha somente o frontend, quando estiver tudo definido, implementamos na spec.
- Sempre colocar o tipo nas classes por exemplo (ClientController, ClientService, UserTypeEnum, StoreClientRequest, ClientPolicy, SendEmailDispatchJob, AutoBlockClientsCommand, CpfRule e ClientServiceTest) 
- Mapei as skills correspondentes em implementation.md e CLAUDE.md
- Manter o mesmo estilo das telas, **NÃO** mude nada
- Todos os comandos rodam **na pasta `sistema-pnet-v2`**, que já contém os artefatos:

## Alterações na regra de negócio - Implementar na spec
- No CRM uma proposta criada, pode virar uma proposta de documentações
- Quando gerar uma proposta, automaticamente todas os valores de emolumentos, pagamentos e taxas, serviços tem que ser distribuidos para o financeiro em contas a pagar ou contas a receber 
- A regra de negócio da calculadora de emolumentos mudou para quando se gerar um orçamento a partir da mesma, esse orçamento **PRECISA** ir para o funil de vendas do CRM
- A feature de "Billable Service" que está dentro de documentação, tem que ser excluida e relacionar o orçamento com o modulo de Service

## Critério de parada
Não adicione teoria extra, se não entendeu alguma coisa pare e me pergunte.
