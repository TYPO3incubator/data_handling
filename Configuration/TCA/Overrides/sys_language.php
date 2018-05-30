<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['sys_language'])) {
    $GLOBALS['TCA']['sys_language']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => false,
    ];
}
