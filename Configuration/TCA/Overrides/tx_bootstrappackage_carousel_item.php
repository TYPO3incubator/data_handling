<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TCA']['tx_bootstrappackage_carousel_item']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => true,
];