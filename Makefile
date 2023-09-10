code-fix:
	vendor/bin/rector process

code-check:
	vendor/bin/rector process --dry-run
