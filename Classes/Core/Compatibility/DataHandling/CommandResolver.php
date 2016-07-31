<?php
namespace TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\Domain\Command\AbstractCommand;
use TYPO3\CMS\DataHandling\Core\Domain\Command\Identifiable;
use TYPO3\CMS\DataHandling\Core\Domain\Command\Record;
use TYPO3\CMS\DataHandling\Domain\Object\Record\Bundle;
use TYPO3\CMS\DataHandling\Domain\Object\Record\Reference;
use TYPO3\CMS\DataHandling\Domain\Object\Record\State;

/**
 * @deprecated Stuff from yesterday (aka "the past")
 */
class CommandResolver
{
    /**
     * @param Bundle $bundle
     * @param null $context
     * @return CommandResolver
     */
    public static function build(Bundle $bundle, $context = null)
    {
        return GeneralUtility::makeInstance(CommandResolver::class, $bundle, $context);
    }

    /**
     * @var Bundle
     */
    protected $bundle;

    /**
     * @var null
     */
    protected $context;

    /**
     * @var State
     */
    protected $previousState;

    /**
     * @var State
     */
    protected $currentState;

    /**
     * @var AbstractCommand
     */
    protected $commands = [];

    protected function __construct(Bundle $bundle, $context = null)
    {
        $this->bundle = $bundle;
        $this->context = $context;
    }

    protected function thaw()
    {
        $this->thawContext();
        $this->thawProperties();
    }

    protected function thawContext()
    {
        $this->currentState = State::instance();
        $this->currentState->getReference()->import($this->bundle->getReference());

        $reference = $this->currentState->getReference();

        if ($this->isValidUid($reference->getUid())) {
            $reference->setUuid(
                $this->fetchUuid($reference)
            );

            if ($this->isDifferentContext($this->bundle)) {
                $this->addCommand(
                    Record\ForkCommand::instance($reference->getName(), $reference->getUuid())
                );
            }
        } else {
            $this->addCommand(
                Record\CreateCommand::instance($reference->getName())
            );
        }
    }

    protected function thawProperties()
    {

    }

    protected function addCommand(AbstractCommand $command)
    {
        if ($command instanceof Identifiable) {
            $this->currentState->getReference()->setUuid(
                $command->getIdentifier()
            );
        }
        $this->commands[] = $command;
    }

    protected function isValidUid($uid): bool
    {
        return (!empty($uid) && MathUtility::canBeInterpretedAsInteger($uid));
    }

    protected function isDifferentContext(Bundle $bundle): bool
    {
        return false;
    }

    protected function fetchUuid(Reference $reference): string
    {
        $queryBuilder = ConnectionPool::instance()->getOriginQueryBuilder();
        $statement = $queryBuilder
            ->select('uuid')
            ->from($reference->getName())
            ->where($queryBuilder->expr()->eq('uid', $reference->getUid()))
            ->execute();
        return $statement->fetchColumn();
    }
}
