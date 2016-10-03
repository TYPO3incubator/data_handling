<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TCA']['sys_workspace_stage']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => false,
];