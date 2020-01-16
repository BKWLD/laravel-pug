<?php

list($laravelVersion, $pugVersion) = array_slice($argv, 1);

$composerFile = __DIR__ . '/../composer.json';
$composer = file_get_contents($composerFile);
$newContent = $composer;

foreach (['illuminate/support', 'illuminate/view'] as $package) {
    $newContent = preg_replace(
        '/"' . preg_quote($package, '/') . '"\s*:\s*"[^"]+"/',
        '"' . $package . '": "' . $laravelVersion . '"',
        $newContent
    );
}

$newContent = preg_replace(
    '/' . preg_quote('"php": ">=5.4.0",') . '/',
    '$0"pug-php/pug": "' . $pugVersion . '",',
    $newContent
);

if ($newContent === $composer) {
    echo 'illuminate/support and illuminate/view not found in ./composer.json';
    exit(1);
}

if (empty($newContent) || !file_put_contents($composerFile, $newContent)) {
    echo './composer.json cannot be updated';
    exit(1);
}

echo "./composer.json has been updated:\n$newContent";

exit(0);
