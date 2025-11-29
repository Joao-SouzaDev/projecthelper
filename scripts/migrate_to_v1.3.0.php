<?php
/**
 * Script de migração manual para v1.3.0
 * 
 * Execute este script via CLI para atualizar o banco de dados:
 * php scripts/migrate_to_v1.3.0.php
 * 
 * Ou acesse via navegador:
 * http://seu-glpi/plugins/projecthelper/scripts/migrate_to_v1.3.0.php
 */

// Carrega o GLPI
define('GLPI_ROOT', dirname(dirname(dirname(dirname(__FILE__)))));
include(GLPI_ROOT . "/inc/includes.php");

// Verifica permissões
Session::checkRight("config", UPDATE);

echo "<h1>ProjectHelper - Migração para v1.3.0</h1>";
echo "<pre>";

global $DB;

$config_table = 'glpi_plugin_projecthelper_configs';

try {
    echo "Verificando estrutura da tabela...\n";

    // Verifica se a tabela existe
    if (!$DB->tableExists($config_table)) {
        echo "❌ ERRO: Tabela $config_table não existe!\n";
        echo "Execute a instalação do plugin primeiro.\n";
        exit(1);
    }

    echo "✓ Tabela encontrada\n\n";

    // Verifica tipo do campo replicate_followups
    $field_spec = $DB->fieldspec($config_table, 'replicate_followups');
    echo "Campo replicate_followups:\n";
    echo "  Tipo atual: " . $field_spec['Type'] . "\n";

    if (strpos(strtolower($field_spec['Type']), 'tinyint') !== false) {
        echo "  Status: ⚠️ PRECISA MIGRAÇÃO (TINYINT → VARCHAR)\n\n";

        echo "Executando migração do campo replicate_followups...\n";
        $query = "ALTER TABLE `$config_table`
                  CHANGE `replicate_followups` `replicate_followups` VARCHAR(50) NOT NULL DEFAULT '0'
                  COMMENT 'Comma-separated: 0=No, 1=All project tickets, 2=Parent to children, 3=Child to parent, 4=Related tickets'";

        if ($DB->query($query)) {
            echo "✓ Campo replicate_followups migrado com sucesso!\n\n";
        } else {
            echo "❌ ERRO ao migrar replicate_followups: " . $DB->error() . "\n\n";
        }
    } else {
        echo "  Status: ✓ JÁ ESTÁ EM VARCHAR\n\n";
    }

    // Verifica tipo do campo replicate_tasks
    $field_spec = $DB->fieldspec($config_table, 'replicate_tasks');
    echo "Campo replicate_tasks:\n";
    echo "  Tipo atual: " . $field_spec['Type'] . "\n";

    if (strpos(strtolower($field_spec['Type']), 'tinyint') !== false) {
        echo "  Status: ⚠️ PRECISA MIGRAÇÃO (TINYINT → VARCHAR)\n\n";

        echo "Executando migração do campo replicate_tasks...\n";
        $query = "ALTER TABLE `$config_table`
                  CHANGE `replicate_tasks` `replicate_tasks` VARCHAR(50) NOT NULL DEFAULT '0'
                  COMMENT 'Comma-separated: 0=No, 1=All project tickets, 2=Parent to children, 3=Child to parent, 4=Related tickets'";

        if ($DB->query($query)) {
            echo "✓ Campo replicate_tasks migrado com sucesso!\n\n";
        } else {
            echo "❌ ERRO ao migrar replicate_tasks: " . $DB->error() . "\n\n";
        }
    } else {
        echo "  Status: ✓ JÁ ESTÁ EM VARCHAR\n\n";
    }

    // Mostra estrutura final
    echo "=== ESTRUTURA FINAL ===\n";
    $query = "SHOW FULL COLUMNS FROM `$config_table`";
    $result = $DB->query($query);

    if ($result) {
        while ($row = $DB->fetchAssoc($result)) {
            if (in_array($row['Field'], ['replicate_followups', 'replicate_tasks'])) {
                echo "\n" . $row['Field'] . ":\n";
                echo "  Tipo: " . $row['Type'] . "\n";
                echo "  Default: " . $row['Default'] . "\n";
                echo "  Comentário: " . $row['Comment'] . "\n";
            }
        }
    }

    echo "\n=== VALORES ATUAIS ===\n";
    $query = "SELECT * FROM `$config_table` WHERE id = 1";
    $result = $DB->query($query);

    if ($result && $row = $DB->fetchAssoc($result)) {
        echo "replicate_followups: " . $row['replicate_followups'] . "\n";
        echo "replicate_tasks: " . $row['replicate_tasks'] . "\n";
    }

    echo "\n✅ MIGRAÇÃO CONCLUÍDA COM SUCESSO!\n";
    echo "\nAgora você pode usar múltiplas seleções na configuração do plugin.\n";

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre>";
