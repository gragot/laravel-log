# Instalación

Añadir repositories del archivo composer.json el siguiente codigo:

```
"repositories":[
    ...    
    {
        "type": "vcs",
        "url": "https://github.com/gragot/laravel-log.git"
    }
    ...
],
```

Ejecutar composer require para descargar el repositorio:

```
composer require gragot/laravel-log
```

# Configuraciónes opcionales

En la clase ` App\Providers\AppServiceProvider ` añadimos el siguiente código en el método boot:

```
use Gragot\Laravel\Log;
use Illuminate\Support\Facades\DB;
```

```
Log::info('PETICION HTTP '.request()->fullUrl());
DB::listen(function($query) {
    Log::sql($query->sql . ' [' . implode(', ', $query->bindings) . ']');
});
```

El código anterior imprimirá en el log una linea que registra una petición HTTP y las consultas realizadas a base de datos.