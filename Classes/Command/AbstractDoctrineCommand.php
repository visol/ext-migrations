<?php

namespace FriendsOfTYPO3\Migrations\Command;

use FriendsOfTYPO3\Migrations\Service\DoctrineService;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command;

/**
 * Class AbstractDoctrineCommand
 */
abstract class AbstractDoctrineCommand extends Command
{

    public const SUCCESS = 'success';
    public const WARNING = 'warning';
    public const ERROR = 'error';
    protected DoctrineService $doctrineService;
    protected SymfonyStyle $io;

    /**
     * @var bool
     */
    protected $isSilent = false;

    /**
     * @param string $message
     * @param array $arguments
     * @param string $severity can be 'warning', 'error', 'success'
     */
    protected function log(string $message = '', array $arguments = [], $severity = '')
    {
        if (!$this->isSilent) {
            $formattedMessage = vsprintf($message, $arguments);
            if ($severity) {
                $this->io->$severity($formattedMessage);
            } else {
                $this->io->writeln($formattedMessage);
            }
        }
    }

    /**
     * @param string $message
     * @param array $arguments
     */
    protected function success(string $message = '', array $arguments = [])
    {
        $this->log($message, $arguments, self::SUCCESS);
    }

    /**
     * @param string $message
     * @param array $arguments
     */
    protected function warning(string $message = '', array $arguments = [])
    {
        $this->log($message, $arguments, self::WARNING);
    }

    /**
     * @param string $message
     * @param array $arguments
     */
    protected function error(string $message = '', array $arguments = [])
    {
        $this->log($message, $arguments, self::ERROR);
    }

}
