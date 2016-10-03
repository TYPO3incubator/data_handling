<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TCA']['be_groups']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => false,
];