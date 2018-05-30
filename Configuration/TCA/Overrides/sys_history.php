<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['sys_history'])) {
    $GLOBALS['TCA']['sys_history']['ctrl']['eventSourcing'] = [
        'listenEvents' => false,
        'recordEvents' => false,
        'projectEvents' => false,
    ];
}
