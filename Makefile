lint:
	docker run --volume ${PWD}:/project \
		--rm jakzal/phpqa \
		phpcs \
		-p \
		--report=checkstyle \
		--standard=PSR12 \
		/project/module/Application/src \
		--warning-severity=0

build:
	docker-compose build membrane

scan:
	trivy --exit-code 0 --severity MEDIUM,HIGH membrane-app:latest
	trivy --exit-code 1 --severity CRITICAL membrane-app:latest

inspec:
	docker-compose up -d membrane
	inspec exec docker/tests -t docker://opg-sirius-membrane_membrane_1 --reporter cli junit:test-output/inspec.xml
	$(MAKE) down

unit:
	docker-compose run --rm membrane \
		vendor/bin/phpunit \
		--configuration=tests/phpunit.xml \
		--exclude-group=functional \
		--testsuite=unit \
		--log-junit=/output/unit.xml

unit-coverage:
	docker-compose run --rm membrane \
		sh -c \
		"pecl install pcov; \
		docker-php-ext-enable pcov; \
		vendor/bin/phpunit \
		--configuration=tests/phpunit.xml \
		--exclude-group=functional \
		--testsuite=unit \
		--log-junit=/output/unit.xml \
		--coverage-clover=/output/clover.xml \
		--coverage-html=/output/unit/"


functional: functional-setup functional-test

functional-setup:
	docker-compose build --parallel api wait-for-it
	docker-compose up -d api localstack postgres
	docker-compose run --rm wait-for-it -address postgres:5432 --timeout=30 -debug
	docker-compose run --rm membrane php public/index.php orm:schema-tool:drop --force --full-database --no-interaction
	docker-compose run --rm membrane php public/index.php migrations:migrate --no-interaction
	docker-compose run --rm membrane php public/index.php data-fixture:import --append
	docker-compose exec -T localstack bash -c '. /scripts/wait/wait.sh'

functional-test:
	docker-compose run --rm membrane \
		vendor/bin/phpunit \
      	--configuration \
      	/var/www/tests/phpunit.xml \
      	--testsuite=functional \
      	--process-isolation \
      	--log-junit \
      	/output/functional.xml

cleanup:
	docker rm --force membrane-app

down:
	docker-compose down
