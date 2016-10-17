<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TCA']['tt_content']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => true,
];