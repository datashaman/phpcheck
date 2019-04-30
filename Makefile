test: phpcheck-no-defects phpunit

watch:
	while inotifywait -e close_write -r composer.* ./src ./checks ./functions ./tests; do make phpunit phpcheck; done

watch-playground:
	while inotifywait -e close_write -r composer.* ./src ./checks ./functions ./playground ./tests; do php playground/test.php; done

docs-build:
	php sami.phar update sami.config.php

docs-clean:
	rm -rf build/ cache/

docs-functions:
	mkdir -p build/docs resources/json/
	cp resources/views/functions.html build/docs/
	webpack --mode=development

docs-functions-regenerate:
	mkdir -p build/docs resources/json/
	cp resources/views/functions.html build/docs/
	./generate-functions > resources/json/functions.json
	webpack --mode=development

phpcheck:
	@phpcheck

phpcheck-coverage:
	sudo phpenmod xdebug
	@phpcheck --coverage-html report/phpcheck-coverage
	sudo phpdismod xdebug
	@xdg-open report/phpcheck-coverage/index.html

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

phpunit:
	@phpunit

phpunit-coverage:
	sudo phpenmod xdebug
	@phpunit --coverage-html report/phpunit-coverage
	sudo phpdismod xdebug
	@xdg-open report/phpunit-coverage/index.html

profile:
	sudo phpenmod xdebug
	# @php -d xdebug.profiler_enable=1 `which phpunit`
	@php -d xdebug.profiler_enable=1 `which phpcheck`
	sudo phpdismod xdebug

webpack-development:
	webpack --mode=development

webpack-production:
	webpack --mode=production

webpack-watch:
	webpack --mode=development --watch
