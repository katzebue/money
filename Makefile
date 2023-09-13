install-tools:
	composer i --working-dir=tools/php-cs-fixer
	composer i --working-dir=tools/psalm
	composer i --working-dir=tools/rector

code-fix:
	tools/rector/vendor/bin/rector process
	tools/psalm/vendor/bin/psalm --alter --no-cache --safe-types --issues=MissingReturnType
	tools/php-cs-fixer/vendor/bin/php-cs-fixer fix

code-check:
	tools/rector/vendor/bin/rector process --dry-run
	tools/psalm/vendor/bin/psalm --no-cache
