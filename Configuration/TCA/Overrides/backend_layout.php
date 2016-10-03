<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TCA']['backend_layout']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => false,
];