<?php

// Centraliza a definição de versão e hooks no hook.php
include_once __DIR__ . '/hook.php';

use GlpiPlugin\Projecthelper\Install;

/**
 * Função de instalação legada para garantir compatibilidade.
 */
function plugin_projecthelper_install()
{
    // O autoloader do plugin é carregado via hook.php, tornando a classe Install disponível.
    if (class_exists(Install::class)) {
        return Install::install(); // <--- Alterado aqui (sem parâmetros)
    }

    trigger_error("ProjectHelper Install class not found.", E_USER_ERROR);
    return false;
}

/**
 * Função de desinstalação legada para garantir compatibilidade.
 */
function plugin_projecthelper_uninstall()
{
    if (class_exists(Install::class)) {
        return Install::uninstall(); // <--- Alterado aqui (sem parâmetros)
    }

    trigger_error("ProjectHelper Install class not found.", E_USER_ERROR);
    return false;
}

/**
 * Função de atualização do plugin.
 * Chamada quando a versão do plugin muda.
 */
function plugin_projecthelper_update()
{
    global $DB;

    if (class_exists(Install::class)) {
        // Busca a versão atual instalada
        $plugin = new Plugin();
        if ($plugin->getFromDBbyDir('projecthelper')) {
            $current_version = $plugin->fields['version'];

            // Cria um objeto VersionReader com a versão atual
            $version_reader = new \Glpi\Toolbox\VersionReader($current_version);

            // Chama a função de update
            Install::update($version_reader);

            return true;
        }
    }

    trigger_error("ProjectHelper Install class not found.", E_USER_ERROR);
    return false;
}