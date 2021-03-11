# Docker

Download docker image - https://hub.docker.com/r/fcrepo/fcrepo

run Docker 
```docker run -p8080:8080 --name=fcrepo fcrepo/fcrepo```

# Composer packages

Install composer packages
```composer install```

This should install:

* [EasyRDF](https://www.easyrdf.org)
* [Guzzle](https://docs.guzzlephp.org/en/stable)

# Ingest script

Run ```run.php```

This will loop through all the JSON files in ./data and convert the data to RDF before ingesting into Fedora. It will then ingest images to the new container.

Images can be downloaded with getImages.php