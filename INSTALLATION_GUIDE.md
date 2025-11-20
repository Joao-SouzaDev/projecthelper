# Guia de Implementação - Replicação de Acompanhamentos

## ✅ Implementação Concluída

A funcionalidade de replicação automática de acompanhamentos (followups) entre tickets do mesmo projeto foi implementada com sucesso no plugin ProjectHelper.

## 📋 Arquivos Criados/Modificados

### Novos Arquivos:
1. **`src/FollowupHandler.php`** - Classe principal que gerencia a replicação
2. **`FOLLOWUP_REPLICATION.md`** - Documentação detalhada da funcionalidade
3. **`tests/test_followup_replication.php`** - Script de testes

### Arquivos Modificados:
1. **`hook.php`** - Adicionado hook `item_add` para ITILFollowup
2. **`src/Config.php`** - Ajustes para compatibilidade com CommonDBTM

### Arquivos Existentes (Não Modificados):
- **`src/Install.php`** - Já continha o campo `replicate_followups` na tabela
- **`front/config.form.php`** - Já continha a interface de configuração

## 🚀 Como Usar

### 1. Ativação da Funcionalidade

1. Acesse o GLPI como administrador
2. Navegue para: **Configuração > Plugins > Project Helper**
3. Na opção "Replicate follow-ups from linked tickets to the Project", selecione:
   - **No**: Desabilita a replicação
   - **Yes, for all**: Habilita a replicação automática para todos os tickets do mesmo projeto ✅
   - **Yes, select per ticket**: Reservado para implementação futura

### 2. Funcionamento Automático

Após ativar a configuração (opção "Yes, for all"):

1. **Vincule tickets a um projeto**:
   - Crie ou acesse um Projeto
   - Crie uma Task no Projeto
   - Vincule múltiplos tickets à mesma Task

2. **Adicione um acompanhamento**:
   - Abra qualquer um dos tickets vinculados
   - Adicione um novo acompanhamento (followup)
   - O acompanhamento será **automaticamente replicado** para todos os outros tickets vinculados ao mesmo projeto

### 3. Exemplo Prático

**Cenário**: Projeto "Atualização de Sistema"
- Ticket #100: Backup dos dados
- Ticket #101: Instalação da atualização
- Ticket #102: Testes pós-atualização

**Ação**: Técnico adiciona no Ticket #100:
```
"Backup concluído com sucesso. 
Total: 500GB
Tempo: 2 horas
Localização: /backup/sistema_20250120"
```

**Resultado**: O mesmo acompanhamento aparece automaticamente nos Tickets #101 e #102

## 🔧 Configuração do Servidor

### Requisitos:
- GLPI 10.0.0 ou superior
- Plugin ProjectHelper instalado e ativado
- PHP 7.4 ou superior

### Instalação/Atualização:

Se você já tem o plugin instalado, basta atualizar os arquivos:

```bash
cd /var/www/html/glpi/plugins/projecthelper
git pull origin main
# ou copie os novos arquivos manualmente
```

Não é necessário reinstalar o plugin, pois a tabela já contém o campo necessário.

## 🧪 Testes

### Teste Manual:

1. Configure `replicate_followups = 1` (Yes, for all)
2. Crie um projeto de teste
3. Vincule 2 ou mais tickets ao projeto
4. Adicione um followup em um dos tickets
5. Verifique se o followup aparece automaticamente nos outros tickets

### Teste com Script:

```bash
cd /var/www/html/glpi/plugins/projecthelper/tests
php test_followup_replication.php
```

Ou edite o arquivo e descomente a última linha substituindo 123 pelo ID de um ticket real:
```php
run_all_tests(123);
```

## 🔍 Diagnóstico de Problemas

### A replicação não está funcionando:

1. **Verifique a configuração**:
   ```sql
   SELECT * FROM glpi_plugin_projecthelper_configs;
   ```
   O campo `replicate_followups` deve estar com valor `1`

2. **Verifique se os tickets estão vinculados ao projeto**:
   ```sql
   SELECT t.id, t.name, p.name as project_name
   FROM glpi_tickets t
   INNER JOIN glpi_itils_projects ip ON ip.items_id = t.id AND ip.itemtype = 'Ticket'
   INNER JOIN glpi_projects p ON p.id = ip.projects_id
   WHERE t.id IN (ID1, ID2, ID3);
   ```

3. **Verifique o log do GLPI**:
   - Arquivo: `/var/log/glpi/php-errors.log` ou similar
   - Procure por erros relacionados a "ProjectHelper" ou "FollowupHandler"

4. **Verifique se o plugin está ativo**:
   ```sql
   SELECT * FROM glpi_plugins WHERE directory = 'projecthelper';
   ```
   O campo `state` deve ser `1` (ativo)

## 🛡️ Segurança e Performance

### Proteções Implementadas:

- ✅ **Anti-recursão**: Flag `$is_replicating` previne loops infinitos
- ✅ **Verificação de configuração**: Só replica se explicitamente habilitado
- ✅ **Verificação de tipo**: Só processa followups de tickets
- ✅ **Validação de projeto**: Só replica entre tickets do mesmo projeto

### Performance:

- Utiliza queries otimizadas com JOINs
- Processa apenas tickets vinculados ao mesmo projeto
- Não impacta tickets sem vínculo com projetos

## 📝 Notas Importantes

1. **Privacidade**: Acompanhamentos privados são replicados mantendo a flag de privacidade
2. **Autoria**: O autor original é mantido nos acompanhamentos replicados
3. **Data/Hora**: A data/hora original é preservada
4. **Histórico**: Cada ticket terá seu próprio registro no histórico

## 🔮 Próximas Melhorias (Roadmap)

- [ ] Implementar opção "Yes, select per ticket" (valor 2)
- [ ] Interface na tela do ticket para escolher replicação sob demanda
- [ ] Log de auditoria das replicações
- [ ] Filtros avançados (por status, categoria, etc.)
- [ ] Notificações configuráveis
- [ ] Painel de estatísticas de replicações

## 📞 Suporte

Em caso de dúvidas ou problemas:
1. Verifique a documentação em `FOLLOWUP_REPLICATION.md`
2. Execute o script de testes para diagnóstico
3. Abra uma issue no GitHub: https://github.com/Joao-SouzaDev/projecthelper

---

**Versão**: 1.0.1  
**Data**: 20/11/2025  
**Autor**: Joao-SouzaDev
