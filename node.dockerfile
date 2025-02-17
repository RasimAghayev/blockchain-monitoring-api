# Build Stage
FROM node:23-alpine AS builder

WORKDIR /var/www/html/fe

# Install build dependencies
RUN apk add --no-cache python3 make g++ bash libc6-compat

# Copy necessary files
COPY src/fe/package*.json ./
COPY src/fe/vite.config.js ./
COPY src/fe/tailwind.config.js ./
COPY src/fe/index.html ./
COPY src/fe/src ./src

# Install dependencies and build the project
RUN npm install --include=dev && \
    npm run build

# Runtime Stage
FROM node:23-alpine

WORKDIR /var/www/html/fe

# Copy built assets and minimal configs from builder stage
COPY --from=builder /var/www/html/fe/dist ./dist
COPY src/fe/package*.json ./

# Install only production dependencies and necessary tools
RUN npm install --production && \
    npm cache clean --force && \
    npm install -g serve && \
    addgroup -S nodejs && adduser -S nodejs -G nodejs && \
    chown -R nodejs:nodejs . && \
    rm -rf /root/.npm /root/.cache /tmp/* /var/cache/apk/*

USER nodejs

# Healthcheck
HEALTHCHECK --interval=30s --timeout=3s \
    CMD wget --no-verbose --tries=1 --spider http://localhost:3000/ || exit 1

# Serve built assets
ENV HOST=0.0.0.0
EXPOSE 3000
CMD ["serve", "-s", "dist", "-l", "3000"]
