# Local build so no service references a registry image (R91).
FROM redis:alpine@sha256:5af80107c1eeecb484a2269b80a26d12bfa6521787da4663bd9f19f60a163483

LABEL maintainer="Rasim Aghayev <rasimaqayev@gmail.com>" \
      version="1.0" \
      description="Production-ready Redis server"

# The official image already runs as the redis user via its entrypoint; this only
# adds a healthcheck so dependent services can wait for readiness.

HEALTHCHECK --interval=30s --timeout=3s --retries=3 --start-period=5s \
    CMD redis-cli ping || exit 1

EXPOSE 6379
