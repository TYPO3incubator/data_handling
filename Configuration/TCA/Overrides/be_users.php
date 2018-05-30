<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['be_users'])) {
    $GLOBALS['TCA']['be_users']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => false,
    ];
}
