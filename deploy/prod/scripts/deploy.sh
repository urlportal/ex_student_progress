#!/bin/bash
set -e

COMPOSE_FILE="$DEPLOY_DIR/releases/$CURRENT/docker-compose.prod.yaml"
ENV_FILE="$DEPLOY_DIR/.deploy-env"

function writeEnvFile {
    cat > "$ENV_FILE" << EOF
APP_ENV=${APP_ENV}
APP_NAME=${APP_NAME}
SLOT=${CURRENT}
TRAEFIK_NETWORK=${TRAEFIK_NETWORK}
EOF
}

function changeOwnership {
    sudo chown "${CONTAINER_EXEC_USER_ID:-1000}:${CONTAINER_EXEC_GROUP_ID:-1000}" -R $DEPLOY_DIR/releases/$CURRENT

    if [ -d "${DEPLOY_DIR}/releases/shared" ]; then
        echo "Меняем права на файлы в директории shared..."
        sudo chown -R "${CONTAINER_EXEC_USER_ID:-1000}:${CONTAINER_EXEC_GROUP_ID:-1000}" "${DEPLOY_DIR}/releases/shared"
    else
        echo "Warning: shared directory not found at ${DEPLOY_DIR}/releases/shared"
    fi
}

function ensureInfraRunning {
    if ! docker ps -q -f "name=${APP_NAME}-postgres" | grep -q .; then
        echo "[i] Запуск инфраструктурных сервисов (postgres, redis, rabbitmq...)"
        docker compose \
            --env-file "$ENV_FILE" \
            -p "${APP_NAME}-infra" \
            -f "$COMPOSE_FILE" \
            --profile infra up -d
    else
        echo "[i] ИНФРАСТРУКТУРНЫЕ СЕРВИСЫ УЖЕ ЗАПУЩЕНЫ"
    fi
}

wait_for_infra_health() {
    local container_name="$1"
    local max_attempts=10
    local attempt=1

    echo "Ожидаем, когда контейнер '$container_name' будет в рабочем состоянии..."

    while [ $attempt -le $max_attempts ]; do
        # Получаем статус здоровья контейнера
        local status=$(docker inspect --format='{{.State.Health.Status}}' "$container_name" 2>/dev/null)

        if [ "$status" = "healthy" ]; then
            echo "Контейнер '$container_name' is healthy! Можно запускать приложение..."
            return 0
        fi

        echo "Попытка $attempt/$max_attempts: Статус: '$status'. Подождём 5 секунд..."
        sleep 5
        attempt=$((attempt + 1))
    done

    echo "Error: Container '$container_name' failed to become healthy in time."
    return 1
}

function buildApp {
    docker compose \
        --env-file "$ENV_FILE" \
        -p "${APP_NAME}-${CURRENT}" \
        -f "$COMPOSE_FILE" \
        --profile app up -d --build --no-deps --force-recreate app

    docker exec --user root "${APP_NAME}-${CURRENT}-app" \
        chown -R "${CONTAINER_EXEC_USER_ID:-1000}:${CONTAINER_EXEC_GROUP_ID:-1000}" /var/cache/composer

    docker exec "${APP_NAME}-${CURRENT}-app" \
        composer install --no-dev --no-interaction --optimize-autoloader

    if [ -f "${DEPLOY_DIR}/releases/shared/jwt/public.pem" ]; then
        echo "JWT ключи уже есть в shared-директории. Генерация не нужна"
    else
        echo "JWT ключи отсутствуют! Запускаем генерацию 'lexik:jwt:generate-keypair'"
        docker exec "${APP_NAME}-${CURRENT}-app" php bin/console lexik:jwt:generate-keypair
    fi

    docker exec "${APP_NAME}-${CURRENT}-app" \
        php bin/console doctrine:migrations:migrate --no-interaction

    docker exec --user root "${APP_NAME}-${CURRENT}-app" \
        chown -R "${CONTAINER_EXEC_USER_ID}:${CONTAINER_EXEC_GROUP_ID}" /app/var
}

function optimizeResources {
    docker exec "${APP_NAME}-${CURRENT}-app" php bin/console cache:clear
    docker exec "${APP_NAME}-${CURRENT}-app" php bin/console cache:warmup
}

function startCurrentRelease {
    docker compose \
        --env-file "$ENV_FILE" \
        -p "${APP_NAME}-${CURRENT}" \
        -f "$COMPOSE_FILE" \
        --profile app up -d nginx

    echo "Начинаем healthcheck новой сборки (${CURRENT})..."
    local attempt=0
    until docker exec "${APP_NAME}-${CURRENT}-nginx" \
        curl -sf http://"${APP_NAME}-${CURRENT}-nginx"/healthcheck > /dev/null 2>&1; do
        attempt=$((attempt + 1))
        if [ $attempt -ge 30 ]; then
            echo "Healthcheck не прошёл за 60 секунд, деплой прерван"
            exit 1
        fi
        sleep 2
    done
    echo "Успех! Новая сборка (${CURRENT}) готова к переключению трафика на себя..."

    # Атомарное переключение трафика в Traefik.
    sed "s/${APP_NAME}-${PREV}-nginx/${APP_NAME}-${CURRENT}-nginx/" \
        "${TRAEFIK_CONF_DIR}/${APP_NAME}.yaml" \
        > "/tmp/${APP_NAME}-traefik.yaml.tmp"
    sudo mv "/tmp/${APP_NAME}-traefik.yaml.tmp" "${TRAEFIK_CONF_DIR}/${APP_NAME}.yaml"

    echo "-----------------------------"
    echo "| Трафик переключён на ${CURRENT} |"
    echo "-----------------------------"
}

function stopPrevRelease {
    local prev_env_file="$DEPLOY_DIR/.deploy-env-prev"
    cat > "$prev_env_file" << EOF
APP_ENV=${APP_ENV}
APP_NAME=${APP_NAME}
SLOT=${PREV}
TRAEFIK_NETWORK=${TRAEFIK_NETWORK}
EOF

    if [ -f "$DEPLOY_DIR/releases/$PREV/docker-compose.prod.yaml" ]; then
        docker compose \
            --env-file "$prev_env_file" \
            -p "${APP_NAME}-${PREV}" \
            -f "$DEPLOY_DIR/releases/$PREV/docker-compose.prod.yaml" \
            --profile app stop nginx app

        rm -f "$prev_env_file"
        echo "Слот ${PREV} остановлен"
    else
        echo "docker-compose файл отсутствует в предыдущем релизе, поэтому ничего не делаем"
    fi
}

changeOwnership
writeEnvFile
ensureInfraRunning
wait_for_infra_health "${APP_NAME}-rabbitmq" || exit 1
buildApp
optimizeResources
startCurrentRelease
stopPrevRelease
