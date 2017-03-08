# Docker

## Install

First of all, you have to install [docker](https://www.docker.com/), then you have to go into the docker's directory : 
 
```
$ cd ./docker
```

To install the container you just need to make : 

```
$ docker-compose up 
```

You'll see three differents containers for Apache, PHP-FPM and PostgreSQL. 

## Configuration with Maarch 

During the installation you'll have to configure PostgreSQL w/ maarch, to achieve it, you need to find the IP of your PSQL container by doing : 

```
docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' container_name_or_id
```
