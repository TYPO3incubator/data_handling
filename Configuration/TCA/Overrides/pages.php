<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['pages'])) {
    $GLOBALS['TCA']['pages']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => true,
    ];
}
