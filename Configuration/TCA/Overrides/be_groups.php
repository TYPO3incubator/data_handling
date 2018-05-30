<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['be_groups'])) {
    $GLOBALS['TCA']['be_groups']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => false,
    ];
}
