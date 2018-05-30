<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['sys_news'])) {
    $GLOBALS['TCA']['sys_news']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => true,
    ];
}
