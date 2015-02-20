<?php

namespace Dizda\CloudBackupBundle\Manager;

use Dizda\CloudBackupBundle\Client\ClientChain;
use Dizda\CloudBackupBundle\Client\ClientInterface;
use Dizda\CloudBackupBundle\Database\DatabaseChain;
use Dizda\CloudBackupBundle\Database\DatabaseInterface;
use Monolog\Logger;

class BackupManager
{
    /**
     * @var \Monolog\Logger
     */
    private $logger;

    /**
     * @var \Dizda\CloudBackupBundle\Database\DatabaseChain
     */
    private $database;

    /**
     * @var \Dizda\CloudBackupBundle\Client\ClientChain
     */
    private $client;

    /**
     * @var \Dizda\CloudBackupBundle\Manager\ProcessorManager
     */
    private $processor;

    /**
     * @param Logger $logger
     * @param DatabaseInterface $database
     * @param ClientInterface $client
     * @param ProcessorManager $processor
     */
    public function __construct(Logger $logger, DatabaseInterface $database, ClientInterface $client, ProcessorManager $processor)
    {
        $this->logger    = $logger;
        $this->database  = $database;
        $this->client    = $client;
        $this->processor = $processor;
    }

    /**
     * Start the backup
     *
     * @return bool
     */
    public function execute()
    {
        try {
            // Dump all databases
            $this->database->dump();

            // Backup folders if specified
            $this->processor->copyFolders();

            // Compress everything
            $this->processor->compress();

            var_dump($this->processor->getArchivePath());

            // Transfer with all clients
            $this->client->upload($this->processor->getArchivePath());

            $this->processor->cleanUp();

        } catch (\Exception $e) {
            // write log
            $this->logger->critical('Error while DizdaBackupManager was running.'."\n".$e);

            return false;
        }

        return true;
    }
}
