# frontend/Dockerfile
FROM node:20-alpine

WORKDIR /app

# Copie des fichiers de dépendances
COPY package*.json ./

# Installation propre
RUN npm install

# Copie du reste
COPY . .

# Port standard de Vite
EXPOSE 5173

# Important : --host permet d'accéder au site depuis Windows
CMD ["npm", "run", "dev", "--", "--host"]