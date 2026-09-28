<?php

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setRules([
        'statement_indentation' => true,
        'no_extra_blank_lines' => true,
        'no_whitespace_in_blank_line' => true,
    ]);
