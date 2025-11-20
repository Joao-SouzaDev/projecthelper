# ProjectHelper GLPI plugin

Plugin para GLPI que adiciona funcionalidades auxiliares para gerenciamento de projetos.

## Funcionalidades

### 🔄 Replicação Automática de Acompanhamentos
- Replica automaticamente acompanhamentos (followups) entre tickets do mesmo projeto
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

### Replicação de Acompanhamentos
1. Acesse **Configuração > Plugins > Project Helper**
2. Configure "Replicate follow-ups from linked tickets to the Project":
   - **No**: Desabilitado
   - **Yes, for all**: Replica para todos os tickets do mesmo projeto
   - **Yes, select per ticket**: (Reservado para implementação futura)

## Uso

Após configurar a replicação de acompanhamentos:
1. Vincule múltiplos tickets a um mesmo projeto
2. Adicione um acompanhamento em qualquer um dos tickets
3. O acompanhamento será automaticamente replicado para todos os outros tickets do projeto

## Documentação

- [Guia de Instalação Completo](INSTALLATION_GUIDE.md)
- [Documentação Técnica - Replicação de Followups](FOLLOWUP_REPLICATION.md)

## Requisitos

- GLPI 10.0.0 ou superior
- PHP 7.4 ou superior

## Contributing

* Open a ticket for each bug/feature so it can be discussed
* Follow [development guidelines](http://glpi-developer-documentation.readthedocs.io/en/latest/plugins/index.html)
* Refer to [GitFlow](http://git-flow.readthedocs.io/) process for branching
* Work on a new branch on your own fork
* Open a PR that will be reviewed by a developer
