code-fix:
	tools/rector/vendor/bin/rector process
	tools/psalm/vendor/bin/psalm --alter --no-cache --safe-types --issues=MissingReturnType
	tools/php-cs-fixer/vendor/bin/php-cs-fixer fix

code-check:
	tools/rector/vendor/bin/rector process --dry-run
	tools/psalm/vendor/bin/psalm --no-cache
