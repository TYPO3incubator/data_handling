<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TCA']['tx_bootstrappackage_tab_item']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => true,
];