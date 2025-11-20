<?php
/**
 * Script de teste para verificar a funcionalidade de replicação de followups
 * 
 * Execute este script via linha de comando ou crie uma página de teste no GLPI
 * 
 * IMPORTANTE: Este é apenas um exemplo para teste. Em produção, a funcionalidade
 * é acionada automaticamente através dos hooks do GLPI.
 */

// Carrega o GLPI para execução via CLI
// Tenta encontrar o GLPI em locais comuns
$glpi_paths = [
    __DIR__ . '/../../../inc/includes.php',  // Quando dentro do diretório plugins do GLPI
    '/var/www/html/glpi/inc/includes.php',   // Instalação padrão Linux
    '/usr/share/glpi/inc/includes.php',      // Instalação alternativa Linux
];

$glpi_loaded = false;
foreach ($glpi_paths as $path) {
    if (file_exists($path)) {
        include($path);
        $glpi_loaded = true;
        break;
    }
}

if (!$glpi_loaded) {
    die("ERRO: Não foi possível encontrar o GLPI. Verifique os caminhos em test_followup_replication.php\n");
}

use GlpiPlugin\Projecthelper\FollowupHandler;
use GlpiPlugin\Projecthelper\Config;

/**
 * Teste 1: Verificar se a configuração está acessível
 */
function test_configuration()
{
    echo "=== Teste 1: Verificação de Configuração ===\n";

    $config = new Config();
    if ($config->getFromDB(1)) {
        echo "✓ Configuração encontrada\n";
        echo "  - show_progress_bar: " . $config->fields['show_progress_bar'] . "\n";
        echo "  - replicate_followups: " . $config->fields['replicate_followups'] . "\n";

        if ($config->fields['replicate_followups'] == 1) {
            echo "✓ Replicação de followups está ATIVADA (Yes, for all)\n";
        } else {
            echo "✗ Replicação de followups está DESATIVADA\n";
        }
        return true;
    } else {
        echo "✗ Erro ao carregar configuração\n";
        return false;
    }
}

/**
 * Teste 2: Verificar estrutura do banco de dados
 */
function test_database_structure()
{
    global $DB;

    echo "\n=== Teste 2: Estrutura do Banco de Dados ===\n";

    $tables = [
        'glpi_plugin_projecthelper_configs',
        'glpi_itils_projects',
        'glpi_projects',
        'glpi_itilfollowups'
    ];

    foreach ($tables as $table) {
        if ($DB->tableExists($table)) {
            echo "✓ Tabela $table existe\n";
        } else {
            echo "✗ Tabela $table NÃO existe\n";
        }
    }

    return true;
}

/**
 * Teste 3: Simular busca de projeto a partir de um ticket
 */
function test_find_project($ticket_id)
{
    global $DB;

    echo "\n=== Teste 3: Buscar Projeto do Ticket #$ticket_id ===\n";

    $query = "SELECT ptt.projects_id, pt.name as task_name, p.name as project_name
              FROM glpi_itils_projects AS ptt
              INNER JOIN glpi_projects AS p ON p.id = ptt.projects_id
              INNER JOIN glpi_tickets AS pt ON pt.id = ptt.items_id
              WHERE ptt.items_id = " . (int) $ticket_id . " AND ptt.itemtype = 'Ticket'";

    $result = $DB->query($query);

    if ($result && $DB->numrows($result) > 0) {
        $row = $DB->fetchAssoc($result);
        echo "✓ Ticket vinculado ao projeto\n";
        echo "  - Project ID: " . $row['projects_id'] . "\n";
        echo "  - Project Name: " . $row['project_name'] . "\n";
        echo "  - Task Name: " . $row['task_name'] . "\n";
        return $row['projects_id'];
    } else {
        echo "✗ Ticket NÃO está vinculado a nenhum projeto\n";
        return false;
    }
}

/**
 * Teste 4: Buscar tickets relacionados ao mesmo projeto
 */
function test_find_related_tickets($project_id, $exclude_ticket_id)
{
    global $DB;

    echo "\n=== Teste 4: Buscar Tickets do Projeto #$project_id ===\n";

    $query = "SELECT DISTINCT ptt.items_id as tickets_id, t.name as ticket_name, t.status
              FROM glpi_itils_projects AS ptt
              INNER JOIN glpi_projects AS pt ON pt.id = ptt.projects_id
              LEFT JOIN glpi_tickets AS t ON t.id = ptt.items_id
              WHERE pt.id = " . (int) $project_id . "
              AND ptt.items_id != " . (int) $exclude_ticket_id;
    $result = $DB->query($query);

    if ($result) {
        $count = $DB->numrows($result);
        echo "✓ Encontrados $count tickets relacionados\n";

        while ($row = $DB->fetchAssoc($result)) {
            echo "  - Ticket #" . $row['tickets_id'] . ": " . $row['ticket_name'] . " (Status: " . $row['status'] . ")\n";
        }

        return $count;
    }

    return 0;
}

/**
 * Teste 5: Listar followups de um ticket
 */
function test_list_followups($ticket_id)
{
    global $DB;

    echo "\n=== Teste 5: Followups do Ticket #$ticket_id ===\n";

    $query = "SELECT f.id, f.date, f.content, f.is_private, u.name as user_name
              FROM glpi_itilfollowups AS f
              LEFT JOIN glpi_users AS u ON u.id = f.users_id
              WHERE f.itemtype = 'Ticket'
              AND f.items_id = " . (int) $ticket_id . "
              ORDER BY f.date DESC
              LIMIT 5";

    $result = $DB->query($query);

    if ($result) {
        $count = $DB->numrows($result);
        echo "✓ Encontrados $count followups\n";

        while ($row = $DB->fetchAssoc($result)) {
            $private = $row['is_private'] ? '[PRIVADO]' : '[PÚBLICO]';
            echo "  - Followup #" . $row['id'] . " $private\n";
            echo "    Data: " . $row['date'] . "\n";
            echo "    Usuário: " . $row['user_name'] . "\n";
            echo "    Conteúdo: " . substr(strip_tags($row['content']), 0, 100) . "...\n\n";
        }

        return $count;
    }

    return 0;
}

/**
 * Executa todos os testes
 */
function run_all_tests($test_ticket_id = null)
{
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  ProjectHelper - Teste de Replicação de Followups         ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";

    // Teste 1: Configuração
    test_configuration();

    // Teste 2: Banco de dados
    test_database_structure();

    // Se foi fornecido um ticket ID para teste
    if ($test_ticket_id) {
        // Teste 3: Buscar projeto
        $project_id = test_find_project($test_ticket_id);

        if ($project_id) {
            // Teste 4: Buscar tickets relacionados
            test_find_related_tickets($project_id, $test_ticket_id);
        }

        // Teste 5: Listar followups
        test_list_followups($test_ticket_id);
    } else {
        echo "\n⚠ Para testar com um ticket específico, chame: run_all_tests(TICKET_ID)\n";
    }

    echo "\n╔════════════════════════════════════════════════════════════╗\n";
    echo "║  Testes Concluídos                                         ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
}

// Executa os testes
// Para testar com um ticket específico, passe o ID como argumento: php test_followup_replication.php 123
$ticket_id = isset($argv[1]) ? (int) $argv[1] : null;
run_all_tests($ticket_id);
