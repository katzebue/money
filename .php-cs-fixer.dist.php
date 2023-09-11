<?php

if (!file_exists(__DIR__ . '/src')) {
    exit(0);
}

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        '@PHP82Migration' => true,
    ])
    ->setFinder(
        (new PhpCsFixer\Finder())
            ->in(__DIR__ . '/src')
            ->in(__DIR__ . '/tests')
            ->append([__FILE__])
    );
