<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['tt_content'])) {
    $GLOBALS['TCA']['tt_content']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => true,
    ];
}
