phpcheck:
	@phpcheck

phpcheck-no-defects:
	@phpcheck -d

php-cs-fixer:
	php-cs-fixer fix --using-cache=no

phpmd:
	@phpmd src,checks text phpmd.xml

phpstan:
	@phpstan analyse src checks --level 5
