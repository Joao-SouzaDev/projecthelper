# Funcionalidade de Replicação de Acompanhamentos

## Descrição

Este plugin agora inclui a funcionalidade de replicar automaticamente acompanhamentos (followups) entre tickets que pertencem ao mesmo projeto no GLPI.

## Como Funciona

1. **Configuração**: Na página de configuração do plugin (`Config > Plugins > Project Helper`), você encontrará a opção "Replicate follow-ups from linked tickets to the Project" com quatro valores:
   - **No** (0): Desabilitado - não replica acompanhamentos
   - **Yes, replicate to all project tickets** (1): Replica automaticamente para todos os tickets do mesmo projeto
   - **Yes, replicate from parent to children** (2): Replica de ticket pai para todos os tickets filhos
   - **Yes, replicate from child to parent** (3): Replica de ticket filho para o ticket pai

2. **Comportamento**: 
   
   **Modo 1 - Todos do projeto**: Quando um usuário adiciona um acompanhamento a um ticket vinculado a um projeto:
   - O plugin identifica automaticamente o projeto associado através da tabela `glpi_itils_projects`
   - Busca todos os outros tickets vinculados ao mesmo projeto
   - Replica o acompanhamento para cada um desses tickets
   
   **Modo 2 - Pai para filhos**: Quando um usuário adiciona um acompanhamento a um ticket pai:
   - O plugin identifica todos os tickets filhos através da tabela `glpi_tickets_tickets`
   - Busca onde `tickets_id_2 = ticket_pai` e `link = 3`
   - Replica o acompanhamento para cada ticket filho
   
   **Modo 3 - Filho para pai**: Quando um usuário adiciona um acompanhamento a um ticket filho:
   - O plugin identifica o ticket pai através da tabela `glpi_tickets_tickets`
   - Busca onde `tickets_id_1 = ticket_filho` e `link = 3`
   - Replica o acompanhamento para o ticket pai
   
3. **Proteção contra Recursão**: O sistema possui proteção contra recursão infinita, garantindo que os acompanhamentos replicados não sejam replicados novamente.

## Estrutura Técnica

### Arquivos Criados/Modificados

1. **src/FollowupHandler.php** (NOVO)
   - Classe responsável pela lógica de replicação
   - Métodos principais:
     - `afterAddFollowup()`: Hook principal que intercepta a criação de followups
     - `getProjectFromTicket()`: Identifica o projeto de um ticket
     - `getTicketsFromProject()`: Busca todos os tickets de um projeto
     - `getChildrenTickets()`: Busca todos os tickets filhos de um ticket pai
     - `getParentTicket()`: Busca o ticket pai de um ticket filho
     - `replicateFollowup()`: Cria uma cópia do followup em outro ticket

2. **hook.php** (MODIFICADO)
   - Adicionado registro do hook `item_add` para ITILFollowup
   - Hook: `$PLUGIN_HOOKS['item_add']['projecthelper']['ITILFollowup']`

3. **src/Config.php** (MODIFICADO)
   - Adicionado `static protected $notable = false` para compatibilidade

4. **src/Install.php** (JÁ EXISTIA)
   - Tabela `glpi_plugin_projecthelper_configs` já possui o campo `replicate_followups`

## Fluxo de Dados

### Modo 1: Todos do projeto
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

### Modo 2: Pai para filhos
```
Usuário adiciona followup no Ticket Pai
         ↓
Hook item_add intercepta (FollowupHandler::afterAddFollowup)
         ↓
Verifica configuração (replicate_followups == 2?)
         ↓
Busca tickets filhos via glpi_tickets_tickets
    (tickets_id_2 = Ticket Pai, link = 3)
         ↓
Para cada ticket filho:
    - Cria novo followup com mesmo conteúdo
    - Mantém autor, data e privacidade originais
```

### Modo 3: Filho para pai
```
Usuário adiciona followup no Ticket Filho
         ↓
Hook item_add intercepta (FollowupHandler::afterAddFollowup)
         ↓
Verifica configuração (replicate_followups == 3?)
         ↓
Busca ticket pai via glpi_tickets_tickets
    (tickets_id_1 = Ticket Filho, link = 3)
         ↓
Se encontrar pai:
    - Cria novo followup com mesmo conteúdo
    - Mantém autor, data e privacidade originais
```

## Estrutura de Banco de Dados

O plugin utiliza as tabelas padrão do GLPI:
- `glpi_itils_projects`: Relacionamento direto entre tickets e projetos (usado no modo 1)
- `glpi_tickets_tickets`: Relacionamento pai/filho entre tickets (usado nos modos 2 e 3)
  - `tickets_id_1`: ID do ticket filho
  - `tickets_id_2`: ID do ticket pai
  - `link`: Tipo de relação (3 = relação pai/filho)
- `glpi_itilfollowups`: Acompanhamentos dos tickets
- `glpi_plugin_projecthelper_configs`: Configurações do plugin (campo: `replicate_followups`)

## Exemplo de Uso

### Modo 1: Todos do projeto

1. **Cenário**: Projeto "Migração de Servidor" com 3 tickets:
   - Ticket #100: Preparação
   - Ticket #101: Instalação
   - Ticket #102: Validação

2. **Ação**: Técnico adiciona no Ticket #100:
   ```
   "Servidor de backup configurado e testado. Pronto para migração."
   ```

3. **Resultado**: Se `replicate_followups = 1`, o mesmo acompanhamento é automaticamente adicionado aos Tickets #101 e #102.

### Modo 2: Pai para filhos

1. **Cenário**: Ticket pai #200 "Atualização de Sistema" com 2 filhos:
   - Ticket #201: Backup (filho)
   - Ticket #202: Instalação (filho)

2. **Ação**: Gerente adiciona no Ticket #200:
   ```
   "Atualização aprovada. Iniciar procedimentos."
   ```

3. **Resultado**: Se `replicate_followups = 2`, o mesmo acompanhamento é automaticamente adicionado aos Tickets #201 e #202.

### Modo 3: Filho para pai

1. **Cenário**: Ticket pai #300 "Projeto X" com filho:
   - Ticket #301: Subtarefa A (filho)

2. **Ação**: Técnico adiciona no Ticket #301:
   ```
   "Subtarefa concluída com sucesso."
   ```

3. **Resultado**: Se `replicate_followups = 3`, o mesmo acompanhamento é automaticamente adicionado ao Ticket #300.

## Observações Importantes

- **Modo 1**: A replicação só ocorre para tickets **vinculados ao mesmo projeto via glpi_itils_projects**
- **Modos 2 e 3**: A replicação funciona independente de projetos, apenas com base na relação pai/filho
- Tickets sem vínculo apropriado (projeto ou pai/filho) não acionam a replicação
- O acompanhamento replicado mantém todas as características do original (autor, data, privacidade)
- A configuração pode ser alterada a qualquer momento pelo administrador
- Logs de debug estão desabilitados por padrão para evitar problemas de permissão

## Próximos Passos (Futuras Implementações)

- [ ] Interface na tela do ticket para escolher modo de replicação temporariamente
- [ ] Log de auditoria das replicações realizadas
- [ ] Filtros avançados (por status, categoria, etc.)
- [ ] Notificações configuráveis
- [ ] Painel de estatísticas de replicações
- [ ] Modo híbrido (combinar projeto + pai/filho)
