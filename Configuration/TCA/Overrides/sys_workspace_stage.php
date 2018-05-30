<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['sys_workspace_stage'])) {
    $GLOBALS['TCA']['sys_workspace_stage']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => false,
    ];
}
