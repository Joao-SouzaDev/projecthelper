<?php
/**
 * @return array
 */
function plugin_projecthelper_get_config_pages()
{
    return [
        'front/configuration.form.php' => __('Geral', 'projecthelper')
    ];
}

/**
 * @return bool
 */
function plugin_projecthelper_install()
{
    // Adiciona o registro de configuração padrão, se não existir
    PluginProjecthelperConfiguration::install();
    return true;
}

/**
 * @return bool
 */
function plugin_projecthelper_uninstall()
{
    // Aqui você pode adicionar lógica para remover tabelas e configurações, se desejar.
    return true;
}