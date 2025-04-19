<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$header = <<<'EOF'
This file is part of PHP CS Fixer.
(c) Fabien Potencier <fabien@symfony.com>
    Dariusz Rumiński <dariusz.ruminski@gmail.com>
This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOF;

$finder = (new Finder())
    ->ignoreDotFiles(false)
    ->ignoreVCSIgnored(true)
    ->exclude(['dev-tools/phpstan', 'tests/Fixtures'])
    ->in(__DIR__)
;

return (new Config())->setRules([
    '@PSR12'                 => true,
    'binary_operator_spaces' => ['default' => 'align_single_space_minimal'],
    'braces_position'        => [
        'control_structures_opening_brace'      => 'next_line_unless_newline_at_signature_end',
        'allow_single_line_anonymous_functions' => true,
    ],
    'control_structure_continuation_position' => ['position' => 'next_line'],
    'array_syntax'                            => ['syntax' => 'short'],
    'visibility_required'                     => ['elements' => ['property', 'method']],
    'blank_line_before_statement'             => true,
    'spaces_inside_parentheses'               => ['space' => 'none'],
    'yoda_style'                              => true,
    'cast_spaces'                             => ['space' => 'single'],
    'class_attributes_separation'             => ['elements' => ['method' => 'one']],
    'function_declaration'                    => false,
    'single_space_around_construct'           => ['constructs_followed_by_a_single_space' => ['echo']],
])
                     ->setIndent("  ")
;
