<?php
/**
 * Script para testar a replicação de followups manualmente
 * Simula a adição de um followup e verifica se o hook é chamado
 */

// Carrega o GLPI
$glpi_paths = [
    __DIR__ . '/../../../inc/includes.php',
    '/var/www/html/glpi/inc/includes.php',
];

foreach ($glpi_paths as $path) {
    if (file_exists($path)) {
        include($path);
        break;
    }
}

use GlpiPlugin\Projecthelper\FollowupHandler;

// Verifica argumentos
if (!isset($argv[1])) {
    echo "Uso: php test_add_followup.php TICKET_ID [\"Mensagem do followup\"]\n";
    echo "Exemplo: php test_add_followup.php 13 \"Teste de replicação\"\n";
    exit(1);
}

$ticket_id = (int) $argv[1];
$message = $argv[2] ?? "Followup de teste - " . date('Y-m-d H:i:s');

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Teste de Adição de Followup                              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "Ticket ID: #$ticket_id\n";
echo "Mensagem: $message\n\n";

// Verifica se o ticket existe
$ticket = new Ticket();
if (!$ticket->getFromDB($ticket_id)) {
    echo "✗ ERRO: Ticket #$ticket_id não encontrado!\n";
    exit(1);
}

echo "✓ Ticket encontrado: " . $ticket->fields['name'] . "\n\n";

// Verifica se o plugin está ativo
global $PLUGIN_HOOKS;
if (!isset($PLUGIN_HOOKS['item_add']['projecthelper']['ITILFollowup'])) {
    echo "✗ ERRO: Hook do ProjectHelper não está registrado!\n";
    echo "Verifique se o plugin está ativo.\n";
    exit(1);
}

echo "✓ Hook do ProjectHelper está registrado\n\n";

// Cria o followup
echo "Criando followup...\n";

$followup = new ITILFollowup();
$followup_data = [
    'itemtype' => 'Ticket',
    'items_id' => $ticket_id,
    'content' => $message,
    'is_private' => 0,
    'users_id' => Session::getLoginUserID()
];

$followup_id = $followup->add($followup_data);

if ($followup_id) {
    echo "✓ Followup criado com sucesso! ID: #$followup_id\n\n";

    echo "Verificando logs...\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    // Aguarda um pouco para os logs serem escritos
    sleep(1);

    // Lê os últimos logs do projecthelper
    $log_file = GLPI_LOG_DIR . '/projecthelper.log';
    if (file_exists($log_file)) {
        echo "Logs do ProjectHelper:\n";
        echo "───────────────────────────────────────────────────────────\n";
        $logs = file_get_contents($log_file);
        $lines = explode("\n", $logs);
        $recent_lines = array_slice($lines, -20);
        echo implode("\n", $recent_lines);
        echo "\n───────────────────────────────────────────────────────────\n\n";
    } else {
        echo "⚠ Arquivo de log não encontrado: $log_file\n";
        echo "Isso pode significar que o hook não foi chamado.\n\n";
    }

    // Também verifica o error_log do PHP
    echo "Últimas linhas do php-errors.log:\n";
    echo "───────────────────────────────────────────────────────────\n";
    $php_log = GLPI_LOG_DIR . '/php-errors.log';
    if (file_exists($php_log)) {
        $cmd = "tail -20 " . escapeshellarg($php_log) . " | grep -i projecthelper";
        $output = shell_exec($cmd);
        if ($output) {
            echo $output;
        } else {
            echo "(Nenhuma mensagem do ProjectHelper encontrada)\n";
        }
    }
    echo "───────────────────────────────────────────────────────────\n\n";

    // Verifica se há followups replicados
    echo "Verificando replicação...\n";
    global $DB;

    $query = "SELECT COUNT(*) as total
              FROM glpi_itilfollowups
              WHERE content = " . $DB->quote($message) . "
              AND itemtype = 'Ticket'
              AND date >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)";

    $result = $DB->query($query);
    if ($result) {
        $row = $DB->fetchAssoc($result);
        $total = $row['total'];

        if ($total > 1) {
            echo "✓ Replicação detectada! $total followups com o mesmo conteúdo criados.\n";

            // Lista os tickets
            $query2 = "SELECT items_id 
                       FROM glpi_itilfollowups
                       WHERE content = " . $DB->quote($message) . "
                       AND itemtype = 'Ticket'
                       AND date >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)";

            $result2 = $DB->query($query2);
            echo "Tickets com o followup:\n";
            while ($row2 = $DB->fetchAssoc($result2)) {
                echo "  - Ticket #" . $row2['items_id'] . "\n";
            }
        } else {
            echo "✗ Nenhuma replicação detectada. Apenas 1 followup criado.\n";
            echo "\nPossíveis causas:\n";
            echo "1. O ticket não está vinculado a um projeto\n";
            echo "2. Não há outros tickets no mesmo projeto\n";
            echo "3. O hook não está sendo executado\n";
            echo "4. A configuração replicate_followups não está como 1\n";
        }
    }

} else {
    echo "✗ ERRO ao criar followup!\n";
    exit(1);
}

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║  Teste Concluído                                           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
