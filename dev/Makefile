list: 					        # Lists all available commands
	printf "\n"; grep -v -e "^\t" Makefile | grep . | grep -v ENV | awk -F":.+?#" '{ print $$1 " #" $$2 }' | column -t -s '#';

ENV='provide_ENV_var'
PHPUNIT_ARGS=''
SYNC_PATHS :=
DELETE_PATHS :=

docker-up:       # Launch docker container
	docker compose --file=./Docker/docker-compose.yml up --remove-orphans -d $(ENV)
	sleep 5

docker-install:  # Prepare the 'manual' steps for running this tool
	docker exec uphelper-$(ENV) ./dev/Docker/setup-magento-with-diff.sh

docker-test:     # Run tests inside container
	docker exec -e PHPUNIT_ARGS='$(PHPUNIT_ARGS)'  uphelper-$(ENV) ./dev/Docker/run-phpunit-functional-tests.sh

docker-sync-fixtures: # Sync named existing or new fixture files into an already-running container
	@test "$(ENV)" != "provide_ENV_var" || (echo "Set ENV, for example ENV=m24"; exit 2)
	@test -n "$(SYNC_PATHS)$(DELETE_PATHS)" || (echo "Set SYNC_PATHS and/or DELETE_PATHS"; exit 2)
	@test "$$(docker inspect -f '{{.State.Running}}' uphelper-$(ENV) 2>/dev/null)" = "true" || (echo "uphelper-$(ENV) must already be running"; exit 2)
	docker exec -e SYNC_PATHS='$(SYNC_PATHS)' -e DELETE_PATHS='$(DELETE_PATHS)' uphelper-$(ENV) ./dev/Docker/sync-test-fixtures.sh

docker-stop-all-containers:     # Stop all upgrade helper docker containers
	docker container stop uphelper-m24nodb || true
	docker container stop uphelper-m24nodbnohyva || true
	docker container stop uphelper-m2451nodb || true
	docker container stop uphelper-m24 || true
	docker container stop uphelper-m23 || true
	docker container stop uphelper-mysql57 || true
	docker container stop uphelper-elasticsearch || true
	docker container stop uphelper-elasticsearch8 || true
	docker container rm uphelper-m24nodb || true
	docker container rm uphelper-m24nodbnohyva || true
	docker container rm uphelper-m2451nodb || true
	docker container rm uphelper-m24 || true
	docker container rm uphelper-m23 || true
	docker container rm uphelper-mysql57 || true
	docker container rm uphelper-elasticsearch || true
	docker container rm uphelper-elasticsearch8 || true
	docker volume rm docker_excludedvol || true
