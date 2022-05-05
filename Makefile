## Show this help
help:
	echo "$(EMOJI_interrobang) Makefile version $(VERSION) help "
	echo ''
	echo 'About this help:'
	echo '  Commands are ${BLUE}blue${RESET}'
	echo '  Targets are ${YELLOW}yellow${RESET}'
	echo '  Descriptions are ${GREEN}green${RESET}'
	echo ''
	echo 'Usage:'
	echo '  ${BLUE}make${RESET} ${YELLOW}<target>${RESET}'
	echo ''
	echo 'Targets:'
	awk '/^[a-zA-Z\-\_0-9]+:/ { \
		helpMessage = match(lastLine, /^## (.*)/); \
		if (helpMessage) { \
			helpCommand = substr($$1, 0, index($$1, ":")+1); \
			helpMessage = substr(lastLine, RSTART + 3, RLENGTH); \
			printf "  ${YELLOW}%-${TARGET_MAX_CHAR_NUM}s${RESET} ${GREEN}%s${RESET}\n", helpCommand, helpMessage; \
		} \
	} \
	{ lastLine = $$0 }' $(MAKEFILE_LIST)

## Creates a backup of the database
.mysql-dump:
	echo "$(EMOJI_floppy_disk) Dumping the database"
	mkdir -p $(SQLDUMPSDIR)
	docker-compose exec -u 1000:1000 mysql bash -c "mysqldump -u$(MYSQL_USER) -p$(MYSQL_PASSWORD) --add-drop-database --create-options --extended-insert --no-autocommit --quick --default-character-set=utf8 $(MYSQL_DATABASE) | gzip > /$(SQLDUMPSDIR)/$(SQLDUMPFILE)"

## Wait for the mysql container to be fully provisioned
.mysql-wait:
		echo "$(EMOJI_ping_pong) Checking DB up and running"
		while ! docker-compose exec mysql mysql -u$(MYSQL_USER) -p$(MYSQL_PASSWORD) $(MYSQL_DATABASE) -e "SELECT 1;" &> /dev/null; do \
				echo "$(EMOJI_face_with_rolling_eyes) Waiting for database ..."; \
				sleep 1; \
		done;

## Restores the database from the backup file defined in .env
mysql-restore: .mysql-wait
	echo "$(EMOJI_robot) Restoring the database"
	docker-compose exec mysql bash -c 'DUMPFILE="/$(SQLDUMPSDIR)/$(SQLDUMPFILE)"; if [[ "$${DUMPFILE##*.}" == "sql" ]]; then cat $$DUMPFILE; else zcat $$DUMPFILE; fi | mysql --default-character-set=utf8 -u$(MYSQL_USER) -p$(MYSQL_PASSWORD) $(MYSQL_DATABASE)'
	docker-compose exec mysql bash -c 'cat /$(SQLDUMPSDIR)/local_setup.sql | mysql --default-character-set=utf8 -u$(MYSQL_USER) -p$(MYSQL_PASSWORD) $(MYSQL_DATABASE)'

## Synchronize fileadmin with newer files from the backup server
.rsync-fileadmin:
	echo "$(EMOJI_receive) Synchronizing fileadmin"
	rsync -azl --info=progress2 --exclude '*.zip' --exclude '*.ZIP' --exclude '_processed_' --max-size=2M -e ssh $(SYNCSERVER):$(SYNCFOLDER)/fileadmin/ $(WEBROOT)/fileadmin/

## Synchronize uploads with newer files from the backup server
.rsync-uploads:
	echo "$(EMOJI_receive) Synchronizing uploads"
	rsync -azl --info=progress2 --exclude '*.zip' --exclude '*.ZIP' --exclude '_processed_' --max-size=2M -e ssh $(SYNCSERVER):$(SYNCFOLDER)/uploads/ $(WEBROOT)/uploads/

## Update the database dump file (required for mysql-restore)
.rsync-database:
	echo "$(EMOJI_receive) Downloading newer database dump"
	mkdir -p $(SQLDUMPSDIR)
	rsync -azL --info=progress2 -e ssh $(SYNCSERVER):$(SYNCFOLDER)/$(SQLDUMPFILE) $(SQLDUMPSDIR)/$(SQLDUMPFILE)

## Stop all containers
stop:
	echo "$(EMOJI_stop) Shutting down"
	docker-compose stop
	sleep 0.4
ifeq ($(shell uname -s), Darwin)
	sleep 2
endif
	docker-compose down --remove-orphans

## Removes all containers and volumes
destroy: stop
	echo "$(EMOJI_litter) Removing the project"
	docker-compose down -v --remove-orphans

## Starts docker-compose up -d
start: .docker-pull .docker-start
	make urls

## Choose the right docker-compose file for your environment
.link-compose-file:
	echo "$(EMOJI_triangular_ruler) Linking the OS specific compose file"
ifeq ($(shell uname -s), Darwin)
	ln -snf .project/docker/docker-compose.darwin.yml docker-compose.yml
else
	ln -snf .project/docker/docker-compose.unix.yml docker-compose.yml
endif

## Starts composer-install
composer-install:
	echo "$(EMOJI_package) Installing composer dependencies"
	docker-compose exec php composer install

## Starts phive install
phive-install:
	echo "$(EMOJI_wrapped_gift) Installing phars with phive"
	[ -h docker-compose.yml ] || make .link-compose-file
	CMD="phive install --trust-gpg-keys 31C7E470E2138192,0F9684B8B16B7AB0,60B7CDE72C913795"; [ -f /.dockerenv ] && $$CMD || docker-compose run -w /app php $$CMD

## Create necessary directories
.create-dirs:
	echo "$(EMOJI_dividers) Creating required directories"
	mkdir -p $$HOME/.phive
	mkdir -p .Build/$(TYPO3_CACHE_DIR)
	mkdir -p $(SQLDUMPSDIR)
	mkdir -p build/prototype/HTML-Prototype

## Starts composer-install for production
.composer-install-production:
	echo "$(EMOJI_package) Installing composer dependencies (without dev)"
	docker-compose exec php composer install --no-dev -ao

## Install mkcert on this computer, skips installation if already present
.install-mkcert:
	if [[ "$$OSTYPE" == "linux-gnu" ]]; then \
		if [[ "$$(command -v certutil > /dev/null; echo $$?)" -ne 0 ]]; then sudo apt install libnss3-tools; fi; \
		if [[ "$$(command -v mkcert > /dev/null; echo $$?)" -ne 0 ]]; then sudo curl -L https://github.com/FiloSottile/mkcert/releases/download/v1.4.1/mkcert-v1.4.1-linux-amd64 -o /usr/local/bin/mkcert; sudo chmod +x /usr/local/bin/mkcert; fi; \
	elif [[ "$$OSTYPE" == "darwin"* ]]; then \
	    BREW_LIST=$$(brew ls --formula); \
		if [[ ! $$BREW_LIST == *"mkcert"* ]]; then brew install mkcert; fi; \
		if [[ ! $$BREW_LIST == *"nss"* ]]; then brew install nss; fi; \
	fi;
	mkcert -install > /dev/null

## Create SSL certificates for dinghy and starting project
.create-certificate: .install-mkcert
	echo "$(EMOJI_secure) Creating SSL certificates for dinghy http proxy"
	mkdir -p $(HOME)/.dinghy/certs/
	PROJECT=$$(echo "$${PWD##*/}" | tr -d '.'); \
	if [[ ! -f $(HOME)/.dinghy/certs/$$PROJECT.docker.key ]]; then mkcert -cert-file $(HOME)/.dinghy/certs/$$PROJECT.docker.crt -key-file $(HOME)/.dinghy/certs/$$PROJECT.docker.key "*.$$PROJECT.docker"; fi;
	if [[ ! -f $(HOME)/.dinghy/certs/${WEB_HOST}.key ]]; then mkcert -cert-file $(HOME)/.dinghy/certs/${WEB_HOST}.crt -key-file $(HOME)/.dinghy/certs/${WEB_HOST}.key ${WEB_HOST}; fi;
	if [[ ! -f $(HOME)/.dinghy/certs/${MAIL_HOST}.key ]]; then mkcert -cert-file $(HOME)/.dinghy/certs/${MAIL_HOST}.crt -key-file $(HOME)/.dinghy/certs/${MAIL_HOST}.key ${MAIL_HOST}; fi;
	if [[ ! -f $(HOME)/.dinghy/certs/${NODE_HOST}.key ]]; then mkcert -cert-file $(HOME)/.dinghy/certs/${NODE_HOST}.crt -key-file $(HOME)/.dinghy/certs/${NODE_HOST}.key ${NODE_HOST}; fi;

## Try to login to the docker registry. Will not ask for credentials if valid login is already persisted. Skipped if no registry is defined.
.docker-login:
	[ -z "${DOCKER_REGISTRY}" ] || ( \
		echo "$(EMOJI_eyes) Logging in to gitlab.in2code.de:5050"; \
		docker login gitlab.in2code.de:5050 \
	)

## Update all images related to this project
.docker-pull:
	echo "$(EMOJI_fishing_pole) Updating all docker images for this project"
	docker-compose pull
	docker-compose build --pull

## Start the project
.docker-start:
	echo "$(EMOJI_musical_score) Starting the docker compose project"
	docker-compose up -d

## Set correct onwership of mounts. Docker creates mounts owned by root:root.
.fix-mount-perms:
	echo "$(EMOJI_rocket) Fixing docker mount permissions"
	docker-compose exec -u root php chown -R app:app /app/.Build/$(TYPO3_CACHE_DIR)/;

## Copies the Additional/DockerConfiguration.php to the correct directory
.typo3-add-dockerconfig:
	echo "$(EMOJI_plug) Copying the docker specific configuration for TYPO3"
	mkdir -p $(WEBROOT)/typo3conf/AdditionalConfiguration
	ln -snf ../../../../.project/TYPO3/DockerConfiguration.php $(WEBROOT)/typo3conf/AdditionalConfiguration/DockerConfiguration.php

## Starts the TYPO3 Databasecompare
typo3-comparedb:
	echo "$(EMOJI_leftright) Running database:updateschema"
	docker-compose exec php ./.Build/vendor/bin/typo3cms database:updateschema

## Starts the TYPO3 setup process
.typo3-setupinstall:
	echo "$(EMOJI_upright) Running install:setup"
	docker-compose exec php ./.Build/vendor/bin/typo3cms install:setup

## Clears TYPO3 caches via typo3-console
typo3-clearcache:
	echo "$(EMOJI_broom) Clearing TYPO3 caches"
	docker-compose exec php ./.Build/vendor/bin/typo3cms cache:flush

## To start an existing project incl. rsync from fileadmin, uploads and database dump
install-project: .link-compose-file stop .add-hosts-entry .create-dirs .create-certificate .docker-login .docker-pull .docker-start .fix-mount-perms composer-install .typo3-add-dockerconfig typo3-comparedb .print-online

## To start a new project
new-project: .link-compose-file destroy .add-hosts-entry .create-dirs .create-certificate .docker-login .docker-pull .docker-start .fix-mount-perms composer-install phive-install npm-install .typo3-add-dockerconfig .typo3-setupinstall typo3-comparedb .setup-git-hooks  .print-online

## Outputs the success message that the project is online
.print-online:
	echo "---------------------"
	echo ""
	echo "The project is online $(EMOJI_thumbsup)"
	echo ""
	echo 'Stop the project with "make stop"'
	echo ""
	echo "---------------------"
	make urls

## Print Project URIs
urls:
	echo "$(EMOJI_telescope) Project URLs:";
	echo '';
	printf "  %-10s %s\n" "Frontend:" "https://$(WEB_HOST)/";
	printf "  %-10s %s\n" "Backend:" "https://$(WEB_HOST)/typo3/";
	printf "  %-10s %s\n" "Mail:" "https://$(MAIL_HOST)/";
	printf "  %-10s %s\n" "Node:" "https://$(NODE_HOST)/";

## Create the hosts entry for the custom project URL (non-dinghy convention)
.add-hosts-entry:
	echo "$(EMOJI_monkey) Creating Hosts Entry (if not set yet)"
	SERVICES=$$(command -v getent > /dev/null && echo "getent ahostsv4" || echo "dscacheutil -q host -a name"); \
	if [ ! "$$($$SERVICES $(WEB_HOST) | grep 127.0.0.1 > /dev/null; echo $$?)" -eq 0 ]; then sudo bash -c 'echo "127.0.0.1 $(WEB_HOST) $(MAIL_HOST) $(NODE_HOST)" >> /etc/hosts; echo "Entry was added"'; else echo 'Entry already exists'; fi;

## Log into the PHP container
login-php:
	echo "$(EMOJI_elephant) Logging in into the PHP container"
	docker-compose exec php bash

## Log into the mysql container
login-mysql:
	echo "$(EMOJI_dolphin) Logging in into MySQL container"
	docker-compose exec mysql bash

## Log into the node container
login-node:
	echo "$(EMOJI_package) Logging in into Node container"
	docker-compose exec -unode node bash

## Sets up pre-commit hook
.setup-git-hooks:
	echo "$(EMOJI_nutandbolt) Setting up git pre-commit hook"
	git config core.hooksPath .project/githooks
	chmod ug+x .project/githooks/*

## Run all QA tasks
qa: qa-php

## Run all PHP specific QA tasks
qa-php: qa-phpcs qa-phpmd

## Run the PHP Code Sniffer QA tasks
qa-phpcs:
	[ -h .project/phars/phpcs ] || make phive-install
	[ -h docker-compose.yml ] || make .link-compose-file
	CMD=".project/phars/phpcs"; [ -f /.dockerenv ] && $$CMD || docker-compose run -w /app php $$CMD

## Run the PHP Mess Detector QA tasks
qa-phpmd:
	[ -h .project/phars/phpmd ] || make phive-install
	[ -h docker-compose.yml ] || make .link-compose-file
	CMD=".project/phars/phpmd app/packages/ ansi .phpmd.xml"; [ -f /.dockerenv ] && $$CMD || docker-compose run -w /app php $$CMD

qa-typoscriptlint:
	[ -h .project/phars/typoscript-lint ] || make phive-install
	[ -h docker-compose.yml ] || make .link-compose-file
	CMD=".project/phars/typoscript-lint "; [ -f /.dockerenv ] && $$CMD || docker-compose run -w /app php $$CMD

## Fix all phpcs issues which can be fixed automatically
fix-phpcbf:
	[ -h .project/phars/phpcbf ] || make phive-install
	[ -h docker-compose.yml ] || make .link-compose-file
	CMD=".project/phars/phpcbf"; [ -f /.dockerenv ] && $$CMD || docker-compose run -w /app php $$CMD

include .env

# SETTINGS
TARGET_MAX_CHAR_NUM := 25
MAKEFLAGS += --silent
SHELL := /bin/bash
VERSION := 1.0.0

# COLORS
RED     := $(shell tput -Txterm setaf 1)
GREEN   := $(shell tput -Txterm setaf 2)
YELLOW  := $(shell tput -Txterm setaf 3)
BLUE    := $(shell tput -Txterm setaf 4)
MAGENTA := $(shell tput -Txterm setaf 5)
CYAN    := $(shell tput -Txterm setaf 6)
WHITE   := $(shell tput -Txterm setaf 7)
RESET   := $(shell tput -Txterm sgr0)

# EMOJIS (some are padded right with whitespace for text alignment)
EMOJI_litter := "🚮️"
EMOJI_interrobang := "⁉️ "
EMOJI_floppy_disk := "💾️"
EMOJI_dividers := "🗂️ "
EMOJI_up := "🆙️"
EMOJI_receive := "📥️"
EMOJI_robot := "🤖️"
EMOJI_stop := "🛑️"
EMOJI_package := "📦️"
EMOJI_secure := "🔐️"
EMOJI_explodinghead := "🤯️"
EMOJI_rocket := "🚀️"
EMOJI_plug := "🔌️"
EMOJI_leftright := "↔️ "
EMOJI_upright := "↗️ "
EMOJI_thumbsup := "👍️"
EMOJI_telescope := "🔭️"
EMOJI_monkey := "🐒️"
EMOJI_elephant := "🐘️"
EMOJI_dolphin := "🐬️"
EMOJI_helicopter := "🚁️"
EMOJI_broom := "🧹"
EMOJI_nutandbolt := "🔩"
EMOJI_crystal_ball := "🔮"
EMOJI_triangular_ruler := "📐"
EMOJI_ping_pong := "🏓"
EMOJI_face_with_rolling_eyes := "🙄"
EMOJI_eyes := "👀"
EMOJI_fire := "🔥"
EMOJI_runningshirt := "🎽"
EMOJI_evergreen_tree := "🌲"
EMOJI_luggage := "🧳"
EMOJI_fishing_pole := "🎣"
EMOJI_musical_score := "🎼"
EMOJI_nerd_face := "🤓"
EMOJI_digit_zero := "0️"
EMOJI_digit_one := "1️"
EMOJI_digit_two := "2️"
EMOJI_digit_three := "3️"
EMOJI_digit_four := "4️"
EMOJI_digit_seven := "7️"
EMOJI_pig_nose := "🐽"
EMOJI_customs := "🛃"
EMOJI_hot_face := "🥵"
EMOJI_cold_face := "🥶"
EMOJI_hourglass_not_done := "⏳"
EMOJI_wrvendor/bin/ed_gift := "🎁"
