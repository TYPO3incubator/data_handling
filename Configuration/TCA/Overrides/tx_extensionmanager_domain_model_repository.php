<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TCA']['tx_extensionmanager_domain_model_repository']['ctrl']['eventSourcing'] = [
    'listenEvents' => false,
    'recordEvents' => false,
    'projectEvents' => false,
];