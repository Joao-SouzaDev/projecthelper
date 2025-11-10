<?php

namespace GlpiPlugin\Projecthelper;

use Glpi\Toolbox\VersionReader;
use Glpi\Application\Utils\Migration;

class Install
{
    /**
     * Instala o plugin, criando a tabela de configuração.
     *
     * @param VersionReader|null $old_version A versão antiga (pode ser null).
     *
     * @return bool
     */
    public static function install(?VersionReader $old_version = null)
    {
        global $DB;
        $config_table = 'glpi_plugin_projecthelper_configs';

        if (!$DB->tableExists($config_table)) {
            $query = "CREATE TABLE `$config_table` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                        `show_progress_bar` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1 for Yes, 0 for No',
                        `replicate_followups` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0=No, 1=Yes all, 2=Yes select',
                        `date_mod` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
                        PRIMARY KEY (`id`)
                     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $DB->query($query) or die("Error creating table $config_table");

            $DB->insert($config_table, [
                'id' => 1,
                'show_progress_bar' => 1,
                'replicate_followups' => 0
            ]);
        }

        return true;
    }

    /**
     * Desinstala o plugin, removendo a tabela de configuração.
     *
     * @param VersionReader|null $old_version A versão antiga (pode ser null).
     *
     * @return bool
     */
    public static function uninstall(?VersionReader $old_version = null)
    {
        global $DB;
        $config_table = 'glpi_plugin_projecthelper_configs';

        if ($DB->tableExists($config_table)) {
            $DB->query("DROP TABLE `$config_table`");
        }

        return true;
    }

    /**
     * Migra a estrutura do banco de dados ao atualizar o plugin.
     *
     * @param VersionReader $old_version
     */
    public static function update(VersionReader $old_version)
    {
        global $DB;
        $config_table = 'glpi_plugin_projecthelper_configs';

        if (version_compare($old_version->getVersion(), '1.0.1', '<')) {
            $migration = new Migration(101); // 1.0.1

            // Verifica se a coluna é do tipo VARCHAR
            $field_spec = $DB->fieldspec($config_table, 'replicate_followups');
            if (strpos(strtolower($field_spec['Type']), 'varchar') !== false) {
                // Converte os valores existentes de string para inteiro
                $migration->add(
                    "-- update replicate_followups from string to int",
                    "UPDATE `$config_table`
                     SET `replicate_followups` = CASE `replicate_followups`
                        WHEN 'all' THEN '1'
                        WHEN 'select' THEN '2'
                        ELSE '0'
                     END;"
                );
                // Altera o tipo da coluna para TINYINT
                $migration->add(
                    "-- alter replicate_followups column type",
                    "ALTER TABLE `$config_table`
                     CHANGE `replicate_followups` `replicate_followups` TINYINT(1) NOT NULL DEFAULT '0'
                     COMMENT '0=No, 1=Yes all, 2=Yes select';"
                );
            }
            $migration->executeMigration();
        }
    }
}