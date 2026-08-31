#!/bin/bash
source /root/.bashrc
set -euo pipefail
cd /src/

vendor/bin/phpunit -c dev/phpunit/functional/phpunit.xml --group="v${ID#m}" --debug $PHPUNIT_ARGS
