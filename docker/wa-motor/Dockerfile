# ─────────────────────────────────────────────────────────────────────────────
# Dockerfile para el Motor de WhatsApp (wa-motor)
# Node.js 20 + Chromium — Optimizado para Puppeteer en Linux
# ─────────────────────────────────────────────────────────────────────────────
FROM node:20-slim

# Instalar Chromium y dependencias necesarias para Puppeteer/WhatsApp-Web.js
# (Versión slim de Node no incluye estas librerías por defecto)
RUN apt-get update && apt-get install -y \
    chromium \
    ca-certificates \
    fonts-liberation \
    fonts-noto-color-emoji \
    libnss3 \
    libatk-bridge2.0-0 \
    libdrm2 \
    libxkbcommon0 \
    libxcomposite1 \
    libxrandr2 \
    libgbm1 \
    libasound2 \
    libpangocairo-1.0-0 \
    libpango-1.0-0 \
    libcairo2 \
    --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

# Decirle a Puppeteer que NO descargue su propia versión de Chrome
# (usamos el Chromium del sistema que ya instalamos arriba)
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium

# Directorio de trabajo para la app completa (Laravel + wa-motor)
WORKDIR /app

# Copiar solo el directorio del motor de WhatsApp
COPY wa-motor/package.json wa-motor/package-lock.json ./wa-motor/
RUN cd wa-motor && npm ci --omit=dev

# Copiar el código del motor
COPY wa-motor/ ./wa-motor/

# Copiar también el .env raíz (wa-motor lo necesita para la conexión a MySQL)
COPY .env.docker.example .env

# No ejecutar como root (buena práctica de seguridad)
RUN groupadd -r wauser && useradd -r -g wauser -d /app wauser \
    && chown -R wauser:wauser /app
USER wauser

# Comando de inicio del motor de WhatsApp
CMD ["node", "wa-motor/index.js"]

