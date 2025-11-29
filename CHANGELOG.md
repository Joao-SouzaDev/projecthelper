# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

## [1.3.0] - 2025-11-29

### ✨ Novo
- **Múltiplas seleções**: Agora é possível selecionar múltiplos modos de replicação simultaneamente para followups e tasks
  - Exemplo: Replicar para tickets do projeto (modo 1) + tickets relacionados (modo 4) ao mesmo tempo
  - Interface atualizada com checkboxes em vez de dropdown único
  
- **Modo 4 - Tickets Relacionados**: Novo modo de replicação que replica followups/tasks para tickets relacionados (link = 2)
  - Funciona com relacionamentos bidirecionais do GLPI
  - Independente de projetos ou hierarquia pai/filho

### 🔧 Modificado
- Campos `replicate_followups` e `replicate_tasks` alterados de TINYINT para VARCHAR(50)
  - Armazena múltiplas seleções como string separada por vírgulas (ex: "1,2,4")
- Lógica de replicação refatorada para processar múltiplos modos em um único evento
- Remoção automática de duplicatas ao combinar resultados de diferentes modos

### 📚 Documentação
- Atualizada documentação para refletir novo modo 4 e múltiplas seleções
- Guia de configuração atualizado com exemplos de uso combinado

### 🛠️ Técnico
- Nova migration v1.3.0 converte campos TINYINT para VARCHAR automaticamente
- Métodos `getRelatedTickets()` adicionados em FollowupHandler e TaskHandler
- Lógica de loop para processar array de modos de replicação

---

## [1.2.1] - 2025-11-29

### 🔧 Modificado
- **Tasks replicadas agora são apenas informativas**: As tarefas replicadas não incluem mais apontamento de tempo (`actiontime`, `begin`, `end` são zerados/nulos)
- **State sempre definido como "Information"**: Tasks replicadas sempre têm `state = 1` (Information)
- **Motivação**: Evitar duplicação de horas trabalhadas nos relatórios do GLPI

### 📝 Comportamento Anterior vs Novo

#### Antes (v1.2.0):
```
Ticket #100: Task com 2 horas de trabalho
   ↓ (replicava)
Ticket #101: Task com 2 horas (DUPLICADO)
Ticket #102: Task com 2 horas (DUPLICADO)
Resultado: 6 horas nos relatórios (ERRADO!)
```

#### Agora (v1.2.1):
```
Ticket #100: Task com 2 horas de trabalho (ORIGINAL)
   ↓ (replica como informação)
Ticket #101: Task informativa (0 horas)
Ticket #102: Task informativa (0 horas)
Resultado: 2 horas nos relatórios (CORRETO!)
```

### 📚 Documentação
- Atualizado `TASK_REPLICATION.md` com explicação sobre tasks informativas
- Atualizado `README.md` com aviso importante sobre apontamento de tempo
- Adicionado `CHANGELOG.md` para rastrear mudanças

---

## [1.2.0] - 2025-11-23

### ✨ Novo
- **Replicação de Tasks**: Implementada replicação de tarefas de tickets (`glpi_tickettasks`)
- Mesma lógica dos followups: 3 modos (todos do projeto, pai→filhos, filho→pai)
- Novo handler: `TaskHandler.php`
- Nova configuração: `replicate_tasks` no banco de dados

### 📝 Arquivos Adicionados
- `src/TaskHandler.php`
- `TASK_REPLICATION.md`

### 🔧 Modificado
- `src/Install.php`: Adicionado campo `replicate_tasks`
- `hook.php`: Registrado hook para `TicketTask`
- `front/config.form.php`: Adicionada opção de configuração para tasks

---

## [1.1.0] - 2025-11-23

### ✨ Novo
- **Três modos de replicação de followups**:
  1. Todos os tickets do projeto
  2. Pai para filhos
  3. Filho para pai
- Suporte para hierarquia de tickets (tabela `glpi_tickets_tickets`)

### 🔧 Modificado
- `src/FollowupHandler.php`: Refatorado para suportar 3 modos
- `front/config.form.php`: Atualizado dropdown com 3 opções
- `src/Install.php`: Migration para v1.1.0

### 📚 Documentação
- Atualizado `FOLLOWUP_REPLICATION.md` com detalhes dos 3 modos
- Atualizado `.github/copilot-instructions.md`

---

## [1.0.1] - 2025-11-20

### 🔧 Modificado
- Migration de `replicate_followups` de VARCHAR para TINYINT
- Melhorias na estrutura do banco de dados

---

## [1.0.0] - 2025-11-20

### ✨ Inicial
- **Replicação de Followups**: Replica acompanhamentos entre tickets do mesmo projeto
- **Barra de Progresso**: Exibição visual de progresso de projetos
- Proteção contra recursão infinita
- Interface de configuração

### 📝 Arquivos Iniciais
- `src/FollowupHandler.php`
- `src/Config.php`
- `src/Install.php`
- `front/config.form.php`
- `hook.php`

---

## Formato

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

### Tipos de Mudanças
- **✨ Novo** - para novas funcionalidades
- **🔧 Modificado** - para mudanças em funcionalidades existentes
- **🗑️ Descontinuado** - para funcionalidades que serão removidas
- **🚫 Removido** - para funcionalidades removidas
- **🐛 Corrigido** - para correção de bugs
- **🔒 Segurança** - para correções de vulnerabilidades
