<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TCA']['pages_language_overlay']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => true,
];