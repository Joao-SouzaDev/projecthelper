# Múltiplas Seleções e Modo Relacionados

**Versão**: 1.3.0+

## 📋 Visão Geral

A partir da versão 1.3.0, o plugin ProjectHelper permite:
1. **Múltiplas seleções**: Combinar diferentes modos de replicação simultaneamente
2. **Modo 4 (Novo)**: Replicar para tickets relacionados (link = 2 no GLPI)

---

## 🎯 Modos Disponíveis

| Modo | Descrição | Tabela Usada | Condição |
|------|-----------|--------------|----------|
| **0** | Não replicar | - | - |
| **1** | Todos os tickets do projeto | `glpi_itils_projects` | Tickets no mesmo projeto |
| **2** | Pai → Filhos | `glpi_tickets_tickets` | `link = 3`, pai para filhos |
| **3** | Filho → Pai | `glpi_tickets_tickets` | `link = 3`, filho para pai |
| **4** | Tickets relacionados ✨ NOVO | `glpi_tickets_tickets` | `link = 2`, bidirecional |

---

## 🔗 Modo 4 - Tickets Relacionados

### O que é?

O modo 4 replica followups/tasks para tickets que têm relacionamento do tipo "Relacionado a" (Related to) no GLPI.

### Como funciona?

No GLPI, quando você cria um relacionamento entre tickets usando "Relacionado a":

```
Ticket #100 ←→ Relacionado a ←→ Ticket #200
```

Isso cria um registro na tabela `glpi_tickets_tickets` com `link = 2`.

### Diferença dos outros modos

- **Modo 2/3 (Pai/Filho)**: Hierarquia definida (um é pai, outro é filho)
  - `link = 3` na tabela
  - Unidirecional (pai→filho OU filho→pai)
  
- **Modo 4 (Relacionado)**: Sem hierarquia, apenas relacionamento
  - `link = 2` na tabela  
  - **Bidirecional**: Se #100 adiciona followup, replica para #200 E vice-versa

### Query SQL utilizada

```sql
SELECT DISTINCT 
    CASE 
        WHEN tickets_id_1 = <ticket_atual> THEN tickets_id_2
        WHEN tickets_id_2 = <ticket_atual> THEN tickets_id_1
    END as related_id
FROM glpi_tickets_tickets
WHERE (tickets_id_1 = <ticket_atual> OR tickets_id_2 = <ticket_atual>)
AND link = 2
```

### Exemplo prático

**Cenário**: Bug #100 está relacionado a Feature #200

1. Técnico adiciona followup em Bug #100:
   ```
   "Corrigido bug na função de login"
   ```

2. Com modo 4 ativado, o followup é replicado automaticamente para Feature #200

3. Equipe trabalhando na Feature #200 fica informada sobre a correção do bug relacionado

---

## 🎛️ Múltiplas Seleções

### Como usar?

Na tela de configuração (`Configuração > Plugins > Project Helper`), agora você vê **checkboxes** em vez de dropdown:

```
☐ No
☑ Yes, replicate to all project tickets
☐ Yes, replicate from parent to children
☐ Yes, replicate from child to parent
☑ Yes, replicate to related tickets
```

Você pode marcar **quantas opções quiser** (exceto "No" com outras).

### Comportamento

#### Exemplo 1: Modos 1 + 4

**Configuração**:
- ☑ Modo 1 (Todos do projeto)
- ☑ Modo 4 (Relacionados)

**Resultado**: 
Quando adicionar followup em Ticket #100:
1. Replica para todos tickets do mesmo projeto (via `glpi_itils_projects`)
2. Replica para todos tickets relacionados (via `glpi_tickets_tickets` com `link=2`)
3. Remove duplicatas automaticamente

#### Exemplo 2: Modos 2 + 3 + 4

**Configuração**:
- ☑ Modo 2 (Pai → Filhos)
- ☑ Modo 3 (Filho → Pai)
- ☑ Modo 4 (Relacionados)

**Resultado**:
Máxima cobertura! Replica para:
- Todos os filhos (se for pai)
- O pai (se for filho)
- Todos os relacionados

---

## 💾 Armazenamento

### Banco de Dados

Os campos `replicate_followups` e `replicate_tasks` agora são **VARCHAR(50)** e armazenam múltiplas seleções como string separada por vírgulas:

```sql
-- Exemplo de valores:
'0'          -- Desabilitado
'1'          -- Apenas modo 1
'1,4'        -- Modos 1 e 4
'2,3,4'      -- Modos 2, 3 e 4
'1,2,3,4'    -- Todos os modos
```

### Processamento

No código PHP:

```php
// String do banco: "1,2,4"
$replication_modes_string = $config->fields['replicate_followups'];

// Converte para array: [1, 2, 4]
$replication_modes = array_map('intval', explode(',', $replication_modes_string));

// Loop processa cada modo
foreach ($replication_modes as $mode) {
    if ($mode == 1) { /* busca tickets do projeto */ }
    if ($mode == 2) { /* busca filhos */ }
    if ($mode == 4) { /* busca relacionados */ }
}

// Remove duplicatas no final
$related_tickets = array_unique($related_tickets);
```

---

## 🔄 Migration (Atualização)

Se você já tinha o plugin instalado em versão anterior:

### Migração Automática v1.3.0

A migration detecta campos TINYINT e converte automaticamente para VARCHAR:

```php
if (version_compare($old_version->getVersion(), '1.3.0', '<')) {
    // Converte replicate_followups: TINYINT → VARCHAR(50)
    ALTER TABLE glpi_plugin_projecthelper_configs
    CHANGE replicate_followups replicate_followups VARCHAR(50) 
    NOT NULL DEFAULT '0'
    COMMENT 'Comma-separated: 0=No, 1=All project, 2=Parent to children, 3=Child to parent, 4=Related';
    
    // Converte replicate_tasks: TINYINT → VARCHAR(50)
    ALTER TABLE glpi_plugin_projecthelper_configs
    CHANGE replicate_tasks replicate_tasks VARCHAR(50)
    NOT NULL DEFAULT '0'
    COMMENT 'Comma-separated: 0=No, 1=All project, 2=Parent to children, 3=Child to parent, 4=Related';
}
```

### Valores Preservados

- Se tinha valor `1` (TINYINT) → vira `"1"` (VARCHAR) ✅
- Se tinha valor `0` → vira `"0"` ✅
- Compatibilidade total com versões anteriores

---

## ⚠️ Importante

### Evitar Duplicatas

O plugin automaticamente remove duplicatas ao combinar múltiplos modos:

```php
// Ticket #100 está no Projeto A e é relacionado ao #200
// Ticket #200 também está no Projeto A

// Com modos 1 + 4:
// Modo 1 retorna: [#200]
// Modo 4 retorna: [#200]
// 
// array_unique() garante: [#200] (apenas uma vez)
```

### Recursão

A flag `$is_replicating` continua protegendo contra loops infinitos mesmo com múltiplos modos.

### Performance

Quanto mais modos ativos, mais queries SQL são executadas. Recomendação:
- Use apenas os modos necessários para seu fluxo de trabalho
- Modo 1 (projeto) pode ser mais "pesado" em projetos com muitos tickets

---

## 📊 Casos de Uso

### Caso 1: Projeto + Relacionados

**Cenário**: Projeto de migração com bugs relacionados

**Configuração**: Modos 1 + 4

**Benefício**: 
- Toda equipe do projeto vê atualizações (modo 1)
- Bugs técnicos relacionados fora do projeto também recebem updates (modo 4)

---

### Caso 2: Hierarquia Completa

**Cenário**: Ticket pai com vários filhos, alguns com relacionamentos externos

**Configuração**: Modos 2 + 3 + 4

**Benefício**:
- Pai sabe tudo que acontece nos filhos (modo 3)
- Filhos veem atualizações do pai (modo 2)
- Tickets relacionados também ficam informados (modo 4)

---

### Caso 3: Apenas Relacionados

**Cenário**: Tickets avulsos sem projeto, apenas com relacionamentos

**Configuração**: Modo 4

**Benefício**: 
- Sem overhead de buscar projetos
- Foco apenas em relacionamentos diretos

---

## 🧪 Testando

### Teste Modo 4

1. Crie dois tickets: #AAA e #BBB
2. Em #AAA, vá em "Tickets" → "Adicionar relacionamento"
3. Selecione #BBB e tipo "Relacionado a"
4. Ative modo 4 no plugin
5. Adicione um followup em #AAA
6. Verifique se aparece em #BBB

### Teste Múltiplas Seleções

1. Configure modos 1 + 4
2. Crie Projeto X com Ticket #100 e #200
3. Crie Ticket #300 relacionado ao #100 (mas fora do projeto)
4. Adicione followup no #100
5. Deve replicar para:
   - #200 (modo 1 - mesmo projeto)
   - #300 (modo 4 - relacionado)

---

## 📚 Referências

- [FOLLOWUP_REPLICATION.md](FOLLOWUP_REPLICATION.md) - Documentação de replicação de followups
- [TASK_REPLICATION.md](TASK_REPLICATION.md) - Documentação de replicação de tasks
- [CHANGELOG.md](CHANGELOG.md) - Histórico de versões
