# Local build so no service references a registry image (R91).
FROM redis:alpine@sha256:3811787313eba226a2ef38658c6ccb91cd5e110edc89c37767de373120a0e5a0

LABEL maintainer="Rasim Aghayev <rasimaqayev@gmail.com>" \
      version="1.0" \
      description="Production-ready Redis server"

# The official image already runs as the redis user via its entrypoint; this only
# adds a healthcheck so dependent services can wait for readiness.

HEALTHCHECK --interval=30s --timeout=3s --retries=3 --start-period=5s \
    CMD redis-cli ping || exit 1

EXPOSE 6379
