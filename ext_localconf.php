<?php
defined('TYPO3_MODE') || die();

\TYPO3\CMS\DataHandling\Common::overrideConfiguration();
\TYPO3\CMS\DataHandling\Common::registerAlternativeImplementations();

\TYPO3\CMS\DataHandling\Common::registerHooks();
\TYPO3\CMS\DataHandling\Common::registerSlots();

\TYPO3\CMS\DataHandling\Common::registerUpdates();
\TYPO3\CMS\DataHandling\Common::registerEventSources();
