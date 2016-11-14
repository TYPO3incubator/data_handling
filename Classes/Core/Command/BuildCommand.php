<?php
namespace TYPO3\CMS\DataHandling\Core\Command;

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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\DataHandling\Core\Service\ProjectionService;
use TYPO3\CMS\EventSourcing\Infrastructure\EventStore\EventSelector;

class BuildCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Builder');
        $this->addArgument('concerning', InputArgument::REQUIRED, 'Event selector used for building (e.g. "$stream-name")');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ProjectionService::instance()
            ->project(EventSelector::create($input->getArgument('concerning')));
    }
}
