FROM node:20-alpine
WORKDIR /app
COPY package.json ./
COPY packages ./packages
COPY services ./services
COPY tenant-blueprints ./tenant-blueprints
COPY apps ./apps
RUN npm install --omit=dev 2>/dev/null || true
WORKDIR /app/services/platform-api
ENV ECOSYSTEM_API_PORT=8790
EXPOSE 8790
CMD ["node", "src/server.mjs"]
