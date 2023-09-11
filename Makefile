code-fix:
	vendor/bin/rector process
	tools/php-cs-fixer/vendor/bin/php-cs-fixer fix

code-check:
	vendor/bin/rector process --dry-run
