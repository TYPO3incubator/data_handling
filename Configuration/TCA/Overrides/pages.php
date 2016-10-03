<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TCA']['pages']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => true,
];