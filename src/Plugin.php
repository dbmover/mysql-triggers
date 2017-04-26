<?php

/**
 * @package Dbmover
 * @subpackage Mysql
 * @subpackage Triggers
 */

namespace Dbmover\Mysql\Triggers;

use Dbmover\Core;
use PDO;

class Plugin extends Core\Plugin
{
    public $description = 'Dropping existing triggers...';

    public function __invoke(string $sql) : string
    {
        $stmt = $this->loader->getPdo()->prepare(
            "SELECT trigger_name, event_object_table
                FROM INFORMATION_SCHEMA.TRIGGERS WHERE TRIGGER_SCHEMA = ?");
        $stmt->execute([$this->loader->getDatabase()]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $trigger) {
            $this->addOperation("DROP TRIGGER {$trigger['trigger_name']};");
        }
        if (preg_match_all("@^CREATE TRIGGER.*?^END;$@ms", $sql, $triggers, PREG_SET_ORDER)) {
            foreach ($triggers as $trigger) {
                $this->defer($trigger[0]);
                $sql = str_replace($trigger[0], '', $sql);
            }
        }
        return $sql;
    }

    public function __destruct()
    {
        $this->description = 'Recreating triggers...';
        parent::__destruct();
    }
}

