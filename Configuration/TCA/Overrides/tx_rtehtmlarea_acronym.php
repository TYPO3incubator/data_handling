<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['tx_rtehtmlarea_acronym'])) {
    $GLOBALS['TCA']['tx_rtehtmlarea_acronym']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => false,
    ];
}
