<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['sys_filemounts'])) {
    $GLOBALS['TCA']['sys_filemounts']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => false,
    ];
}
