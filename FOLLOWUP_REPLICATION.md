# Funcionalidade de Replicação de Acompanhamentos

## Descrição

Este plugin agora inclui a funcionalidade de replicar automaticamente acompanhamentos (followups) entre tickets que pertencem ao mesmo projeto no GLPI.

## Como Funciona

1. **Configuração**: Na página de configuração do plugin (`Config > Plugins > Project Helper`), você encontrará a opção "Replicate follow-ups from linked tickets to the Project" com três valores:
   - **No** (0): Desabilitado - não replica acompanhamentos
   - **Yes, for all** (1): Habilitado - replica automaticamente para todos os tickets do mesmo projeto
   - **Yes, select per ticket** (2): Reservado para implementação futura (seleção por ticket)

2. **Comportamento**: Quando um usuário adiciona um acompanhamento a um ticket que está vinculado a um projeto:
   - O plugin identifica automaticamente o projeto associado através da tabela `glpi_itils_projects`
   - Busca todos os outros tickets vinculados ao mesmo projeto
   - Replica o acompanhamento para cada um desses tickets
   
3. **Proteção contra Recursão**: O sistema possui proteção contra recursão infinita, garantindo que os acompanhamentos replicados não sejam replicados novamente.

## Estrutura Técnica

### Arquivos Criados/Modificados

1. **src/FollowupHandler.php** (NOVO)
   - Classe responsável pela lógica de replicação
   - Métodos principais:
     - `afterAddFollowup()`: Hook principal que intercepta a criação de followups
     - `getProjectFromTicket()`: Identifica o projeto de um ticket
     - `getTicketsFromProject()`: Busca todos os tickets de um projeto
     - `replicateFollowup()`: Cria uma cópia do followup em outro ticket

2. **hook.php** (MODIFICADO)
   - Adicionado registro do hook `item_add` para ITILFollowup
   - Hook: `$PLUGIN_HOOKS['item_add']['projecthelper']['ITILFollowup']`

3. **src/Config.php** (MODIFICADO)
   - Adicionado `static protected $notable = false` para compatibilidade

4. **src/Install.php** (JÁ EXISTIA)
   - Tabela `glpi_plugin_projecthelper_configs` já possui o campo `replicate_followups`

## Fluxo de Dados

```
Usuário adiciona followup no Ticket A
         ↓
Hook item_add intercepta (FollowupHandler::afterAddFollowup)
         ↓
Verifica configuração (replicate_followups == 1?)
         ↓
Busca projeto do Ticket A via glpi_itils_projects
         ↓
Busca todos tickets do mesmo projeto (exceto Ticket A)
         ↓
Para cada ticket relacionado:
    - Cria novo followup com mesmo conteúdo
    - Mantém autor, data e privacidade originais
```

## Estrutura de Banco de Dados

O plugin utiliza as tabelas padrão do GLPI:
- `glpi_itils_projects`: Relacionamento direto entre tickets e projetos
- `glpi_itilfollowups`: Acompanhamentos dos tickets
- `glpi_plugin_projecthelper_configs`: Configurações do plugin (campo: `replicate_followups`)

## Exemplo de Uso

1. **Cenário**: Projeto "Migração de Servidor" com 3 tickets:
   - Ticket #100: Preparação
   - Ticket #101: Execução
   - Ticket #102: Validação

2. **Ação**: Técnico adiciona acompanhamento no Ticket #100:
   ```
   "Servidor de backup configurado e testado. Pronto para migração."
   ```

3. **Resultado**: Se `replicate_followups = 1`, o mesmo acompanhamento é automaticamente adicionado aos Tickets #101 e #102.

## Observações Importantes

- A replicação só ocorre para tickets **vinculados ao mesmo projeto via glpi_itils_projects**
- Tickets sem vínculo com projetos não acionam a replicação
- O acompanhamento replicado mantém todas as características do original (autor, data, privacidade)
- A configuração pode ser alterada a qualquer momento pelo administrador
- Logs de debug estão desabilitados por padrão para evitar problemas de permissão

## Próximos Passos (Futuras Implementações)

- Implementar opção "Yes, select per ticket" (valor 2) para permitir seleção manual por ticket
- Adicionar interface na tela do ticket para escolher se deseja replicar ou não
- Adicionar log de auditoria das replicações realizadas
- Permitir filtros de quais tickets devem receber a replicação
