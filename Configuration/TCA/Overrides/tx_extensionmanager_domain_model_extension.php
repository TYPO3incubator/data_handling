<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TCA']['tx_extensionmanager_domain_model_extension']['ctrl']['eventSourcing'] = [
    'listenEvents' => false,
    'recordEvents' => false,
    'projectEvents' => false,
];