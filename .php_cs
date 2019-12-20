<?php

$finder = PhpCsFixer\Finder::create()
	->in(__DIR__ . '/src')
;

return PhpCsFixer\Config::create()
//	->setUsingCache(false)
	->setRiskyAllowed(true)
	->setRules([
		'@PSR2' => true,
		'@PhpCsFixer' => true,
		'@PHP70Migration' => true,
		'@PHP70Migration:risky' => true,
		'@PHP71Migration' => true,
		'@PHP71Migration:risky' => true,

		'no_php4_constructor' => true,
		'phpdoc_add_missing_param_annotation' => true,
		'no_superfluous_phpdoc_tags' => false,
		'strict_param' => true,
	])
	->setFinder($finder);
