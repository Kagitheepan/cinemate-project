<?php
require 'vendor/autoload.php';
$kernel = new App\Kernel('dev', true);
$kernel->boot();
$tmdb = $kernel->getContainer()->get('App\Service\TmdbService');
print_r($tmdb->getVideos(256040));
