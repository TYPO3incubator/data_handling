<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['sys_workspace'])) {
    $GLOBALS['TCA']['sys_workspace']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => false,
    ];
}
