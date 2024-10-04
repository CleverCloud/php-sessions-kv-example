# PHP sessions tester

This application is a simple PHP sessions demo, allowing to show them working either with a Redis add-on on Clever Cloud or with [Materia KV](https://www.clever-cloud.com/materia/materia-kv/). It's as simple as create a new Clever Cloud application, set the environment variables, create a Materia KV add-on linked to the application and deploy :

```
clever create -t php --alias phpSessions
clever env set ENABLE_REDIS "true"
clever env set SESSION_TYPE "redis"

clever addon create kv phpSessionsKV --link phpSessions
clever deploy & clever open
```
