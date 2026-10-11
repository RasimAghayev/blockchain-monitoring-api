# Start with Alpine Linux for a minimal base image
# Digest pinned (R91): a floating tag silently changes the build input.
FROM nginx:alpine@sha256:df221db836e1754089190208cee7eeda94f233197056426eda74a43ab1abeac2

# Add metadata labels
LABEL maintainer="Rasim Aghayev <rasimaqayev@gmail.com>" \
      version="1.0" \
      description="Production-ready Nginx server"

# Ensure the 'nginx' user and group exist or create them
RUN if ! getent group nginx; then \
        addgroup -g 101 -S nginx; \
    fi && \
    if ! id -u nginx; then \
        adduser -S -D -H -u 101 -h /var/cache/nginx -s /sbin/nologin -G nginx -g nginx nginx; \
    fi

# Install required packages
RUN apk add --no-cache \
    curl \
    tzdata \
    ca-certificates

# Create necessary directories with proper permissions
RUN mkdir -p /var/cache/nginx \
             /var/run/nginx \
             /var/log/nginx \
             /var/www/html/be \
             /var/www/html/fe \
             /etc/nginx/conf.d && \
    chown -R nginx:nginx /var/run/nginx \
                         /var/cache/nginx \
                         /var/log/nginx

# Copy custom Nginx configuration
COPY --chown=nginx:nginx ./server/nginx/nginx.conf /etc/nginx/nginx.conf
COPY --chown=nginx:nginx ./server/nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy backend content
#
# The repository contains no frontend. node.dockerfile and the previous
# "COPY ./src/fe/" line referred to a directory that exists neither on disk nor in
# git, so this image could never build. node.dockerfile is left in place for
# reference; the frontend is a separate concern.
COPY --chown=nginx:nginx ./src/be/ /var/www/html/be

# Implement healthcheck
# Laravel registers its health route as /up (bootstrap/app.php), not /health.
# The page renders a full HTML document and takes several seconds on a cold
# container, so the default 3s timeout always failed.
HEALTHCHECK --interval=30s --timeout=15s --start-period=30s --retries=3 \
    CMD curl -fsS -o /dev/null http://localhost/up || exit 1

# Set working directory
WORKDIR /var/www/html

# Expose ports
EXPOSE 80 443

# Switch to non-root user
USER nginx

# Start Nginx
CMD ["nginx", "-g", "daemon off;"]
