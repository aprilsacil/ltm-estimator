<?php //-->
use Cradle\Module\Utility\File;

require_once __DIR__ . '/src/events.php';

$this->preprocess(function($request, $response) {
    $extensions = $this->package('global')->path('public') . '/json/extensions.json';
    $json = file_get_contents($extensions);
    File::$extensions = json_decode($json, true);
});
