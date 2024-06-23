<?php

// Cloudflare D1 Query Builder
// Crafted by: https://github.com/nandunkz
// Source: https://github.com/nandunkz/Cloudflare-D1

namespace App\Helpers;

class CloudflareD1
{
    private static $query = '';
    private static $table = '';

    private static function url() {
        return 'https://api.cloudflare.com/client/v4/accounts/' 
                . env('CLOUDFLARE_ACCOUNT_ID')
                . '/d1/database/' 
                . env('CLOUDFLARE_DATABASE_ID')
                . '/query';
    }

    private static function bearer() {
        return env('CLOUDFLARE_API_KEY');
    }

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
        return $data['result'][0]['results'] ?? [];
    }

    public static function table($table) {
        self::$table = $table;
        self::$query = "SELECT * FROM $table";
        return new static;
    }

    public static function select($columns = ['*']) {
        $columns = implode(', ', $columns);
        self::$query = str_replace('SELECT *', "SELECT $columns", self::$query);
        return new static;
    }

    public static function raw($expression) {
        self::$query .= " $expression";
        return new static;
    }

    public static function join($table, $first, $operator, $second) {
        self::$query .= " JOIN $table ON $first $operator $second";
        return new static;
    }

    public static function union($query) {
        self::$query .= " UNION ($query)";
        return new static;
    }

    public static function where($column, $operator, $value) {
        self::$query .= (strpos(self::$query, 'WHERE') !== false) 
            ? " AND $column $operator '$value'"
            : " WHERE $column $operator '$value'";
        return new static;
    }

    public static function orWhere($column, $operator, $value) {
        self::$query .= (strpos(self::$query, 'WHERE') !== false)
            ? " OR $column $operator '$value'"  
            : " WHERE $column $operator '$value'";
        return new static;
    }

    public static function whereIn($column, $values) {
        $values = implode(', ', array_map(fn($value) => "'$value'", $values));
        self::$query .= (strpos(self::$query, 'WHERE') !== false)
            ? " AND $column IN ($values)"
            : " WHERE $column IN ($values)";
        return new static;
    }

    public static function orderBy($column, $direction = 'ASC') {
        $valid = ['ASC', 'DESC'];
        $direction = in_array($direction, $valid) ? $direction : 'ASC';
        self::$query .= " ORDER BY $column $direction";
        return new static;
    }

    public static function groupBy($column) {
        self::$query .= " GROUP BY $column";
        return new static;
    }

    public static function limit($number) {
        if (!is_numeric($number) || $number <= 0) {
            $number = 1000;
        }
        self::$query .= " LIMIT $number";
        return new static;
    }

    public static function offset($number) {
        if (!is_numeric($number) || $number <= 0) {
            $number = 0;
        }
        self::$query .= " OFFSET $number";
        return new static;
    }

    public static function insert($data) {
        if (!is_array($data)) {
            return false;
        }
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_map(fn($value) => "'$value'", array_values($data)));
        $query = "INSERT INTO " . self::$table . " ($columns) VALUES ($values)";
        return self::executeQuery($query);
    }

    public static function update($data) {
        if (!is_array($data)) {
            return false;
        }
        $set = implode(', ', array_map(fn($key, $value) => "$key = '$value'", array_keys($data), array_values($data)));
        self::$query = "UPDATE " . self::$table . " SET $set";
        return new static;
    }

    public static function delete() {
        self::$query = "DELETE FROM " . self::$table;
        return new static;
    }

    public static function get() {
        return self::executeQuery(self::$query);
    }

    public static function first() {
        return self::executeQuery(self::$query . " LIMIT 1")[0] ?? null;
    }
}
