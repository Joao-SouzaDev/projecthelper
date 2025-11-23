<?php

include('../../../inc/includes.php');

use GlpiPlugin\Projecthelper\Config;

// Define the UPDATE constant if not already defined (GLPI rights)
if (!defined('UPDATE')) {
    define('UPDATE', 2);
}

// Título da página de configuração
Html::header(
    __('Project Helper', 'projecthelper'),
    $_SERVER['PHP_SELF'],
    'config',
    'plugins',
    'projecthelper'
);

// Verifica se o usuário tem permissão para configurar
Session::checkRight('config', UPDATE);

$config = new Config();

// Ação de salvar as configurações
if (isset($_POST['update'])) {
    // ATENÇÃO: A validação de CSRF foi removida a seu pedido.

    // CORREÇÃO: Adiciona o ID ao array de dados antes de chamar o update.
    $_POST['id'] = 1;

    // CORREÇÃO: A função update espera um único array contendo todos os dados, inclusive o ID.
    if ($config->update($_POST)) {
        Session::addMessageAfterRedirect(__('Configuration saved successfully.'), true, INFO);
    } else {
        Session::addMessageAfterRedirect(__('Error saving configuration.'), true, ERROR);
    }
    Html::redirect($CFG_GLPI['root_doc'] . "/plugins/projecthelper/front/config.form.php");
}

// Busca a configuração com ID 1
$config->getFromDB(1);
$current_config = $config->fields;


// Início do formulário
echo "<form method='post' action='config.form.php' class='glpi_form'>";
echo "<div class='center-h'>";
echo "<table class='tab_cadre_fixe' style='width: 70%;'>";

// Cabeçalho da tabela de configuração
echo "<tr class='headerRow'><th colspan='2'>" . __('Settings', 'projecthelper') . "</th></tr>";

// Opção: Mostrar Barra de progresso do projeto
echo "<tr>";
echo "<td>" . __('Show project progress bar', 'projecthelper') . "</td>";
echo "<td>";
Dropdown::showYesNo("show_progress_bar", $current_config['show_progress_bar']);
echo "</td>";
echo "</tr>";

$replicate_options = [
    0 => __('No'),
    1 => __('Yes, replicate to all project tickets'),
    2 => __('Yes, replicate from parent to children'),
    3 => __('Yes, replicate from child to parent')
];

echo "<tr>";
echo "<td>" . __("Replicate follow-ups from linked tickets to the Project", "projecthelper") . "</td>";
echo "<td>";
Dropdown::showFromArray("replicate_followups", $replicate_options, ['value' => $current_config['replicate_followups']]);
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<td>" . __("Replicate tasks from linked tickets to the Project", "projecthelper") . "</td>";
echo "<td>";
Dropdown::showFromArray("replicate_tasks", $replicate_options, ['value' => $current_config['replicate_tasks']]);
echo "</td>";
echo "</tr>";
echo "</table>";

echo "</div>";

// Exibe os botões do formulário
$config->showFormButtons(['formfooter' => true, 'candel' => false]);

echo "</form>";

Html::footer();