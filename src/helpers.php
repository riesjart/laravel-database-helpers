<?php

use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\VarDumper\VarDumper;

if ( ! function_exists('mysql_version')) {

    /**
     * @param Connection|string|null $connection
     *
     * @return string|null
     */
    function mysql_version($connection = null): ? string
    {
        $connection = normalize_connection($connection);

        if ($connection->getDriverName() === 'mysql') {

            $version = $connection->select("SHOW VARIABLES LIKE 'innodb_version'")[0]->Value;

            return explode('-', $version)[0];
        }

        return null;
    }
}

if ( ! function_exists('normalize_connection')) {

    /**
     * @param Connection|string|null $connection
     *
     * @return Connection
     */
    function normalize_connection($connection = null): Connection
    {
        if ( ! $connection instanceof Connection) {

            $connection = DB::connection($connection);
        }

        return $connection;
    }
}

if ( ! function_exists('query_dump')) {

    /**
     * @param Connection|string|null $connection
     *
     * @return void
     */
    function query_dump($connection = null): void
    {
        query_listen(function ($query, $time): void {

            VarDumper::dump(compact('query', 'time'));

        }, $connection);
    }
}

if ( ! function_exists('query_listen')) {

    /**
     * @param Closure $callback
     * @param Connection|string|null $connection
     *
     * @return void
     */
    function query_listen(Closure $callback, $connection = null): void
    {
        $connection = normalize_connection($connection);

        $connection->listen(function (QueryExecuted $event) use ($callback): void {

            $sql = $event->sql;

            foreach ($event->bindings as $binding) {

                $sql = preg_replace('/\?/', $binding, $sql, 1);
            }

            call_user_func($callback, $sql, $event->time);
        });
    }
}

if ( ! function_exists('query_log')) {

    /**
     * @param Connection|string|null $connection
     *
     * @return void
     */
    function query_log($connection = null): void
    {
        query_listen(function ($query, $time): void {

            Log::debug('SQL query', compact('query', 'time'));

        }, $connection);
    }
}
