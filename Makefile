phpcheck:
	@phpcheck

phpcheck-coverage-html:
	@phpcheck --coverage-html build/coverage
	@xdg-open build/coverage/index.html

phpcheck-coverage-console:
	@phpcheck --coverage-text

phpcheck-coverage-text:
	@phpcheck --coverage-text build/coverage.txt

phpcheck-log-junit:
	@phpcheck --log-junit build/phpcheck.xml

phpcheck-log-text:
	@phpcheck --log-text build/phpcheck.txt

phpcheck-no-defects:
	@phpcheck -d

php-cs-fixer:
	php-cs-fixer fix --using-cache=no

phpmd:
	@phpmd src,checks text phpmd.xml

phpstan:
	@phpstan analyse src checks --level 5
