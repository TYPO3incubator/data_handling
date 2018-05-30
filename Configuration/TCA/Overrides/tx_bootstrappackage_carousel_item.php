<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['tx_bootstrappackage_carousel_item'])) {
    $GLOBALS['TCA']['tx_bootstrappackage_carousel_item']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => true,
    ];
}
