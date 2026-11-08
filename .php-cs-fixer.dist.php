<?php

// Một bộ quy tắc định dạng duy nhất cho toàn dự án (constitution, Nguyên tắc I).
// Chạy: composer format

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/bin', __DIR__ . '/templates'])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRules(['@PSR12' => true])
    ->setFinder($finder);
