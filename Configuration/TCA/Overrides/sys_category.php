<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TCA']['sys_category']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => true,
];