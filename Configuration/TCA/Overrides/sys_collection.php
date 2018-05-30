<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['sys_collection'])) {
    $GLOBALS['TCA']['sys_collection']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => true,
    ];
}
