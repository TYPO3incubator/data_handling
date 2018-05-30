<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['sys_category'])) {
    $GLOBALS['TCA']['sys_category']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => true,
    ];
}
