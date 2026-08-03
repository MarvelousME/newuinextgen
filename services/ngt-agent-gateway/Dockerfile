# NGT Agent Gateway — staging/runtime companion for WordPress Docker.
FROM node:20-alpine
WORKDIR /app
COPY package.json ./
RUN npm install --omit=dev
COPY src ./src
ENV NGT_GATEWAY_BIND=0.0.0.0
ENV NGT_GATEWAY_PORT=8787
ENV NGT_GATEWAY_ALLOW_LOCAL=1
EXPOSE 8787
CMD ["node", "src/server.js"]
