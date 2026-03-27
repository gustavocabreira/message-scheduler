FROM node:22-alpine

ARG UID=1000
ARG GID=1000

WORKDIR /app

# node_modules dir owned by host UID so the named volume is initialized with correct ownership
RUN mkdir -p /app/node_modules && chown -R ${UID}:${GID} /app

EXPOSE 5173

CMD ["sh", "-c", "npm install && npm run dev -- --host 0.0.0.0"]
