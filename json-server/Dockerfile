FROM node:latest

RUN npm install -g json-server
COPY db.json db.json
COPY routes.json routes.json

EXPOSE 80
ENTRYPOINT ["json-server"]
# CMD ["db.json"]
CMD ["db.json", "--routes", "routes.json", "--port", "80", "-H", "0.0.0.0"]
