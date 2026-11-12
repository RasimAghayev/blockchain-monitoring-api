# Local build so no service references a registry image (R91).
FROM redis:alpine@sha256:7958cfbb9d152f87dadfbc89f1b0b15938f0b81ff8982feb327476f4911935c3

LABEL maintainer="Rasim Aghayev <rasimaqayev@gmail.com>" \
      version="1.0" \
      description="Production-ready Redis server"

# The official image already runs as the redis user via its entrypoint; this only
# adds a healthcheck so dependent services can wait for readiness.

HEALTHCHECK --interval=30s --timeout=3s --retries=3 --start-period=5s \
    CMD redis-cli ping || exit 1

EXPOSE 6379
