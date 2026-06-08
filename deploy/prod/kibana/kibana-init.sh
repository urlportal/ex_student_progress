#!/bin/sh

set -e

KIBANA_URL="${KIBANA_URL:-http://kibana:5601}"

echo "Waiting for Kibana to be ready..."
until curl -sf "${KIBANA_URL}/api/status" > /dev/null 2>&1; do
    echo "Kibana not ready yet, retrying in 5s..."
    sleep 5
done

echo "Kibana is ready."

# Удаляем существующий Data View app-logs* если есть
ALL_VIEWS=$(curl -s "${KIBANA_URL}/api/data_views" -H "kbn-xsrf: true")
APP_LOGS_ID=$(echo "$ALL_VIEWS" | grep -B1 '"app-logs' | grep '"id"' | head -1 | sed 's/.*"id":"\([^"]*\)".*/\1/')

if [ -n "$APP_LOGS_ID" ]; then
    echo "Deleting existing Data View (id: ${APP_LOGS_ID})..."
    curl -s -X DELETE "${KIBANA_URL}/api/data_views/data_view/${APP_LOGS_ID}" \
        -H "kbn-xsrf: true" > /dev/null
fi

echo "Creating Data View app-logs*..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" \
    -X POST "${KIBANA_URL}/api/data_views/data_view" \
    -H "Content-Type: application/json" \
    -H "kbn-xsrf: true" \
    -d '{"data_view":{"title":"app-logs*","timeFieldName":"@timestamp"}}')

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "409" ]; then
    echo "Data View app-logs* created (HTTP ${HTTP_CODE})."
    exit 0
else
    echo "Failed to create Data View. HTTP code: ${HTTP_CODE}"
    exit 1
fi