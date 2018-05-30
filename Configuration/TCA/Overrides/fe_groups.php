<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['fe_groups'])) {
    $GLOBALS['TCA']['fe_groups']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => false,
    ];
}
