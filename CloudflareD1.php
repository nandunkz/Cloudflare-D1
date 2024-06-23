<?php

/**
 * Cloudflare D1 Query Builder
 * 
 * This class provides a query builder for Cloudflare D1 database.
 * It allows you to build and execute SQL queries using the Cloudflare D1 API.
 * 
 * @link https://github.com/nandunkz/Cloudflare-D1 Source code repository
 * @link https://developers.cloudflare.com/d1/db/sql/ Cloudflare D1 API documentation
 */
namespace App\Helpers;
use Illuminate\Support\Facades\Log;

class CloudflareD1
{
    /**
     * The SQL query string.
     *
     * @var string
     */
    private static $query = '';

    /**
     * The table name.
     *
     * @var string
     */
    private static $table = '';

    /**
     * Get the Cloudflare D1 API URL.
     *
     * @return string The API URL
     */
    private static function url() {
        return 'https://api.cloudflare.com/client/v4/accounts/' 
                . env('CLOUDFLARE_ACCOUNT_ID')
                . '/d1/database/' 
                . env('CLOUDFLARE_DATABASE_ID')
                . '/query';
    }

    /**
     * Get the Cloudflare API bearer token.
     *
     * @return string The bearer token
     */
    private static function bearer() {
        return env('CLOUDFLARE_API_KEY');
    }

    /**
     * Execute the SQL query.
     *
     * @param string $query The SQL query
     * @return array The query result
     */
    private static function executeQuery($query) {
        $url = self::url();
        $payload = json_encode(["sql" => $query]);
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . self::bearer(),
                'Content-Type: application/json'
            ]
        ]);
        
        $response = curl_exec($curl);
        curl_close($curl);
        
        $data = json_decode($response, true);
        if (isset($data['success']) && $data['success'] === false) {
            $errors = $data['errors'];
            $errorMessage = '';
            foreach ($errors as $error) {
                $errorMessage .= $error['message'] . "\n";
            }
            Log::error($errorMessage);
            return false;
        }
        if (isset($data['result'][0]['results']) && count($data['result'][0]['results']) > 0) {
            return $data['result'][0]['results'];
        } else if (isset($data['result'][0]['results']) && count($data['result'][0]['results']) === 0) {
            return true;
        }
        return false;
    }

    /**
     * Set the table for the query.
     *
     * @param string $table The table name
     * @return CloudflareD1 The CloudflareD1 instance
     */
    public static function table($table) {
        self::$table = $table;
        self::$query = "SELECT * FROM $table";
        return new static;
    }

    /**
     * Set the columns to be selected in the query.
     *
     * @param string|array $columns The columns to select
     * @return CloudflareD1 The CloudflareD1 instance
     */
    public static function select($columns = '*') {
        if (is_array($columns)) {
            $columns = implode(', ', $columns);
        }
        self::$query = str_replace('SELECT *', "SELECT $columns", self::$query);
        return new static;
    }

    /**
     * Add a raw expression to the query.
     *
     * @param string $expression The raw expression
     * @return CloudflareD1 The CloudflareD1 instance
     */
    public static function raw($expression) {
        self::$query .= " $expression";
        return new static;
    }

    /**
     * Add a join clause to the query.
     *
     * @param string $table The table to join
     * @param string $first The first column
     * @param string $operator The operator
     * @param string $second The second column
     * @return CloudflareD1 The CloudflareD1 instance
     */
    public static function join($table, $first, $operator, $second) {
        self::$query .= " JOIN $table ON $first $operator $second";
        return new static;
    }

    /**
     * Add a union clause to the query.
     *
     * @param string $query The query to union
     * @return CloudflareD1 The CloudflareD1 instance
     */
    public static function union($query) {
        self::$query .= " UNION ($query)";
        return new static;
    }

    /**
     * Add a where clause to the query.
     *
     * @param string $column The column name
     * @param string $operator The operator
     * @param mixed $value The value
     * @return CloudflareD1 The CloudflareD1 instance
     */
    public static function where($column, $operator, $value) {
        self::$query .= (strpos(self::$query, 'WHERE') !== false) 
            ? " AND $column $operator '$value'"
            : " WHERE $column $operator '$value'";
        return new static;
    }

    /**
     * Add an "or where" clause to the query.
     *
     * @param string $column The column name
     * @param string $operator The operator
     * @param mixed $value The value
     * @return CloudflareD1 The CloudflareD1 instance
     */
    public static function orWhere($column, $operator, $value) {
        self::$query .= (strpos(self::$query, 'WHERE') !== false)
            ? " OR $column $operator '$value'"  
            : " WHERE $column $operator '$value'";
        return new static;
    }

    /**
     * Add a where in clause to the query.
     *
     * @param string $column The column name
     * @param array $values The values
     * @return CloudflareD1 The CloudflareD1 instance
     */
    public static function whereIn($column, $values) {
        $values = implode(', ', array_map(fn($value) => "'$value'", $values));
        self::$query .= (strpos(self::$query, 'WHERE') !== false)
            ? " AND $column IN ($values)"
            : " WHERE $column IN ($values)";
        return new static;
    }

    /**
     * Add an order by clause to the query.
     *
     * @param string $column The column name
     * @param string $direction The sort direction
     * @return CloudflareD1 The CloudflareD1 instance
     */
    public static function orderBy($column, $direction = 'ASC') {
        $valid = ['ASC', 'DESC'];
        $direction = in_array($direction, $valid) ? $direction : 'ASC';
        self::$query .= " ORDER BY $column $direction";
        return new static;
    }

    /**
     * Add a group by clause to the query.
     *
     * @param string $column The column name
     * @return CloudflareD1 The CloudflareD1 instance
     */
    public static function groupBy($column) {
        self::$query .= " GROUP BY $column";
        return new static;
    }

    /**
     * Add a limit clause to the query.
     *
     * @param int $number The limit number
     * @return CloudflareD1 The CloudflareD1 instance
     */
    public static function limit($number) {
        if (!is_numeric($number) || $number <= 0) {
            $number = 1000;
        }
        self::$query .= " LIMIT $number";
        return new static;
    }

    /**
     * Add an offset clause to the query.
     *
     * @param int $number The offset number
     * @return CloudflareD1 The CloudflareD1 instance
     */
    public static function offset($number) {
        if (!is_numeric($number) || $number <= 0) {
            $number = 0;
        }
        self::$query .= " OFFSET $number";
        return new static;
    }

    /**
     * Insert data into the table.
     *
     * @param array $data The data to insert
     * @return array The query result
     */
    public static function insert($data) {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_map(fn($value) => "'$value'", array_values($data)));
        $query = "INSERT INTO " . self::$table . " ($columns) VALUES ($values)";
        return self::executeQuery($query);
    }

    /**
     * Update data in the table.
     *
     * @param array $data The data to update
     * @return CloudflareD1 The CloudflareD1 instance
     */
    public static function update($data) {
        $set = implode(', ', array_map(fn($key, $value) => "$key = '$value'", array_keys($data), array_values($data)));
        self::$query = "UPDATE " . self::$table . " SET $set";
        return new static;
    }

    /**
     * Delete data from the table.
     *
     * @return CloudflareD1 The CloudflareD1 instance
     */
    public static function delete() {
        self::$query = "DELETE FROM " . self::$table;
        return new static;
    }

    /**
     * Execute the query and get the result.
     *
     * @return array The query result
     */
    public static function get() {
        return self::executeQuery(self::$query);
    }

    /**
     * Execute the query and get the first result.
     *
     * @return mixed The first query result or null if not found
     */
    public static function first() {
        return self::executeQuery(self::$query . " LIMIT 1")[0] ?? null;
    }

    /**
     * Create a new table.
     *
     * @param string $tableName The name of the table to create
     * @param array $columns The columns of the table
     * @return array The query result
     */
    public static function createTable($tableName, $columns) {
        $columnsDefinition = implode(', ', $columns);
        $query = "CREATE TABLE $tableName ($columnsDefinition)";
        return self::executeQuery($query);
    }

    /**
     * Drop an existing table.
     *
     * @param string $tableName The name of the table to drop
     * @return array The query result
     */
    public static function dropTable($tableName) {
        $query = "DROP TABLE $tableName";
        return self::executeQuery($query);
    }

    /**
     * Add a new column to an existing table.
     *
     * @param string $tableName The name of the table
     * @param string $columnDefinition The column definition
     * @return array The query result
     */
    public static function addColumn($tableName, $columnDefinition) {
        $query = "ALTER TABLE $tableName ADD $columnDefinition";
        return self::executeQuery($query);
    }

    /**
     * Update an existing column in a table.
     *
     * @param string $tableName The name of the table
     * @param string $oldColumnName The name of the column to update
     * @param string $newColumnDefinition The new column definition
     * @return array The query result
     */
    public static function updateColumn($tableName, $oldColumnName, $newColumnDefinition) {
        $query = "ALTER TABLE $tableName CHANGE $oldColumnName $newColumnDefinition";
        return self::executeQuery($query);
    }

    /**
     * Drop a column from an existing table.
     *
     * @param string $tableName The name of the table
     * @param string $columnName The name of the column to drop
     * @return array The query result
     */
    public static function dropColumn($tableName, $columnName) {
        $query = "ALTER TABLE $tableName DROP COLUMN $columnName";
        return self::executeQuery($query);
    }
}