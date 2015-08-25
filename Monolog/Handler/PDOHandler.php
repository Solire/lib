<?php

namespace Solire\Lib\Monolog\Handler;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Solire\Lib\Registry;

/**
 * Classe PDO Handler pour Monolog
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class PDOHandler extends AbstractProcessingHandler
{
    private $initialized = false;
    private $pdo;
    private $statement;

    /**
     * Constructeur
     *
     * @param \PDO      $pdo    Object PDO
     * @param bool|int  $level  Le niveau de journalisation minimale à laquelle ce gestionnaire sera déclenché
     * @param bool|true $bubble Limite ou non les enregistrements de logs similaires
     */
    public function __construct(\PDO $pdo, $level = Logger::DEBUG, $bubble = true)
    {
        $this->pdo = $pdo;
        parent::__construct($level, $bubble);
    }

    /**
     * Écrit le log en base de données
     *
     * @param array $record L'enregistrement à écrire
     *
     * @return void
     */
    protected function write(array $record)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        $remoteIp = Registry::get('request')->getClientIp();

        $this->statement->execute([
            'channel'  => $record['channel'],
            'level'    => $record['level'],
            'message'  => $record['formatted'],
            'time'     => $record['datetime']->format('U'),
            'remoteip' => $remoteIp
        ]);
    }

    /**
     * Initialisation
     *
     * @return void
     */
    private function initialize()
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS monolog '
            . '(channel VARCHAR(255), level INTEGER, message LONGTEXT, time INTEGER UNSIGNED, remoteip VARCHAR(255))'
        );
        $this->statement = $this->pdo->prepare(
            'INSERT INTO monolog (channel, level, message, time, remoteip) VALUES (:channel, :level, :message, :time, :remoteip)'
        );

        $this->initialized = true;
    }
}
