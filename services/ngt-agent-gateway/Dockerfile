# NGT Agent Gateway — staging/runtime companion for WordPress Docker.
# Node >= 22 required for built-in node:sqlite (durable task store).
FROM node:22-alpine
WORKDIR /app
COPY package.json ./
RUN npm install --omit=dev
COPY src ./src
RUN mkdir -p /data/gateway
ENV NGT_GATEWAY_BIND=0.0.0.0
ENV NGT_GATEWAY_PORT=8787
ENV NGT_GATEWAY_ALLOW_LOCAL=1
ENV NGT_GATEWAY_DATA_DIR=/data/gateway
EXPOSE 8787
CMD ["node", "src/server.js"]
