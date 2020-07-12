# Instalación

Añadir en require y repositories del archivo composer.json el siguiente codigo:

```
"require": {
    ...
    "gragot/laravel/log": "dev-master",
    ...
},
"repositories":[
    ...    
    {
        "type": "vcs",
        "url": "https://github.com/gragot/laravel-log.git"
    }
    ...
],
```

Ejecutar composer update para descargar el repositorio:

```
composer update gragot/laravel-log
```

