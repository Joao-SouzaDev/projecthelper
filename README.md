# ProjectHelper GLPI plugin

Plugin para GLPI que adiciona funcionalidades auxiliares para gerenciamento de projetos.

## Funcionalidades

### 🔄 Replicação Automática de Acompanhamentos e Tarefas
- Replica automaticamente acompanhamentos (followups) e tarefas (tasks) entre tickets relacionados
- **Quatro modos de replicação** (✨ v1.3.0):
  1. Todos os tickets do projeto
  2. Pai para filhos
  3. Filho para pai
  4. **Tickets relacionados (NOVO)** ✨
- **Múltiplas seleções**: Combine vários modos simultaneamente (✨ v1.3.0)
- Configurável via interface do plugin
- Mantém autor, data e privacidade originais

### 📊 Barra de Progresso de Projetos
- Exibe barra de progresso visual nos projetos
- Configurável via interface do plugin

## Instalação

1. Extraia o plugin na pasta `plugins/projecthelper` do GLPI
2. Acesse **Configuração > Plugins** no GLPI
3. Instale e ative o plugin **Project Helper**
4. Configure as opções em **Configuração > Plugins > Project Helper**

## Configuração

### Replicação de Acompanhamentos e Tarefas (v1.3.0+)

1. Acesse **Configuração > Plugins > Project Helper**
2. Selecione um ou mais modos de replicação (checkboxes):

#### Modos Disponíveis:
- ☐ **No**: Desabilitado
- ☐ **Yes, replicate to all project tickets**: Replica para todos os tickets vinculados ao mesmo projeto
- ☐ **Yes, replicate from parent to children**: Replica de um ticket pai para todos os seus tickets filhos
- ☐ **Yes, replicate from child to parent**: Replica de um ticket filho para o seu ticket pai
- ☐ **Yes, replicate to related tickets** ✨ **NOVO**: Replica para tickets relacionados (link "Relacionado a")

#### Múltiplas Seleções ✨ NOVO
Você pode marcar **vários modos ao mesmo tempo**!

**Exemplos**:
- Modos 1 + 4: Replica para tickets do projeto E tickets relacionados
- Modos 2 + 3: Replica pai↔filho (bidirecional)
- Modos 1 + 2 + 4: Máxima cobertura (projeto + hierarquia + relacionados)

> 💡 **Dica**: Use apenas os modos necessários para seu fluxo de trabalho para melhor performance.

> **⚠️ Importante**: As tarefas replicadas são apenas **informativas** e não contam como apontamento de tempo. Elas servem para manter todos os tickets relacionados informados sobre o trabalho sendo realizado, mas o tempo trabalhado (`actiontime`) só é contabilizado no ticket original. Isso evita duplicação de horas nos relatórios do GLPI.

## Uso

Após configurar a replicação de acompanhamentos/tarefas:

**Modo 1 - Todos do projeto:**
1. Vincule múltiplos tickets a um mesmo projeto
2. Adicione um acompanhamento/tarefa em qualquer um dos tickets
3. Será automaticamente replicado para todos os outros tickets do projeto

**Modo 2 - Pai para filhos:**
1. Crie uma relação pai/filho entre tickets
2. Adicione um acompanhamento/tarefa no ticket pai
3. Será automaticamente replicado para todos os tickets filhos

**Modo 3 - Filho para pai:**
1. Crie uma relação pai/filho entre tickets
2. Adicione um acompanhamento/tarefa no ticket filho
3. Será automaticamente replicado para o ticket pai

**Modo 4 - Tickets relacionados** ✨ **NOVO (v1.3.0)**:
1. Crie um relacionamento "Relacionado a" entre tickets
2. Adicione um acompanhamento/tarefa em qualquer ticket
3. Será automaticamente replicado para todos os tickets relacionados (bidirecional)

**Múltiplas seleções** ✨ **NOVO (v1.3.0)**:
- Marque vários modos e combine seus efeitos
- Exemplo: Modos 1+4 replica para projeto E relacionados
- Duplicatas são removidas automaticamente

## Documentação

- [Guia de Instalação Completo](INSTALLATION_GUIDE.md)
- [Documentação Técnica - Replicação de Followups](FOLLOWUP_REPLICATION.md)
- [Documentação Técnica - Replicação de Tasks](TASK_REPLICATION.md)
- [Múltiplas Seleções e Modo Relacionados](MULTIPLE_MODES.md) ✨ **NOVO (v1.3.0)**
- [Changelog](CHANGELOG.md)

## Requisitos

- GLPI 10.0.0 ou superior
- PHP 7.4 ou superior

## Contributing

* Open a ticket for each bug/feature so it can be discussed
* Follow [development guidelines](http://glpi-developer-documentation.readthedocs.io/en/latest/plugins/index.html)
* Refer to [GitFlow](http://git-flow.readthedocs.io/) process for branching
* Work on a new branch on your own fork
* Open a PR that will be reviewed by a developer
