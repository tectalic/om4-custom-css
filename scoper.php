<?php

use Isolated\Symfony\Component\Finder\Finder;

return [
	'prefix'  => 'OM4\Vendor',
	'finders' => [
		// For more see: https://github.com/humbug/php-scoper#finders-and-paths.
		Finder::create()->files()->in('vendor/scssphp/scssphp/')->name(['*.php']),
	],
	'patchers' => [
        function (string $filePath, string $prefix, string $content): string {
            //
            // PHP-Parser patch conditions for file targets
            //
			return str_replace(
				' \ScssPhp\ScssPhp',
				' \OM4\Vendor\ScssPhp\ScssPhp',
				$content
			);
            return $content;
        },
    ],
];
