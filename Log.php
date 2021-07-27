<?php

/**
 * Class Log
 */

namespace Gragot\Laravel;

use Auth;

class Log
{
    const ALL = 'ALL'; // Todos
    const INFO = 'INFO'; // Informacion de traza
    const ERROR = 'ERROR'; // Errores en la aplicacion
    const WARN = 'WARN'; // Exepciones que no son consideradas errores
    const SQL = 'SQL'; // Sentencias SQL
    const CURL = 'CURL'; // Peticiones CURL

    private static $identificadorPeticion = null;
    public static $activate = true;

    private static function config() {
        return [
            'log_path' => config('gragot_log.log_path', storage_path().DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR),
            'user_field' => config('gragot_log.user_field', 'login'),
        ];
    }

    /**
     * Error
     * @param $mensaje
     */
    static function error($mensaje) {
        self::write($mensaje, self::ERROR);
    }

    static function warn($mensaje) {
        self::write($mensaje, self::WARN);
    }

    static function curl($mensaje) {
        self::write($mensaje, self::CURL);
    }

    static function info($mensaje, $saltosDeLinea = 'false') {
        self::write($mensaje, self::INFO, $saltosDeLinea);
    }

    static function sql($mensaje) {
        self::write($mensaje, self::SQL);
    }

    /**
     * @param $tipoLog
     * @return string
     * @throws Exception
     */
    static function getPathLogFile($tipoLog)
    {
        $fechaActual = new \DateTime();
        $fechaActualString = $fechaActual->format("Y-m-d");
        $year = $fechaActual->format("Y");
        $month = $fechaActual->format("m");
        $rutaBase = self::config()['log_path'];
        $ruta = $rutaBase.$year.DIRECTORY_SEPARATOR.$month.DIRECTORY_SEPARATOR;
        self::checkPath($year, $month);
        switch ($tipoLog) {
            case self::INFO:
                return $ruta."$fechaActualString INFO.log";
            case self::ERROR:
                return $ruta."$fechaActualString ERROR.log";
            case self::SQL:
                return $ruta."$fechaActualString SQL.log";
            case self::CURL:
                return $ruta."$fechaActualString CURL.log";
            case self::WARN:
                return $ruta."$fechaActualString WARN.log";
            default:
                return $ruta."$fechaActualString All.log";
        }
    }

    /**
     * @param $tipoLog
     *
     * @return string
     * @throws Exception
     */
    static function getTextoFromTipoLog($tipoLog)
    {
        switch ($tipoLog) {
            case self::INFO:
                return '_INFO';
                break;
            case self::ERROR:
                return 'ERROR';
                break;
            case self::WARN:
                return '_WARN';
                break;
            case self::SQL:
                return '__SQL';
                break;
            case self::CURL:
                return '_CURL';
                break;
            case self::ALL:
                return '__ALL';
                break;
            default:
                throw new Exception('Tipo log no definido');
        }
    }

    /**
     * @param Exception $e
     */
    static function dumpException(Throwable $e) {
        self::error($e->getMessage()." ({$e->getCode()})");
        self::error($e->getFile().' en la linea '.$e->getLine());
    }

    /**
     * @param $year
     * @param $month
     * @throws Exception
     */
    static function checkPath($year, $month)
    {
        $dirYear = self::config()['log_path'].$year;
        if(!is_dir($dirYear)) {
            if(!mkdir($dirYear)) {
                throw new Exception("Error al crear el directorio de logs: ".$dirYear);
            }
        }
        $dirMonth = $dirYear.DIRECTORY_SEPARATOR.$month;
        if(!is_dir($dirMonth)) {
            if(!mkdir($dirMonth)) {
                throw new Exception("Error al crear el directorio de logs: ".$dirMonth);
            }
        }
    }

    static function getIdentificadorPeticion()
    {
        if(!is_null(self::$identificadorPeticion)) {
            return self::$identificadorPeticion;
        }
        self::$identificadorPeticion = self::generateRandomString();
        return self::$identificadorPeticion;
    }

    static function generateRandomString($length = 4)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * @param $mensaje
     * @param $tipoLog
     * @throws \Exception
     */
    static function write($mensaje, $tipoLog, $saltosDeLinea = false)
    {
        if(!self::$activate) {
            return;
        }

        $t = microtime(true);
        $micro = sprintf("%06d",($t - floor($t)) * 1000000);
        $fechaActual = new \DateTime(date('Y-m-d H:i:s.'.$micro, $t));

        $identificadorPeticion = self::getIdentificadorPeticion();
        $campo_usuario = self::config()['user_field'];
        if(Auth::hasUser()) {
            $usuario = Auth::user();
            $usuario = $usuario->$campo_usuario."($usuario->id)#";
        } else {
            $usuario = 'no-user#';
        }

        $textoTipoLog = self::getTextoFromTipoLog($tipoLog);

        $cabezeraLog = $fechaActual->format("Y-m-d H:i:s")." $identificadorPeticion $textoTipoLog: $usuario";

        if(is_object($mensaje) && (is_a($mensaje, 'Exception') || is_a($mensaje, 'Error'))) {
            $mensaje = [
                'Error:' => $mensaje->getMessage(),
                'Archivo:' => $mensaje->getFile().'::'.$mensaje->getLine(),
                'Traza:' => explode("\n", $mensaje->getTraceAsString())
            ];
        }
        if(is_array($mensaje) || is_object($mensaje)) {
            $mensaje = $cabezeraLog.print_r($mensaje, true);
        } else {
            if(!$saltosDeLinea) {
                // Sustituimos los saltos de linea por espacios y la concatenacion de varios espacios por un solo espacio
                $mensaje = preg_replace('/\s+/', ' ', $mensaje);
            }
            $mensaje = $cabezeraLog.$mensaje;
        }

        $rutaArchivoLogAll = self::getPathLogFile(self::ALL);
        $rutaArchivo = self::getPathLogFile($tipoLog);
        file_put_contents($rutaArchivoLogAll, $mensaje."\n", FILE_APPEND);
        file_put_contents($rutaArchivo, $mensaje."\n", FILE_APPEND);
    }
}