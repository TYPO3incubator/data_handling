<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['sys_domain'])) {
    $GLOBALS['TCA']['sys_domain']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => false,
    ];
}
