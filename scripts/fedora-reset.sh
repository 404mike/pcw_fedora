#!/bin/bash

set -e

echo "🛑 Stopping containers and removing volumes..."
docker-compose down -v

echo "✅ Containers stopped and Fedora volume removed."

echo "🚀 Rebuilding and starting fresh containers..."
docker-compose up --build -d

echo "⏳ Waiting for Fedora to start (5s)..."
sleep 5

echo "⏳ Waiting for Fedora to become responsive..."

until curl -s -o /dev/null -u fedoraAdmin:fedoraAdmin http://localhost:8080/fcrepo/rest; do
  echo "🕒 Still waiting for Fedora..."
  sleep 2
done

echo "✅ Fedora is up!"

echo "📦 Creating Fedora container /pcw..."

HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
  -u fedoraAdmin:fedoraAdmin \
  -X PUT http://localhost:8080/fcrepo/rest/pcw \
  -H "Content-Type: text/turtle" \
  -d "")

if [ "$HTTP_STATUS" = "201" ]; then
  echo "✅ /pcw container created."
elif [ "$HTTP_STATUS" = "412" ]; then
  echo "⚠️  /pcw already exists."
else
  echo "❌ Failed to create /pcw. Status: $HTTP_STATUS"
fi
