phpcheck:
	@phpcheck

phpcheck-no-defects:
	@phpcheck -d

phpmd:
	@phpmd src,checks text phpmd.xml

phpstan:
	@phpstan analyse src checks --level 5
