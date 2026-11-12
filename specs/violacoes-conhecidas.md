# Violações conhecidas da constituição

Lista o código existente que viola algum princípio de [`specs/constitution.md`](constitution.md). Serve de aviso para o agente não copiar o padrão. Não é importado em sessão. Uma entrada entra no commit que encontra a violação e sai no commit que a corrige; isso não é emenda da constituição (D-02).

Verificado em 2026-11-12.

| # | Princípio | Símbolo | Violação | Resultado esperado do comando |
| --- | --- | --- | --- | --- |
| V1 | P-03 | `app/Services/CompanySettingService.php` (`CompanySettingService`) | Apaga o logo antigo dentro de `DB::transaction`. | Lista as linhas do `$disk->delete(` dentro do bloco da transação. |
| V2 | P-03 | `app/Services/UserProfileService.php` (`UserProfileService`) | Apaga a foto antiga dentro de `DB::transaction`. | Lista as linhas do `$disk->delete(` dentro do bloco da transação. |
| V3 | P-03 | `DriveService` (movimentação de arquivo depois do commit) | A falha ao mover é registrada só com `Log::info`, sem o ID do tenant; a falha secundária é ignorada sem log. | Lista a linha do `Log::info('Aviso ao mover diretório no storage:'`. |
| V4 | P-03 | `ProposalTimelineService::complete` e `ProposalDocumentService::delete` | Apagam arquivo depois do commit sem try/log. Com o disco `minio` (`throw => true`), a falha devolve "Erro" com os dados já gravados. | Uma linha em cada arquivo, sem `try` em volta. |
| V5 | P-03 | `TenantReportController` | Despacha `BuildFinishedProposalsZipJob` sem `afterCommit()`. | O bloco do `dispatch(` não tem `->afterCommit()`. |
| V6 | P-05 | `database/migrations/tenant/2026_10_01_200015_add_proposal_roles.php`, `database/migrations/tenant/2026_10_15_100002_add_registry_protocols_and_reports_permissions.php`, `database/migrations/tenant/2026_10_28_100009_sync_crm_permissions_and_remove_contracts.php` | Usam os models `Permission`/`Role` do Spatie. Já foram aplicadas: pela P-06, não são editadas. | Lista os 3 arquivos. |
| V7 | P-07 | Rota `tenant.documents.proposals.cost-items.proof.store` | Escrita (POST) protegida por `documents.proposals.view`. A Policy faz a checagem real. | Lista `tenant.documents.proposals.cost-items.proof.store`. A rota `tenant.documents.reports.finished-proposals.download` também aparece: é leitura em POST, não é violação. |
| V8 | P-08 | Entrada agregada: chamadas `Log::` com `getMessage()` em `app/` (controllers, services, commands), incluindo o `TenantProductController` usado como exemplo nas convenções §7 | A mensagem da `QueryException` inclui os valores de bind do SQL. | Lista as ocorrências (146 em 2026-11-12). |
| V9 | P-08 | Entrada agregada: `DriveService`, `TenantCompanySettingController`, `TenantProfileController`, `TenantRegistrationController` e `TenantDriveController` | Logam `'exception' => $th`, que serializa a mensagem com os binds. | Lista as ocorrências (6 em 2026-11-12). |
| V10 | P-08 | `bootstrap/app.php` (handler padrão) | Sem `report` para `QueryException`: o handler grava a mensagem completa de toda exceção não capturada. | Saída vazia. |
| V11 | P-12 | `app/Utils/Utils.php` (`Utils`) | Classe do projeto sem sufixo de tipo. | Lista `Utils.php`. |
| V12 | P-12 | `tests/Support/TenantRegistry.php` (`TenantRegistry`) | Classe do projeto sem sufixo de tipo. | Lista `TenantRegistry.php`. |

## Comandos de verificação

Rodar na raiz do repositório.

- **V1:** `grep -n '$disk->delete(' app/Services/CompanySettingService.php`
- **V2:** `grep -n '$disk->delete(' app/Services/UserProfileService.php`
- **V3:** `grep -n "Log::info" app/Services/DriveService.php`
- **V4:** `grep -n 'delete($replacedPath)' app/Services/ProposalTimelineService.php` e `grep -n 'Storage::disk($document->disk)->delete' app/Services/ProposalDocumentService.php`
- **V5:** `grep -n -A10 "BuildFinishedProposalsZipJob::dispatch(" app/Http/Controllers/TenantReportController.php`
- **V6:** `grep -rln 'Spatie\\Permission\\Models' database/migrations/tenant`
- **V8:** `grep -rn -A3 "Log::" app | grep "getMessage()"`
- **V9:** `grep -rn "'exception' => \$th" app`
- **V10:** `grep -n "QueryException" bootstrap/app.php`
- **V11:** `ls app/Utils`
- **V12:** `ls tests/Support`
- **V7:**

```bash
vendor/bin/sail php -r 'foreach (json_decode(shell_exec("php artisan route:list --json"), true) as $r) { $m = implode(" ", $r["middleware"] ?? []); if (preg_match("/POST|PUT|PATCH|DELETE/", $r["method"]) && preg_match("/PermissionMiddleware:\S+\.view(\s|$)/", $m)) echo $r["name"], PHP_EOL; }'
```
