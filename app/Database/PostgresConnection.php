<?php

namespace App\Database;

use DateTimeInterface;
use Illuminate\Database\PostgresConnection as BasePostgresConnection;
use PDO;

class PostgresConnection extends BasePostgresConnection
{
    /**
     * Bind values to their parameters in the given statement.
     * Preserves PDO::PARAM_BOOL so PostgreSQL boolean columns work natively
     * when PDO::ATTR_EMULATE_PREPARES is enabled (e.g. Supabase PgBouncer :6543).
     */
    public function bindValues($statement, $bindings): void
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $value,
                match (true) {
                    is_bool($value) => PDO::PARAM_BOOL,
                    is_int($value) => PDO::PARAM_INT,
                    is_resource($value) => PDO::PARAM_LOB,
                    default => PDO::PARAM_STR,
                },
            );
        }
    }

    /**
     * Prepare the query bindings for execution without casting booleans to integers,
     * preventing PostgreSQL SQLSTATE[42804] datatype mismatch under emulated prepares.
     */
    public function prepareBindings(array $bindings): array
    {
        $grammar = $this->getQueryGrammar();

        foreach ($bindings as $key => $value) {
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = $value->format($grammar->getDateFormat());
            }
        }

        return $bindings;
    }
}
