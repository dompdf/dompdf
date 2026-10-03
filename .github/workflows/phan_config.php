<?php

/**
 * This configuration will be read and overlaid on top of the
 * default configuration. Command line arguments will be applied
 * after this file is read.
 */
return [
    // 'processes' => 6,
    'backward_compatibility_checks' => false,
    'simplify_ast'=>true,
    'analyzed_file_extensions' => ['php','inc'],

    // Supported values: `'5.6'`, `'7.0'`, `'7.1'`, `'7.2'`, `'7.3'`, `'7.4'`, `null`.
    // If this is set to `null`,
    // then Phan assumes the PHP version which is closest to the minor version
    // of the php executable used to execute Phan.
    //"target_php_version" => null,
    "target_php_version" => '8.2',
    //"target_php_version" => '7.3',
    //"target_php_version" => '5.6',

    // A list of directories that should be parsed for class and
    // method information. After excluding the directories
    // defined in exclude_analysis_directory_list, the remaining
    // files will be statically analyzed for errors.
    //
    // Thus, both first-party and third-party code being used by
    // your application should be included in this list.
    'directory_list' => [
        'src',
        'vendor',
    ],

    // A directory list that defines files that will be excluded
    // from static analysis, but whose class and method
    // information should be included.
    //
    // Generally, you'll want to include the directories for
    // third-party code (such as "vendor/") in this list.
    //
    // n.b.: If you'd like to parse but not analyze 3rd
    //       party code, directories containing that code
    //       should be added to the `directory_list` as
    //       to `exclude_analysis_directory_list`.
    "exclude_analysis_directory_list" => [
        'vendor',
    ],
    //'exclude_file_regex' => '@^vendor/.*/(tests?|Tests?)/@',
    'exclude_file_regex' => '@^('
        .')$@',



    // A list of plugin files to execute.
    // Plugins which are bundled with Phan can be added here by providing their name
    // (e.g. 'AlwaysReturnPlugin')
    //
    // Documentation about available bundled plugins can be found
    // at https://github.com/phan/phan/tree/master/.phan/plugins
    //
    // Alternately, you can pass in the full path to a PHP file
    // with the plugin's implementation (e.g. 'vendor/phan/phan/.phan/plugins/AlwaysReturnPlugin.php')
    'plugins' => [
        // checks if a function, closure or method unconditionally returns.
        // can also be written as 'vendor/phan/phan/.phan/plugins/AlwaysReturnPlugin.php'
        //'DeprecateAliasPlugin',
        //'EmptyMethodAndFunctionPlugin',
        'InvalidVariableIssetPlugin',
        //'MoreSpecificElementTypePlugin',
        'NoAssertPlugin',
        'NotFullyQualifiedUsagePlugin',
        //'PHPDocRedundantPlugin',
        'PHPUnitNotDeadCodePlugin',
        //'PossiblyStaticMethodPlugin',
        'PreferNamespaceUsePlugin',
        'PrintfCheckerPlugin',
        'RedundantAssignmentPlugin',
        // PhanPluginCanUseParamType : 1300+ occurrences
        // PhanPluginComparisonNotStrictForScalar : 700+ occurrences
        // PhanPluginCanUseReturnType : 680+ occurrences
        // PhanPluginNumericalComparison : 470+ occurrences
        // PhanPluginNonBoolInLogicalArith : 290+ occurrences
        // PhanPluginPossiblyStaticClosure : 270+ occurrences
        // PhanPluginPossiblyStaticPublicMethod : 230+ occurrences
        // PhanPluginSuspiciousParamPosition : 150+ occurrences
        // PhanPluginCanUseNullableParamType : 140+ occurrences
        // PhanPluginCanUsePHP71Void : 130+ occurrences
        // PhanPluginInlineHTML : 100+ occurrences
        // PhanPluginPossiblyStaticPrivateMethod : 100+ occurrences
        // PhanPluginCanUseNullableReturnType : 90+ occurrences
        // PhanPluginInlineHTMLTrailing : 65+ occurrences

        'PHPDocToRealTypesPlugin',
        'PHPDocInWrongCommentPlugin', // Missing /** (/* was used)
        /* Could be enabled for new code.
        'ConstantVariablePlugin', // Warns about values that are actually constant 
        'HasPHPDocPlugin', // Requires PHPDoc
        'InlineHTMLPlugin', // html in PHP file, or at end of file
        'NonBoolBranchPlugin', // Requires test on bool, nont on ints
        'NonBoolInLogicalArithPlugin',
        'NumericalComparisonPlugin',
        'ShortArrayPlugin', // Checks that [] is used
        'StrictLiteralComparisonPlugin',
        'UnknownClassElementAccessPlugin',
        'UnknownElementTypePlugin',
        'WhitespacePlugin',
        /**/
        //'RemoveDebugStatementPlugin', // Reports echo, print, ...
        //'SimplifyExpressionPlugin',
        //'StrictComparisonPlugin', // Expects ===
        //'SuspiciousParamOrderPlugin', // reports function calls for parameters, not clear
        'UnsafeCodePlugin',
        //'UnusedSuppressionPlugin',

        'AlwaysReturnPlugin',
        //'DollarDollarPlugin',
        'DuplicateArrayKeyPlugin',
        'DuplicateExpressionPlugin',
        'PregRegexCheckerPlugin',
        'PrintfCheckerPlugin',
        'SleepCheckerPlugin',
        // Checks for syntactically unreachable statements in
        // the global scope or function bodies.
        'UnreachableCodePlugin',
        'UseReturnValuePlugin',
        'EmptyStatementListPlugin',
        'LoopVariableReusePlugin',
    ],

    // Add any issue types (such as 'PhanUndeclaredMethod')
    // here to inhibit them from being reported
    'suppress_issue_types' => [
        //'PhanUndeclaredThis',
        'PhanPluginMixedKeyNoKey',
        'PhanPluginDuplicateConditionalNullCoalescing', // Suggests to optimize to ??
        //'PhanUnreferencedClosure',  // False positives seen with closures in arrays, TODO: move closure checks closer to what is done by unused variable plugin
        //'PhanPluginNoCommentOnProtectedMethod',
        //'PhanPluginDescriptionlessCommentOnProtectedMethod',
        //'PhanPluginNoCommentOnPrivateMethod',
        //'PhanPluginDescriptionlessCommentOnPrivateMethod',
        //'PhanPluginDescriptionlessCommentOnPrivateProperty',
        // TODO: Fix edge cases in --automatic-fix for PhanPluginRedundantClosureComment
        //'PhanPluginRedundantClosureComment',
        'PhanPluginPossiblyStaticPublicMethod',
        //'PhanPluginPossiblyStaticProtectedMethod',

        // The types of ast\Node->children are all possibly unset.
        'PhanTypePossiblyInvalidDimOffset', // Also checks optional array keys and requires that they are checked for existence.
        'PhanUndeclaredGlobalVariable',
        'PhanUndeclaredProperty',
        'PhanPluginPrintfNotPercent',
        'PhanPossiblyUndeclaredGlobalVariable',
        'PhanPluginPossiblyStaticProtectedMethod',
        'PhanUndeclaredThis',
        'PhanTypeMismatchReturn',
        'PhanPluginMoreSpecificActualReturnType',
        'PhanTypeMismatchReturnProbablyReal',
        'PhanPossiblyUndeclaredVariable',
        'PhanTypeMismatchArgument',
        //'PhanPluginUnreachableCode',
        //'PhanTypeMismatchArgumentInternal',
        //'PhanPluginAlwaysReturnMethod',
        'PhanUndeclaredClassMethod',
        'PhanUndeclaredMethod',
        'PhanTypeMismatchArgumentProbablyReal',
        'PhanPluginDuplicateExpressionAssignmentOperation',
        'PhanTypeMismatchPropertyDefault',
        'PhanPluginAlwaysReturnMethod',
        'PhanPluginMissingReturnMethod',
        'PhanUndeclaredTypeReturnType',
        'PhanUndeclaredClassProperty',
        'PhanTypeArraySuspiciousNullable',
        'PhanPluginInconsistentReturnMethod',
        'PhanTypeExpectedObjectPropAccessButGotNull',
        'PhanUndeclaredClassAttribute',
        'PhanNonClassMethodCall',
        'PhanPluginNoAssert',
        'PhanTypeMismatchReturnSuperType',
        'PhanTypeMismatchArgumentSuperType',
        'PhanPluginDuplicateConditionalTernaryDuplication',
    ],
    // You can put relative paths to internal stubs in this config option.
    // Phan will continue using its detailed type annotations,
    // but load the constants, classes, functions, and classes (and their Reflection types)
    // from these stub files (doubling as valid php files).
    // Use a different extension from php (and preferably a separate folder)
    // to avoid accidentally parsing these as PHP (includes projects depending on this).
    // The 'mkstubs' script can be used to generate your own stubs (compatible with php 7.0+ right now)
    // Note: The array key must be the same as the extension name reported by `php -m`,
    // so that phan can skip loading the stubs if the extension is actually available.
    'autoload_internal_extension_signatures' => [
        // Xdebug stubs are bundled with Phan 0.10.1+/0.8.9+ for usage,
        // because Phan disables xdebug by default.
        //'xdebug'     => 'vendor/phan/phan/.phan/internal_stubs/xdebug.phan_php',
        //'memcached'  => '.phan/your_internal_stubs_folder_name/memcached.phan_php',
        // 'PDO'  => '.github/workflows/phan_stubs/PDO.phan_php',
        // 'curl'  => '.github/workflows/phan_stubs/curl.phan_php',
        // 'fileinfo'  => '.github/workflows/phan_stubs/fileinfo.phan_php',
        // 'intl'  => '.github/workflows/phan_stubs/intl.phan_php',
        // 'mcrypt'  => '.github/workflows/phan_stubs/mcrypt.phan_php',
        // 'memcache'  => '.github/workflows/phan_stubs/memcache.phan_php',
        // 'pdo_cubrid'  => '.github/workflows/phan_stubs/pdo_cubrid.phan_php',
        // 'pdo_mysql'  => '.github/workflows/phan_stubs/pdo_mysql.phan_php',
        // 'pdo_pgsql'  => '.github/workflows/phan_stubs/pdo_pgsql.phan_php',
        // 'pdo_sqlite'  => '.github/workflows/phan_stubs/pdo_sqlite.phan_php',
        // 'session'  => '.github/workflows/phan_stubs/session.phan_php',
        // 'soap'  => '.github/workflows/phan_stubs/soap.phan_php',
        // DOM extension
        'dom'  => '.github/workflows/phan_stubs/dom.phan_php',
        // MBString extension
        'mbstring'  => '.github/workflows/phan_stubs/mbstring.phan_php',
        // php-font-lib
        // php-svg-lib
        // opcache
        // gd
        'gd'  => '.github/workflows/phan_stubs/gd.phan_php',
        // imagick
        'imagick'  => '.github/workflows/phan_stubs/imagick.phan_php',
        'gmagick'  => '.github/workflows/phan_stubs/gmagick.phan_php',
        'json'  => '.github/workflows/phan_stubs/json.phan_php',
    ],

];
