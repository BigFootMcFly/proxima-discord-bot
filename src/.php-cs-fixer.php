<?php

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS' => true,
        '@PHP82Migration' => true,
        'new_with_parentheses' => [
            'anonymous_class' => false,
        ],
        'braces_position' => [
            'anonymous_classes_opening_brace' => 'next_line_unless_newline_at_signature_end',
        ],
        'function_declaration' => [
            'closure_fn_spacing' => 'one',
            'closure_function_spacing' => 'one',
        ],
        'single_trait_insert_per_statement' => false,
        'no_blank_lines_after_class_opening' => false,

    ])
    ->setFinder((new PhpCsFixer\Finder())
        ->in(__DIR__)
        ->exclude([
            'Bootstrap', // skip original package files
            'Core', // skip original package files
            'Storage/Smarty', // skip temporary files
        ])
        ->notPath([
            'BotDev.php', // skip original package files
            'Client/ClientMessages.php', // fixer don't understand template, would messing up sapcing
        ])
    )
;