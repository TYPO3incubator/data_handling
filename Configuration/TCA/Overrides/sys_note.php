<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['sys_note'])) {
    $GLOBALS['TCA']['sys_note']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => true,
    ];
}
