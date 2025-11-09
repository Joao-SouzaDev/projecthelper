<?php
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginProjecthelperConfiguration extends CommonDBTM
{

    static function getTypeName($nb = 0)
    {
        return __('Configuration', 'projecthelper');
    }

    /**
     * @return bool
     */
    static function canView()
    {
        return Session::haveRight('config', READ);
    }

    /**
     * @return bool
     */
    static function canCreate()
    {
        return Session::haveRight('config', UPDATE);
    }

    /**
     * Mostra o formulário de configuração.
     *
     * @param $ID
     * @param $options
     * @return bool
     */
    function showForm($ID, $options = [])
    {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Ativar replicação de acompanhamentos', 'projecthelper') . "</td>";
        echo "<td>";
        Dropdown::showYesNo("enabled", $this->fields["enabled"]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Modo de Replicação', 'projecthelper') . "</td>";
        echo "<td>";
        $replication_modes = [
            'manual' => __('Manual (via opção no chamado)', 'projecthelper'),
            'auto' => __('Automático (para todos os chamados vinculados)', 'projecthelper')
        ];
        Dropdown::showFromArray("replication_mode", $replication_modes, ['value' => $this->fields["replication_mode"]]);
        echo "</td>";
        echo "</tr>";

        $this->showFormButtons($options);

        return true;
    }

    /**
     * Garante que sempre teremos um registro de configuração.
     */
    static function install()
    {
        global $DB;

        if (countElementsInTable('glpi_plugin_projecthelper_configurations') == 0) {
            $config = new self();
            $config->add([
                'enabled' => 1,
                'replication_mode' => 'manual',
            ]);
        }
    }
}