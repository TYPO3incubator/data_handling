<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['backend_layout'])) {
    $GLOBALS['TCA']['backend_layout']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => false,
    ];
}
