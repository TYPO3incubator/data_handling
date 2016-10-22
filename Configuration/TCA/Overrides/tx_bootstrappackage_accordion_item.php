<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TCA']['tt_content']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => true,
];