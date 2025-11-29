# Guia de Atualização para v1.3.0

## 🆕 Novidades da v1.3.0

- ✨ **Múltiplas seleções**: Combine vários modos de replicação simultaneamente
- ✨ **Modo 4 - Tickets Relacionados**: Replica para tickets com relacionamento "Relacionado a"
- 🎨 **Interface melhorada**: Checkboxes estilizados com cores para cada modo

---

## 📦 Como Atualizar

### Opção 1: Atualização Automática (Recomendado)

1. Desinstale a versão antiga do plugin no GLPI
2. Instale a nova versão v1.3.0
3. A migration rodará automaticamente

⚠️ **Atenção**: Suas configurações serão preservadas!

### Opção 2: Atualização Manual (Se a automática falhar)

1. Faça backup do banco de dados primeiro!

2. Execute o script de migração via navegador:
   ```
   http://seu-glpi/plugins/projecthelper/scripts/migrate_to_v1.3.0.php
   ```

3. Ou execute via CLI:
   ```bash
   cd /var/www/html/glpi/plugins/projecthelper
   php scripts/migrate_to_v1.3.0.php
   ```

### Opção 3: SQL Manual

Execute as queries SQL diretamente no banco:

```sql
-- Converte campo replicate_followups
ALTER TABLE glpi_plugin_projecthelper_configs
CHANGE replicate_followups replicate_followups VARCHAR(50) NOT NULL DEFAULT '0'
COMMENT 'Comma-separated: 0=No, 1=All project tickets, 2=Parent to children, 3=Child to parent, 4=Related tickets';

-- Converte campo replicate_tasks  
ALTER TABLE glpi_plugin_projecthelper_configs
CHANGE replicate_tasks replicate_tasks VARCHAR(50) NOT NULL DEFAULT '0'
COMMENT 'Comma-separated: 0=No, 1=All project tickets, 2=Parent to children, 3=Child to parent, 4=Related tickets';
```

---

## ✅ Verificação

Após a atualização, verifique:

1. Acesse **Configuração > Plugins > Project Helper**
2. Você deve ver checkboxes estilizados com cores:
   - 🔴 No (vermelho)
   - 🔵 All project tickets (azul)
   - 🟢 Parent to children (verde)
   - 🟡 Child to parent (amarelo)
   - 🔷 Related tickets (ciano) ✨ NOVO

3. Marque múltiplas opções e salve
4. Recarregue a página e verifique se as seleções foram mantidas

---

## 🐛 Resolução de Problemas

### Problema: "Migration não foi executada"

**Sintoma**: Ainda vejo dropdown em vez de checkboxes

**Solução**:
1. Execute o script manual: `scripts/migrate_to_v1.3.0.php`
2. Ou execute as queries SQL diretamente

### Problema: "Erro ao salvar configuração"

**Sintoma**: Mensagem de erro ao clicar em Save

**Solução**:
1. Verifique se os campos foram migrados para VARCHAR:
   ```sql
   DESCRIBE glpi_plugin_projecthelper_configs;
   ```
2. Você deve ver:
   - `replicate_followups` → varchar(50)
   - `replicate_tasks` → varchar(50)

### Problema: "Checkboxes não aparecem estilizados"

**Sintoma**: Checkboxes sem cores ou formatação

**Solução**:
1. Limpe o cache do navegador (Ctrl+Shift+R)
2. Verifique se o arquivo `front/config.form.php` foi atualizado
3. O CSS deve estar inline no próprio arquivo

---

## 📊 Compatibilidade

### Valores Preservados

A migration preserva automaticamente suas configurações:

| Valor Antigo (TINYINT) | Valor Novo (VARCHAR) | Comportamento |
|-------------------------|----------------------|---------------|
| `0` | `"0"` | Sem replicação |
| `1` | `"1"` | Apenas modo 1 |
| `2` | `"2"` | Apenas modo 2 |
| `3` | `"3"` | Apenas modo 3 |

### Novos Valores Possíveis

Com múltiplas seleções:

| Configuração | Valor no Banco | Modos Ativos |
|--------------|----------------|--------------|
| Projeto + Relacionados | `"1,4"` | 1 e 4 |
| Pai↔Filho completo | `"2,3"` | 2 e 3 |
| Tudo | `"1,2,3,4"` | Todos |

---

## 🆘 Suporte

Se encontrar problemas:

1. Verifique os logs do GLPI: `files/_log/`
2. Execute o script de verificação: `scripts/migrate_to_v1.3.0.php`
3. Abra uma issue no GitHub com a saída do script

---

## 📚 Documentação Adicional

- [MULTIPLE_MODES.md](MULTIPLE_MODES.md) - Documentação completa sobre múltiplas seleções e modo 4
- [CHANGELOG.md](CHANGELOG.md) - Histórico de mudanças
- [README.md](README.md) - Documentação geral do plugin
