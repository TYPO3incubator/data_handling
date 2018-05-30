<?php
defined('TYPO3_MODE') || die();

// @todo Remove in v9
if (!empty($GLOBALS['TCA']['pages_language_overlay'])) {
    $GLOBALS['TCA']['pages_language_overlay']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => true,
    ];
}
