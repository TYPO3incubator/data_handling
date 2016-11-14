<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Interceptor\Hook\Frontend;

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
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\Common\ProjectionContext;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Event;

class PostBackendUserAuthenticationHook
{
    public function execute(array $parameters)
    {
        $workspaceId = 0;

        /** @var BackendUserAuthentication $backendUser */
        $backendUser = $parameters['BE_USER'];
        if (isset($backendUser->workspace)) {
            $workspaceId = $backendUser->workspace;
        }

        $projectionContext = ProjectionContext::provide();
        $projectionContext->setWorkspaceId($workspaceId);
        $projectionContext->lock();

        $projectionContext->enforceLocalStorage();
    }
}
