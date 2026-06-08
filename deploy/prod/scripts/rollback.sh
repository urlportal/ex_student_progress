#!/bin/bash
set -e

# Переменные $CURRENT и $PREV уже гарантированно есть в окружении, так как их передал GitLab

PREV_COMPOSE_FILE="$DEPLOY_DIR/releases/$PREV/docker-compose.prod.yaml"
CURR_COMPOSE_FILE="$DEPLOY_DIR/releases/$CURRENT/docker-compose.prod.yaml"
ENV_FILE="$DEPLOY_DIR/.deploy-env-rollback"

cat > "$ENV_FILE" << EOF
APP_ENV=${APP_ENV}
APP_NAME=${APP_NAME}
SLOT=${PREV}
TRAEFIK_NETWORK=${TRAEFIK_NETWORK}
EOF

#function rollbackDatabase {
#    echo "Проверяем необходимость отката миграций Symfony..."
#    if ! docker ps -q -f "name=${APP_NAME}-${CURRENT}-app" | grep -q .; then
#        echo "Контейнер ${APP_NAME}-${CURRENT}-app не запущен. Откат миграций пропущен."
#        return 0
#    fi
#
#    echo "Поиск новых примененных миграций..."
#    local new_migrations_count
#    new_migrations_count=$(docker exec "${APP_NAME}-${CURRENT}-app" php bin/console doctrine:migrations:status --format=json 2>/dev/null | grep -oP '"new_migrations":\s*\K\d+' || echo "0")
#
#    if [ "$new_migrations_count" -eq 0 ] || [ -z "$new_migrations_count" ]; then
#        echo "Пробуем безопасный откат на 1 шаг назад (prev)..."
#        docker exec "${APP_NAME}-${CURRENT}-app" php bin/console doctrine:migrations:migrate prev --no-interaction --env=prod || true
#    else
#        echo "Обнаружено миграций для отката: ${new_migrations_count}"
#        for ((i=1; i<=new_migrations_count; i++)); do
#            docker exec "${APP_NAME}-${CURRENT}-app" php bin/console doctrine:migrations:migrate prev --no-interaction --env=prod
#        done
#    fi
#}

function startPrevRelease {
    echo "Запуск стабильного слота ${PREV}..."
    docker compose \
        --env-file "$ENV_FILE" \
        -p "${APP_NAME}-${PREV}" \
        -f "$PREV_COMPOSE_FILE" \
        --profile app up -d --no-deps app nginx
        # --no-deps чтобы не пытались перезапускаться сервисы инфраструктуры (к этому их подталкивает depends_on)

    echo "Начинаем healthcheck стабильной сборки (${PREV})..."
    local attempt=0
    until docker exec "${APP_NAME}-${PREV}-nginx" \
            curl -sf http://"${APP_NAME}-${PREV}-nginx"/healthcheck > /dev/null 2>&1; do
        attempt=$((attempt + 1))
        if [ $attempt -ge 30 ]; then
            echo "Healthcheck слота ${PREV} не прошёл, откат прерван!"
            exit 1
        fi
        sleep 2
    done

    # Атомарное переключение трафика в Traefik
    sed "s/${APP_NAME}-${CURRENT}-nginx/${APP_NAME}-${PREV}-nginx/" \
        "${TRAEFIK_CONF_DIR}/${APP_NAME}.yaml" \
        > "/tmp/${APP_NAME}-traefik.yaml.tmp"
    sudo mv "/tmp/${APP_NAME}-traefik.yaml.tmp" "${TRAEFIK_CONF_DIR}/${APP_NAME}.yaml"

    echo "-----------------------------"
    echo "| Трафик переключён на ${PREV} |"
    echo "-----------------------------"
}

function stopCurrentRelease {
    local curr_env_file="$DEPLOY_DIR/.deploy-env-curr"
    cat > "$curr_env_file" << EOF
APP_ENV=${APP_ENV}
APP_NAME=${APP_NAME}
SLOT=${CURRENT}
TRAEFIK_NETWORK=${TRAEFIK_NETWORK}
EOF

    if [ -f "$CURR_COMPOSE_FILE" ]; then
        echo "Остановка приложения неудачного слота ${CURRENT}..."
        docker compose \
            --env-file "$curr_env_file" \
            -p "${APP_NAME}-${CURRENT}" \
            -f "$CURR_COMPOSE_FILE" \
            --profile app stop nginx app
        rm -f "$curr_env_file"
    fi
}

#rollbackDatabase
startPrevRelease
stopCurrentRelease
rm -f "$ENV_FILE"
