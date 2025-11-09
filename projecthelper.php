<?php
/**
 * Plugin ProjectHelper for GLPI
 */

define('PLUGIN_PROJECTHELPER_VERSION', '1.0.0');

function plugin_init_projecthelper()
{
    global $PLUGIN_HOOKS;
    $PLUGIN_HOOKS['csrf_compliant']['projecthelper'] = true;
}

function plugin_version_projecthelper()
{
    return [
        'name' => 'ProjectHelper',
        'version' => PLUGIN_PROJECTHELPER_VERSION,
        'author' => 'Joao-SouzaDev',
        'license' => 'GPLv2+',
        'homepage' => '',
        'minGlpiVersion' => '10.0.0'
    ];
}

function plugin_projecthelper_check_prerequisites()
{
    return true;
}

function plugin_projecthelper_check_config($verbose = false)
{
    return true;
}