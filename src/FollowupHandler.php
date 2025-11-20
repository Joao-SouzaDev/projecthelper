<?php

namespace GlpiPlugin\Projecthelper;

use ITILFollowup;
use Ticket;
use ProjectTask_Ticket;
use ProjectTask;
use Config as PluginConfig;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class FollowupHandler
{
    /**
     * Flag para evitar recursão infinita
     */
    private static $is_replicating = false;

    /**
     * Função auxiliar para logs de debug (desabilitada em produção)
     * 
     * @param string $message
     * @return void
     */
    private static function logDebug($message)
    {
        // Log desabilitado para evitar problemas de permissão
        // Para debug, descomente a linha abaixo:
        // error_log("[ProjectHelper FollowupHandler] $message");
    }

    /**
     * Hook chamado após adicionar um followup
     * 
     * @param ITILFollowup $followup
     * @return void
     */
    public static function afterAddFollowup(ITILFollowup $followup)
    {
        global $DB;

        // Log de debug
        self::logDebug("Hook afterAddFollowup chamado");

        // Evita recursão infinita
        if (self::$is_replicating) {
            self::logDebug("Recursão detectada, abortando");
            return;
        }

        // Busca a configuração do plugin diretamente do banco
        $replicate_config = new Config();
        $replicate_config->getFromDB(1);

        self::logDebug("Configuração replicate_followups: " . $replicate_config->fields['replicate_followups']);

        // Verifica se a replicação está habilitada (1 = Yes for all)
        if ($replicate_config->fields['replicate_followups'] != 1) {
            self::logDebug("Replicação desabilitada");
            return;
        }

        // Obtém dados do followup recém-criado
        $followup_data = $followup->fields;

        // Verifica se é um followup de ticket
        if ($followup_data['itemtype'] != 'Ticket') {
            self::logDebug("Followup não é de ticket, itemtype: " . $followup_data['itemtype']);
            return;
        }

        $ticket_id = $followup_data['items_id'];
        self::logDebug("Processando followup do ticket #$ticket_id");

        // Busca o projeto associado ao ticket atual
        $project_id = self::getProjectFromTicket($ticket_id);

        if (!$project_id) {
            self::logDebug("Ticket #$ticket_id não está vinculado a nenhum projeto");
            return;
        }

        self::logDebug("Ticket vinculado ao projeto #$project_id");

        // Busca todos os outros tickets do mesmo projeto
        $related_tickets = self::getTicketsFromProject($project_id, $ticket_id);

        self::logDebug("Encontrados " . count($related_tickets) . " tickets relacionados");

        if (empty($related_tickets)) {
            self::logDebug("Nenhum ticket relacionado para replicar");
            return;
        }

        // Ativa flag de replicação
        self::$is_replicating = true;

        // Replica o followup para cada ticket relacionado
        foreach ($related_tickets as $related_ticket_id) {
            self::logDebug("Replicando followup para ticket #$related_ticket_id");
            $result = self::replicateFollowup($followup_data, $related_ticket_id);
            self::logDebug("Resultado da replicação: " . ($result ? "sucesso" : "falha"));
        }

        // Desativa flag de replicação
        self::$is_replicating = false;
        self::logDebug("Replicação concluída");
    }

    /**
     * Busca a configuração de replicação do banco de dados
     * 
     * @return int
     */
    private static function getReplicateConfig()
    {
        global $DB;

        $query = "SELECT replicate_followups 
                  FROM glpi_plugin_projecthelper_configs 
                  WHERE id = 1 
                  LIMIT 1";

        $result = $DB->query($query);

        if ($result && $DB->numrows($result) > 0) {
            $row = $DB->fetchAssoc($result);
            return (int) $row['replicate_followups'];
        }

        return 0; // Desabilitado por padrão
    }

    /**
     * Obtém o ID do projeto a partir de um ticket
     * 
     * @param int $ticket_id
     * @return int|false
     */
    private static function getProjectFromTicket($ticket_id)
    {
        global $DB;

        // Busca o projeto associado ao ticket
        $query = "SELECT projects_id 
                  FROM glpi_itils_projects
                  WHERE items_id = " . (int) $ticket_id . " AND itemtype = 'Ticket'
                  LIMIT 1";

        self::logDebug("Query para buscar projeto: " . $query);

        $result = $DB->query($query);

        if ($result && $DB->numrows($result) > 0) {
            $row = $DB->fetchAssoc($result);
            self::logDebug("Projeto encontrado: " . $row['projects_id']);
            return $row['projects_id'];
        }

        self::logDebug("Nenhum resultado na query. Rows: " . ($result ? $DB->numrows($result) : 'null'));
        return false;
    }

    /**
     * Busca todos os tickets de um projeto, exceto o ticket atual
     * 
     * @param int $project_id
     * @param int $exclude_ticket_id
     * @return array
     */
    private static function getTicketsFromProject($project_id, $exclude_ticket_id)
    {
        global $DB;

        $tickets = [];

        // Busca todos os tickets vinculados ao mesmo projeto
        $query = "SELECT DISTINCT items_id 
                  FROM glpi_itils_projects
                  WHERE projects_id = " . (int) $project_id . " 
                  AND items_id != " . (int) $exclude_ticket_id . " 
                  AND itemtype = 'Ticket'";

        $result = $DB->query($query);

        if ($result) {
            while ($row = $DB->fetchAssoc($result)) {
                $tickets[] = $row['items_id'];
            }
        }

        return $tickets;
    }

    /**
     * Replica um followup para outro ticket
     * 
     * @param array $original_followup_data
     * @param int $target_ticket_id
     * @return bool
     */
    private static function replicateFollowup($original_followup_data, $target_ticket_id)
    {
        $new_followup = new ITILFollowup();

        // Prepara os dados para o novo followup
        $data = [
            'itemtype' => 'Ticket',
            'items_id' => $target_ticket_id,
            'date' => $original_followup_data['date'],
            'users_id' => $original_followup_data['users_id'],
            'content' => $original_followup_data['content'],
            'is_private' => $original_followup_data['is_private'],
            'requesttypes_id' => $original_followup_data['requesttypes_id'] ?? 0,
            'timeline_position' => $original_followup_data['timeline_position'] ?? 1,
        ];

        // Adiciona o novo followup
        return $new_followup->add($data);
    }
}
