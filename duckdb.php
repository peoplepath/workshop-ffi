<?php

$duckDbFFI = FFI::load('duckdb-ffi2.h');

$database   = $duckDbFFI->new("duckdb_database");
$connection = $duckDbFFI->new("duckdb_connection");

$result = $duckDbFFI->duckdb_open(null, FFI::addr($database));

if ($result === $duckDbFFI->DuckDBError) {
    throw new Exception('Cannot open database');
}

$result = $duckDbFFI->duckdb_connect($database, FFI::addr($connection));

if ($result === $duckDbFFI->DuckDBError) {
    throw new Exception('Cannot connect to database');
}

$result = $duckDbFFI->duckdb_query($connection, 'CREATE TABLE integers(i INTEGER, j INTEGER);', null);

if ($result === $duckDbFFI->DuckDBError) {
    throw new Exception('Cannot execute query');
}

$result = $duckDbFFI->duckdb_query($connection, 'INSERT INTO integers VALUES (3,4), (5,6), (7, NULL) ', null);

if ($result === $duckDbFFI->DuckDBError) {
    throw new Exception('Cannot execute query');
}

$queryResult = $duckDbFFI->new('duckdb_result');

$result = $duckDbFFI->duckdb_query($connection, 'SELECT * FROM integers; ', FFI::addr($queryResult));

if ($result === $duckDbFFI->DuckDBError) {
    echo FFI::string($queryResult->error_message);
    die;
}

for ($row = 0; $row < $queryResult->row_count; $row++) {
    for ($column = 0; $column < $queryResult->column_count; $column++) {
        $value = $duckDbFFI->duckdb_value_varchar(FFI::addr($queryResult), $column, $row);
        echo FFI::string($value)." ";
        $duckDbFFI->duckdb_free($value);
    }

    echo "\n";
}

$duckDbFFI->duckdb_destroy_result(FFI::addr($queryResult));
$duckDbFFI->duckdb_disconnect(FFI::addr($connection));
$duckDbFFI->duckdb_close(FFI::addr($database));

/*
#include "duckdb.h"
#include <stdio.h>

int main() {
    // SLIDE explain data types
    duckdb_database db;
    duckdb_connection con;

    // SLIDE explain pointers + enums
    if (duckdb_open(NULL, &db) == DuckDBError) {
        // handle error
    }

    if (duckdb_connect(db, &con) == DuckDBError) {
        // handle error
    }

    // run queries...
    duckdb_state state;
    duckdb_result result;

    // create a table
    state = duckdb_query(con, "CREATE TABLE integers(i INTEGER, j INTEGER);", NULL);
    if (state == DuckDBError) {
        // handle error
    }

    // insert three rows into the table
    state = duckdb_query(con, "INSERT INTO integers VALUES (3, 4), (5, 6), (7, NULL);", NULL);
    if (state == DuckDBError) {
        // handle error
    }

    // query rows again
    state = duckdb_query(con, "SELECT * FROM integers", &result);
    if (state == DuckDBError) {
        // SLIDE automatic parameters conversions
        printf("%s", result->error_message);
    }

    // SLIDE explain structures
    // print the above result
    idx_t row_count = duckdb_row_count(&result);
    idx_t column_count = duckdb_column_count(&result);
    for (idx_t row = 0; row < row_count; row++) {
        for (idx_t col = 0; col < column_count; col++) {
            char *str_val = duckdb_value_varchar(&result, col, row);
            printf("%s", str_val);
            // SLIDE - memory management PHP vs C
            duckdb_free(str_val);
        }
        printf("\n");
    }


    // destroy the result after we are done with it
    duckdb_destroy_result(&result);

    // cleanup
    duckdb_disconnect(&con);
    duckdb_close(&db);
}

*/

// Generate headers
// echo '#define FFI_LIB "./libduckdb.so"' >> duckdb-ffi.h
// cpp -P -C -D"__attribute__(ARGS)=" duckdb.h >> duckdb-ffi.h

// Memory leaks detections
// valgrind --leak-check=full php duckdb.php

// GDB
// gdb --args php duckdb.php
// source /data/php-src/.gdbinit
// (gdb) dump_bt executor_globals.current_execute_data
