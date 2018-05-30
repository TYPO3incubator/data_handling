<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['fe_users'])) {
    $GLOBALS['TCA']['fe_users']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => false,
    ];
}
