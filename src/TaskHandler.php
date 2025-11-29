<?php

namespace GlpiPlugin\Projecthelper;

use TicketTask;
use Ticket;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class TaskHandler
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
        // error_log("[ProjectHelper TaskHandler] $message");
    }

    /**
     * Hook chamado após adicionar uma task
     * 
     * @param TicketTask $task
     * @return void
     */
    public static function afterAddTask(TicketTask $task)
    {
        global $DB;

        // Log de debug
        self::logDebug("Hook afterAddTask chamado");

        // Evita recursão infinita
        if (self::$is_replicating) {
            self::logDebug("Recursão detectada, abortando");
            return;
        }

        // Busca a configuração do plugin diretamente do banco
        $replicate_config = new Config();
        $replicate_config->getFromDB(1);

        self::logDebug("Configuração replicate_tasks: " . $replicate_config->fields['replicate_tasks']);

        $replication_modes_string = $replicate_config->fields['replicate_tasks'];

        // Converte string de modos para array (ex: "1,2,4" => [1, 2, 4])
        $replication_modes = array_map('intval', explode(',', $replication_modes_string));

        // Verifica se a replicação está desabilitada (modo 0 ou vazio)
        if (empty($replication_modes) || (count($replication_modes) === 1 && $replication_modes[0] === 0)) {
            self::logDebug("Replicação desabilitada");
            return;
        }

        // Obtém dados da task recém-criada
        $task_data = $task->fields;

        // Verifica se é uma task de ticket
        if (!isset($task_data['tickets_id']) || empty($task_data['tickets_id'])) {
            self::logDebug("Task não é de ticket");
            return;
        }

        $ticket_id = $task_data['tickets_id'];
        self::logDebug("Processando task do ticket #$ticket_id");

        $related_tickets = [];

        // Processa cada modo de replicação configurado
        foreach ($replication_modes as $mode) {
            self::logDebug("Processando modo: $mode");

            // Modo 1: Replicar para todos os tickets do mesmo projeto
            if ($mode == 1) {
                self::logDebug("Modo: Replicar para todos do projeto");

                $project_id = self::getProjectFromTicket($ticket_id);

                if (!$project_id) {
                    self::logDebug("Ticket #$ticket_id não está vinculado a nenhum projeto");
                    continue;
                }

                self::logDebug("Ticket vinculado ao projeto #$project_id");
                $tickets = self::getTicketsFromProject($project_id, $ticket_id);
                $related_tickets = array_merge($related_tickets, $tickets);
            }
            // Modo 2: Replicar de pai para filhos
            elseif ($mode == 2) {
                self::logDebug("Modo: Replicar de pai para filhos");
                $tickets = self::getChildrenTickets($ticket_id);
                $related_tickets = array_merge($related_tickets, $tickets);
            }
            // Modo 3: Replicar de filho para pai
            elseif ($mode == 3) {
                self::logDebug("Modo: Replicar de filho para pai");
                $parent_ticket = self::getParentTicket($ticket_id);
                if ($parent_ticket) {
                    $related_tickets[] = $parent_ticket;
                }
            }
            // Modo 4: Replicar para tickets relacionados (link = 2)
            elseif ($mode == 4) {
                self::logDebug("Modo: Replicar para tickets relacionados");
                $tickets = self::getRelatedTickets($ticket_id);
                $related_tickets = array_merge($related_tickets, $tickets);
            }
        }

        // Remove duplicatas
        $related_tickets = array_unique($related_tickets);

        self::logDebug("Encontrados " . count($related_tickets) . " tickets relacionados");

        if (empty($related_tickets)) {
            self::logDebug("Nenhum ticket relacionado para replicar");
            return;
        }

        // Ativa flag de replicação
        self::$is_replicating = true;

        // Replica a task para cada ticket relacionado
        foreach ($related_tickets as $related_ticket_id) {
            self::logDebug("Replicando task para ticket #$related_ticket_id");
            $result = self::replicateTask($task_data, $related_ticket_id);
            self::logDebug("Resultado da replicação: " . ($result ? "sucesso" : "falha"));
        }

        // Desativa flag de replicação
        self::$is_replicating = false;
        self::logDebug("Replicação concluída");
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
     * Busca todos os tickets filhos de um ticket pai
     * 
     * @param int $parent_ticket_id
     * @return array
     */
    private static function getChildrenTickets($parent_ticket_id)
    {
        global $DB;

        $tickets = [];

        // Busca todos os tickets filhos onde tickets_id_2 = pai e link = 3
        $query = "SELECT DISTINCT tickets_id_1 
                  FROM glpi_tickets_tickets
                  WHERE tickets_id_2 = " . (int) $parent_ticket_id . " 
                  AND link = 3";

        self::logDebug("Query para buscar filhos: " . $query);

        $result = $DB->query($query);

        if ($result) {
            while ($row = $DB->fetchAssoc($result)) {
                $tickets[] = $row['tickets_id_1'];
                self::logDebug("Filho encontrado: #" . $row['tickets_id_1']);
            }
        }

        return $tickets;
    }

    /**
     * Busca o ticket pai de um ticket filho
     * 
     * @param int $child_ticket_id
     * @return int|false
     */
    private static function getParentTicket($child_ticket_id)
    {
        global $DB;

        // Busca o ticket pai onde tickets_id_1 = filho e link = 3
        $query = "SELECT tickets_id_2 
                  FROM glpi_tickets_tickets
                  WHERE tickets_id_1 = " . (int) $child_ticket_id . " 
                  AND link = 3
                  LIMIT 1";

        self::logDebug("Query para buscar pai: " . $query);

        $result = $DB->query($query);

        if ($result && $DB->numrows($result) > 0) {
            $row = $DB->fetchAssoc($result);
            self::logDebug("Pai encontrado: #" . $row['tickets_id_2']);
            return $row['tickets_id_2'];
        }

        self::logDebug("Nenhum pai encontrado");
        return false;
    }

    /**
     * Busca todos os tickets relacionados (link = 1)
     * 
     * @param int $ticket_id
     * @return array
     */
    private static function getRelatedTickets($ticket_id)
    {
        global $DB;

        $tickets = [];

        // Busca tickets relacionados onde o ticket atual é tickets_id_1 ou tickets_id_2
        // e link = 1 (relacionado)
        $query = "SELECT DISTINCT 
                    CASE 
                        WHEN tickets_id_1 = " . (int) $ticket_id . " THEN tickets_id_2
                        WHEN tickets_id_2 = " . (int) $ticket_id . " THEN tickets_id_1
                    END as related_id
                  FROM glpi_tickets_tickets
                  WHERE (tickets_id_1 = " . (int) $ticket_id . " OR tickets_id_2 = " . (int) $ticket_id . ")
                  AND link = 1";

        self::logDebug("Query para buscar relacionados: " . $query);

        $result = $DB->query($query);

        if ($result) {
            while ($row = $DB->fetchAssoc($result)) {
                if ($row['related_id']) {
                    $tickets[] = $row['related_id'];
                    self::logDebug("Relacionado encontrado: #" . $row['related_id']);
                }
            }
        }

        return $tickets;
    }

    /**
     * Replica uma task para outro ticket
     * 
     * IMPORTANTE: A task replicada é apenas informativa, não é um apontamento de tempo.
     * Por isso, os campos de tempo (begin, end, actiontime) não são preenchidos e o
     * state é sempre definido como 1 (Information).
     * 
     * @param array $original_task_data
     * @param int $target_ticket_id
     * @return bool
     */
    private static function replicateTask($original_task_data, $target_ticket_id)
    {
        $new_task = new TicketTask();

        // Prepara os dados para a nova task
        // A task replicada é APENAS INFORMATIVA, não inclui apontamento de tempo
        $data = [
            'tickets_id' => $target_ticket_id,
            'taskcategories_id' => $original_task_data['taskcategories_id'] ?? 0,
            'date' => $original_task_data['date'],
            'users_id' => $original_task_data['users_id'],
            'users_id_tech' => $original_task_data['users_id_tech'] ?? 0,
            'groups_id_tech' => $original_task_data['groups_id_tech'] ?? 0,
            'content' => $original_task_data['content'],
            'is_private' => $original_task_data['is_private'] ?? 0,
            // Campos de tempo NÃO são replicados (task apenas informativa)
            'begin' => null,
            'end' => null,
            'actiontime' => 0,
            // State sempre 1 = Information (não é um apontamento real)
            'state' => 1,
        ];

        // Adiciona a nova task
        return $new_task->add($data);
    }
}
