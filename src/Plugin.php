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
    public function __invoke(string $sql) : string
    {
        $stmt = $this->loader->getPdo()->prepare(
            "SELECT trigger_name, event_object_table
                FROM INFORMATION_SCHEMA.TRIGGERS WHERE
                ((TRIGGER_CATALOG = ? AND TRIGGER_SCHEMA = 'public') OR TRIGGER_SCHEMA = ?)");
        $stmt->execute([$this->loader->getDatabase(), $this->loader->getDatabase()]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $trigger) {
            $this->loader->addOperation("DROP TRIGGER {$trigger['trigger_name']};");
        }
        if (preg_match_all("@^CREATE TRIGGER.*?^END;$@ms", $sql, $triggers, PREG_SET_ORDER)) {
            foreach ($triggers as $trigger) {
                $this->triggers[] = $trigger[0];
                $sql = str_replace($trigger[0], '', $sql);
            }
        }
        return $sql;
    }
}

