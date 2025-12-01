#!/bin/bash
# build-container.sh
# Purpose: Build & validate OutCast app, with Docker monitoring and PHP lint

# ---------------------------
# Setup
# ---------------------------
MONITORING_DIR="./monitoring"
mkdir -p "$MONITORING_DIR"

# Unset DOCKER_HOST to use the local Docker socket
unset DOCKER_HOST

# ---------------------------
# Docker Monitoring
# ---------------------------
if ! docker info >/dev/null 2>&1; then
    echo "Docker is not running!" | tee -a "$MONITORING_DIR/build_metrics.txt"
    echo "Exiting build."
    exit 1
else
    echo "Docker is running properly." | tee -a "$MONITORING_DIR/build_metrics.txt"
fi

# ---------------------------
# PHP Lint
# ---------------------------
echo "=== PHP Lint ==="
if ! docker run --rm -v "$(pwd)":/app php:8.2-cli php -l /app/**/*.php; then
    echo "PHP lint failed!" | tee -a "$MONITORING_DIR/build_metrics.txt"
    exit 1
else
    echo "PHP lint passed." | tee -a "$MONITORING_DIR/build_metrics.txt"
fi

# ---------------------------
# Other Build Steps (optional)
# ---------------------------
echo "Starting other build steps..."
# e.g., docker build, unit tests, etc.
# docker build -t outcast-app .

# ---------------------------
# Success
# ---------------------------
echo "Build completed successfully!" | tee -a "$MONITORING_DIR/build_metrics.txt"
