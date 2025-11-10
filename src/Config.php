<?php

namespace GlpiPlugin\Projecthelper;

use CommonDBTM;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class Config extends CommonDBTM
{

    static function getTable($classname = null)
    {
        // Define o nome da tabela sem o 'glpi_'
        return "glpi_plugin_projecthelper_configs";
    }

    static $rightname = 'config';

    static function getTypeName($nb = 0)
    {
        return __('Configuration', 'projecthelper');
    }
}