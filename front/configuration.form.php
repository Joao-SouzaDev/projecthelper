<?php
include("../../../inc/includes.php");

Session::checkLoginUser();
Session::checkRight('config', UPDATE);

// Cria um objeto de configuração
$config = new PluginProjecthelperConfiguration();

// Carrega o cabeçalho do GLPI
Html::header(
    __('ProjectHelper', 'projecthelper'),
    $_SERVER['PHP_SELF'],
    'config',
    'PluginProjecthelperConfiguration',
    'configuration'
);

// Pega o primeiro (e único) registro de configuração para editar
$config_id = $DB->request(['FROM' => 'glpi_plugin_projecthelper_configurations', 'fields' => 'id'])->fetch()['id'] ?? 0;

// Exibe o formulário
$config->showForm($config_id);

// Carrega o rodapé do GLPI
Html::footer();