#!/bin/bash
set -e

COMPOSE_FILE="$DEPLOY_DIR/releases/test/docker-compose.prod.yaml"
ENV_FILE="$DEPLOY_DIR/.deploy-env-test"

cat > "$ENV_FILE" << EOF
APP_NAME=${APP_NAME}
SLOT=test
TRAEFIK_NETWORK=${TRAEFIK_NETWORK}
EOF

function changeOwnership {
    sudo chown "${CONTAINER_EXEC_USER_ID:-1000}:${CONTAINER_EXEC_GROUP_ID:-1000}" -R $DEPLOY_DIR/releases/test

    if [ -d "${DEPLOY_DIR}/releases/shared" ]; then
        echo "Меняем права на файлы в директории shared..."
        sudo chown -R "${CONTAINER_EXEC_USER_ID:-1000}:${CONTAINER_EXEC_GROUP_ID:-1000}" "${DEPLOY_DIR}/releases/shared"
    else
        echo "Warning: shared directory not found at ${DEPLOY_DIR}/releases/shared"
    fi
}

function buildApp {
    docker compose \
        --env-file "$ENV_FILE" \
        -p "${APP_NAME}-test" \
        -f "$COMPOSE_FILE" \
        --profile app_test up -d --build --force-recreate

    docker exec --user root "${APP_NAME}-test-app" \
        chown -R "${CONTAINER_EXEC_USER_ID:-1000}:${CONTAINER_EXEC_GROUP_ID:-1000}" /var/cache/composer

    docker exec "${APP_NAME}-test-app" \
        composer install --no-interaction --optimize-autoloader

    docker exec "${APP_NAME}-test-app" \
        php bin/console lexik:jwt:generate-keypair --overwrite

    docker exec "${APP_NAME}-test-app" \
        php bin/console doctrine:database:create --env=test --if-not-exists

    docker exec "${APP_NAME}-test-app" \
        php bin/console doctrine:migrations:migrate --env=test --no-interaction
}

function runTests {
    docker exec "${APP_NAME}-test-app" \
        bash -c "mkdir -p var/test-results && php vendor/bin/phpunit --log-junit var/test-results/junit.xml"
}

function teardown {
    docker compose \
        --env-file "$ENV_FILE" \
        -p "${APP_NAME}-test" \
        -f "$COMPOSE_FILE" \
        --profile app_test down --remove-orphans -v

    rm -f "$ENV_FILE"
}

changeOwnership
buildApp
runTests
teardown
