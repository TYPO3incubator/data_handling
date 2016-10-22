<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TCA']['backend_layout']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => false,
];