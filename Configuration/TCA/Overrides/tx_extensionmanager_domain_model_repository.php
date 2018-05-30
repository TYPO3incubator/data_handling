<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['tx_extensionmanager_domain_model_repository'])) {
    $GLOBALS['TCA']['tx_extensionmanager_domain_model_repository']['ctrl']['eventSourcing'] = [
        'listenEvents' => false,
        'recordEvents' => false,
        'projectEvents' => false,
    ];
}
