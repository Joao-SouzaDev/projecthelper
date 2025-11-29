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

    // Processa múltiplas seleções de checkboxes
    // Converte arrays de checkboxes em strings separadas por vírgulas
    if (isset($_POST['replicate_followups']) && is_array($_POST['replicate_followups'])) {
        $_POST['replicate_followups'] = implode(',', $_POST['replicate_followups']);
    } elseif (!isset($_POST['replicate_followups'])) {
        $_POST['replicate_followups'] = '0';
    }

    if (isset($_POST['replicate_tasks']) && is_array($_POST['replicate_tasks'])) {
        $_POST['replicate_tasks'] = implode(',', $_POST['replicate_tasks']);
    } elseif (!isset($_POST['replicate_tasks'])) {
        $_POST['replicate_tasks'] = '0';
    }

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
    3 => __('Yes, replicate from child to parent'),
    4 => __('Yes, replicate to related tickets')
];

// Converte string separada por vírgulas em array para checkboxes
$followups_selected = !empty($current_config['replicate_followups'])
    ? explode(',', $current_config['replicate_followups'])
    : [0];
$tasks_selected = !empty($current_config['replicate_tasks'])
    ? explode(',', $current_config['replicate_tasks'])
    : [0];

echo "<tr>";
echo "<td>" . __("Replicate follow-ups from linked tickets to the Project", "projecthelper") . "</td>";
echo "<td>";
// Exibe checkboxes para múltiplas seleções
echo "<div style='display: flex; flex-direction: column; gap: 5px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;'>";
foreach ($replicate_options as $key => $label) {
    $checked = in_array((string) $key, $followups_selected) ? 'checked' : '';
    // Se qualquer opção diferente de 0 está marcada, desabilita o "No"
    $has_other_selected = count(array_diff($followups_selected, ['0'])) > 0;
    $disabled = ($key == 0 && $has_other_selected) ? 'disabled' : '';
    echo "<label style='padding: 8px 0; border-bottom: 1px solid #e0e0e0;'><input type='checkbox' name='replicate_followups[]' value='$key' $checked $disabled /> $label</label>";
}
echo "</div>";
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<td>" . __("Replicate tasks from linked tickets to the Project", "projecthelper") . "</td>";
echo "<td>";
// Exibe checkboxes para múltiplas seleções
echo "<div style='display: flex; flex-direction: column; gap: 5px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;'>";
foreach ($replicate_options as $key => $label) {
    $checked = in_array((string) $key, $tasks_selected) ? 'checked' : '';
    // Se qualquer opção diferente de 0 está marcada, desabilita o "No"
    $has_other_selected = count(array_diff($tasks_selected, ['0'])) > 0;
    $disabled = ($key == 0 && $has_other_selected) ? 'disabled' : '';
    echo "<label style='padding: 8px 0; border-bottom: 1px solid #e0e0e0;'><input type='checkbox' name='replicate_tasks[]' value='$key' $checked $disabled /> $label</label>";
}
echo "</div>";
echo "</td>";
echo "</tr>";
echo "</table>";

echo "</div>";

// Exibe os botões do formulário
$config->showFormButtons(['formfooter' => true, 'candel' => false]);

echo "</form>";

Html::footer();