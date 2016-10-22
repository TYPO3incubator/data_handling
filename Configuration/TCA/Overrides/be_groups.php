<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TCA']['be_groups']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => false,
];