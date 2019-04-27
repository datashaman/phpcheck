<?php

use Sami\RemoteRepository\GitHubRemoteRepository;
use Sami\Sami;
use Symfony\Component\Finder\Finder;

$iterator = Finder::create()
	->files()
	->name('*.php')
	->in($dir = __DIR__.'/src');

return new Sami(
    $iterator,
    [
        'default_opened_level' => 2,
        'remote_repository' => new GitHubRemoteRepository('datashaman/phpcheck', dirname($dir)),
        'sort_class_constants' => true,
        'sort_class_interfaces' => true,
        'sort_class_methods' => true,
        'sort_class_properties' => true,
        'sort_class_traits' => true,
        'title' => 'PHPCheck API',
    ]
);
