# Funcionalidade de Replicação de Tarefas (Tasks)

## Descrição

Este plugin inclui a funcionalidade de replicar automaticamente tarefas (ticket tasks) entre tickets relacionados no GLPI. Similar à replicação de followups, mas aplicada especificamente às tarefas dos tickets.

## Como Funciona

1. **Configuração**: Na página de configuração do plugin (`Config > Plugins > Project Helper`), você encontrará a opção "Replicate tasks from linked tickets to the Project" com quatro valores:
   - **No** (0): Desabilitado - não replica tarefas
   - **Yes, replicate to all project tickets** (1): Replica automaticamente para todos os tickets do mesmo projeto
   - **Yes, replicate from parent to children** (2): Replica de ticket pai para todos os tickets filhos
   - **Yes, replicate from child to parent** (3): Replica de ticket filho para o ticket pai

2. **Comportamento**: 
   
   **Modo 1 - Todos do projeto**: Quando um usuário adiciona uma tarefa a um ticket vinculado a um projeto:
   - O plugin identifica automaticamente o projeto associado através da tabela `glpi_itils_projects`
   - Busca todos os outros tickets vinculados ao mesmo projeto
   - Replica a tarefa para cada um desses tickets
   
   **Modo 2 - Pai para filhos**: Quando um usuário adiciona uma tarefa a um ticket pai:
   - O plugin identifica todos os tickets filhos através da tabela `glpi_tickets_tickets`
   - Busca onde `tickets_id_2 = ticket_pai` e `link = 3`
   - Replica a tarefa para cada ticket filho
   
   **Modo 3 - Filho para pai**: Quando um usuário adiciona uma tarefa a um ticket filho:
   - O plugin identifica o ticket pai através da tabela `glpi_tickets_tickets`
   - Busca onde `tickets_id_1 = ticket_filho` e `link = 3`
   - Replica a tarefa para o ticket pai
   
3. **Proteção contra Recursão**: O sistema possui proteção contra recursão infinita, garantindo que as tarefas replicadas não sejam replicadas novamente.

## Estrutura Técnica

### Arquivos

1. **src/TaskHandler.php**
   - Classe responsável pela lógica de replicação de tasks
   - Métodos principais:
     - `afterAddTask()`: Hook principal que intercepta a criação de tasks
     - `getProjectFromTicket()`: Identifica o projeto de um ticket
     - `getTicketsFromProject()`: Busca todos os tickets de um projeto
     - `getChildrenTickets()`: Busca todos os tickets filhos de um ticket pai
     - `getParentTicket()`: Busca o ticket pai de um ticket filho
     - `replicateTask()`: Cria uma cópia da task em outro ticket

2. **hook.php**
   - Registro do hook `item_add` para TicketTask
   - Hook: `$PLUGIN_HOOKS['item_add']['projecthelper']['TicketTask']`

3. **src/Install.php**
   - Tabela `glpi_plugin_projecthelper_configs` possui o campo `replicate_tasks`

## Fluxo de Dados

### Modo 1: Todos do projeto
```
Usuário adiciona task no Ticket A
         ↓
Hook item_add intercepta (TaskHandler::afterAddTask)
         ↓
Verifica configuração (replicate_tasks == 1?)
         ↓
Busca projeto do Ticket A via glpi_itils_projects
         ↓
Busca todos tickets do mesmo projeto (exceto Ticket A)
         ↓
Para cada ticket relacionado:
    - Cria nova task com mesmo conteúdo
    - Mantém autor, data, privacidade e tempos originais
```

### Modo 2: Pai para filhos
```
Usuário adiciona task no Ticket Pai
         ↓
Hook item_add intercepta (TaskHandler::afterAddTask)
         ↓
Verifica configuração (replicate_tasks == 2?)
         ↓
Busca tickets filhos via glpi_tickets_tickets
    (tickets_id_2 = Ticket Pai, link = 3)
         ↓
Para cada ticket filho:
    - Cria nova task com mesmo conteúdo
    - Mantém autor, data, privacidade e tempos originais
```

### Modo 3: Filho para pai
```
Usuário adiciona task no Ticket Filho
         ↓
Hook item_add intercepta (TaskHandler::afterAddTask)
         ↓
Verifica configuração (replicate_tasks == 3?)
         ↓
Busca ticket pai via glpi_tickets_tickets
    (tickets_id_1 = Ticket Filho, link = 3)
         ↓
Se encontrar pai:
    - Cria nova task com mesmo conteúdo
    - Mantém autor, data, privacidade e tempos originais
```

## Estrutura de Banco de Dados

O plugin utiliza as tabelas padrão do GLPI:
- `glpi_itils_projects`: Relacionamento direto entre tickets e projetos (usado no modo 1)
- `glpi_tickets_tickets`: Relacionamento pai/filho entre tickets (usado nos modos 2 e 3)
  - `tickets_id_1`: ID do ticket filho
  - `tickets_id_2`: ID do ticket pai
  - `link`: Tipo de relação (3 = relação pai/filho)
- `glpi_tickettasks`: Tarefas dos tickets
- `glpi_plugin_projecthelper_configs`: Configurações do plugin (campo: `replicate_tasks`)

## Campos Replicados

### ✅ Campos que SÃO replicados:
- **tickets_id**: Alterado para o ticket de destino
- **taskcategories_id**: Categoria da tarefa
- **date**: Data de criação original
- **users_id**: Usuário criador
- **users_id_tech**: Técnico responsável
- **groups_id_tech**: Grupo técnico responsável
- **content**: Conteúdo/descrição da tarefa
- **is_private**: Se a tarefa é privada

### ❌ Campos que NÃO são replicados:
- **begin**: Data/hora de início (sempre NULL)
- **end**: Data/hora de término (sempre NULL)
- **actiontime**: Tempo de duração (sempre 0)
- **state**: Estado da tarefa (sempre 1 = Information)

### 📝 Importante:
As tarefas replicadas são **apenas informativas**. Elas servem para manter todos os tickets relacionados informados sobre o trabalho sendo realizado, mas **não contam como apontamento de tempo**. Isso evita duplicação de horas trabalhadas nos relatórios do GLPI.

## Exemplo de Uso

### Modo 1: Todos do projeto

1. **Cenário**: Projeto "Implantação de Sistema" com 3 tickets:
   - Ticket #100: Configuração
   - Ticket #101: Testes
   - Ticket #102: Documentação

2. **Ação**: Gerente adiciona task no Ticket #100:
   ```
   Categoria: Planejamento
   Conteúdo: "Revisar requisitos com o cliente"
   Duração: 2 horas
   Técnico: João Silva
   ```

3. **Resultado**: Se `replicate_tasks = 1`, a mesma tarefa é automaticamente adicionada aos Tickets #101 e #102.

### Modo 2: Pai para filhos

1. **Cenário**: Ticket pai #200 "Manutenção Mensal" com 2 filhos:
   - Ticket #201: Backup (filho)
   - Ticket #202: Atualização (filho)

2. **Ação**: Coordenador adiciona task no Ticket #200:
   ```
   Conteúdo: "Verificar logs de sistema"
   Duração: 1 hora
   Estado: Planejado
   ```

3. **Resultado**: Se `replicate_tasks = 2`, a mesma tarefa é automaticamente adicionada aos Tickets #201 e #202.

### Modo 3: Filho para pai

1. **Cenário**: Ticket pai #300 "Projeto Integração" com filho:
   - Ticket #301: API REST (filho)

2. **Ação**: Desenvolvedor adiciona task no Ticket #301:
   ```
   Conteúdo: "API REST concluída e testada"
   Duração: 8 horas
   Estado: Concluído
   ```

3. **Resultado**: Se `replicate_tasks = 3`, a mesma tarefa é automaticamente adicionada ao Ticket #300.

## Observações Importantes

- **Modo 1**: A replicação só ocorre para tickets **vinculados ao mesmo projeto via glpi_itils_projects**
- **Modos 2 e 3**: A replicação funciona independente de projetos, apenas com base na relação pai/filho
- Tickets sem vínculo apropriado (projeto ou pai/filho) não acionam a replicação
- **Tasks replicadas são INFORMATIVAS**: Não contam como apontamento de tempo, servem apenas para comunicação
- A configuração pode ser alterada a qualquer momento pelo administrador
- Logs de debug estão desabilitados por padrão para evitar problemas de permissão

### 🎯 Por que tasks replicadas são apenas informativas?

**Problema**: Se replicássemos os tempos (`actiontime`, `begin`, `end`), teríamos duplicação de horas nos relatórios do GLPI.

**Exemplo**:
- Técnico registra 2 horas no Ticket #100
- Se replicasse para Tickets #101 e #102, o sistema contaria 6 horas (2h × 3 tickets)
- Isso distorceria totalmente os relatórios de produtividade

**Solução**: Tasks replicadas são marcadas como tipo "Information" (state=1) e não têm apontamento de tempo (`actiontime=0`). Isso permite que todos os tickets relacionados fiquem informados sobre o trabalho, mas apenas o ticket original conta o tempo real trabalhado.

## Diferenças entre Followups e Tasks

| Aspecto | Followups | Tasks (Original) | Tasks (Replicada) |
|---------|-----------|------------------|-------------------|
| Tabela | `glpi_itilfollowups` | `glpi_tickettasks` | `glpi_tickettasks` |
| Campos replicados | content, users_id, date, is_private | N/A | content, users_id, date, is_private, category, técnico |
| Uso | Acompanhamento/comentários | Trabalho técnico real | Informação sobre trabalho |
| Tempo | Não rastreia | Rastreia tempo (actiontime) | **NÃO rastreia** (actiontime=0) |
| Técnico | Apenas autor | Autor + técnico + grupo | Autor + técnico + grupo |
| State | N/A | Variável (Planned/Done) | Sempre 1 (Information) |
| Apontamento | Não | **Sim** | **Não** |

## Casos de Uso

1. **Planejamento em cascata**: Tarefas definidas no ticket pai são automaticamente propagadas para todos os filhos
2. **Sincronização de projeto**: Todas as tarefas adicionadas em qualquer ticket do projeto aparecem em todos
3. **Reporte de conclusão**: Tarefas concluídas em tickets filhos são reportadas ao ticket pai
4. **Gestão de tempo**: Permite consolidar o tempo gasto em tarefas entre tickets relacionados

## Próximos Passos (Futuras Implementações)

- [ ] Sincronização bidirecional de tasks (atualização de tarefas já replicadas)
- [ ] Consolidação de tempo (somar actiontime de tasks replicadas)
- [ ] Filtro por categoria de task
- [ ] Opção de replicar apenas tasks de determinados técnicos
- [ ] Dashboard de tarefas replicadas
- [ ] Notificações quando tasks são replicadas

## Compatibilidade

- GLPI 10.0.0 ou superior
- Funciona em conjunto com a replicação de followups (configurações independentes)
- Não interfere com outras funcionalidades de tasks do GLPI
