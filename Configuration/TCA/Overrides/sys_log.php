<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['sys_log'])) {
    $GLOBALS['TCA']['sys_log']['ctrl']['eventSourcing'] = [
        'listenEvents' => false,
        'recordEvents' => false,
        'projectEvents' => false,
    ];
}
