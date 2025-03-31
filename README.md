## Build image
```
docker-compose up --build
```

## Update .env
```
FEDORA_URL=http://fcrepo:8080/fcrepo/rest/
FEDORA_USERNAME=fedoraAdmin
FEDORA_PASSWORD=fedoraAdmin
FEDORA_PROJECT_ID=pcw
```

## Ingest content
```
docker exec -it fedora-php php run.php
```
