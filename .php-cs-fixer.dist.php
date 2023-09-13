<?php declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in('bin')->name('console')
    ->in('config')->name('container.php')
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
        'method_argument_space' => ['on_multiline' => 'ignore'],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
