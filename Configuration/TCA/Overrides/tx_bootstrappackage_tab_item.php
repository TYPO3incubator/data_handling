<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['tx_bootstrappackage_tab_item'])) {
    $GLOBALS['TCA']['tx_bootstrappackage_tab_item']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => true,
    ];
}
