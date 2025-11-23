<?php

use GlpiPlugin\Projecthelper\Install;

/**
 * Define a versão do plugin e a compatibilidade com o GLPI
 */
function plugin_version_projecthelper()
{
    return [
        'name' => 'Project Helper',
        'version' => '1.2.0',
        'author' => 'Joao-SouzaDev',
        'license' => 'AGPLv3+',
        'homepage' => 'https://github.com/Joao-SouzaDev/projecthelper',
        'minGlpiVersion' => '10.0.0'
    ];
}

/**
 * Função opcional que verifica se os pré-requisitos para a ativação do plugin são atendidos.
 * Retorna true se os pré-requisitos forem atendidos, senão uma string com a mensagem de erro.
 */
function plugin_projecthelper_check_prerequisites()
{
    return true;
}

/**
 * Função de inicialização do plugin. Registra todos os hooks necessários.
 */
function plugin_init_projecthelper()
{
    global $PLUGIN_HOOKS;

    // Registra o autoloader para o namespace do plugin
    $PLUGIN_HOOKS['autoloader']['projecthelper'] = [
        'GlpiPlugin\\Projecthelper' => 'src',
    ];

    // Adiciona a página de configuração ao menu
    $PLUGIN_HOOKS['config_page']['projecthelper'] = 'front/config.form.php';

    // Conformidade com CSRF
    $PLUGIN_HOOKS['csrf_compliant']['projecthelper'] = true;

    // Hook que é executado ANTES da instalação padrão do GLPI.
    $PLUGIN_HOOKS['pre_install']['projecthelper'] = [Install::class, 'install'];

    // Hook para desinstalação.
    $PLUGIN_HOOKS['uninstall']['projecthelper'] = [Install::class, 'uninstall'];
    $PLUGIN_HOOKS['update']['projecthelper'] = [Install::class, 'update'];

    // Hook para replicar followups quando adicionados a um ticket
    $PLUGIN_HOOKS['item_add']['projecthelper'] = [
        'ITILFollowup' => [\GlpiPlugin\Projecthelper\FollowupHandler::class, 'afterAddFollowup'],
        'TicketTask' => [\GlpiPlugin\Projecthelper\TaskHandler::class, 'afterAddTask']
    ];

}