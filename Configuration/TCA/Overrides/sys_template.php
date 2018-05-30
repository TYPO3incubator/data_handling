<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['sys_template'])) {
    $GLOBALS['TCA']['sys_template']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => true,
    ];
}
