<?php declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in('bin')
    ->in('src');

$config = new PhpCsFixer\Config();

return $config->setRules([
        '@PSR12' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => true,
        'no_unused_imports' => true,
        'visibility_required' => false,
        'blank_line_after_opening_tag' => false,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
