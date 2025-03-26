#!/bin/bash

set -e

echo "🚀 Starting Fedora container..."
docker-compose up -d fcrepo

echo "⏳ Waiting for Fedora to be ready (5s)..."
sleep 5

echo "📦 Creating Fedora resource /pcw..."
curl -s -u fedoraAdmin:fedoraAdmin -X PUT \
  http://localhost:8080/fcrepo/rest/pcw \
  -H "Content-Type: text/turtle" \
  -d "" || echo "⚠️ Could not create /pcw (might already exist)"

echo "✅ Fedora container and /pcw resource created."
