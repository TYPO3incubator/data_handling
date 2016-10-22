<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TCA']['fe_groups']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => false,
];