<?php
/**
 * GreenBlog Database Connection
 *
 * This file handles the database connection using ADODB.
 */

// Prevent direct access
if (!defined('GREENBLOG')) {
    die('Direct access not permitted');
}

// Include ADODB
require_once ROOT_DIR . '/vendor/adodb/adodb-php/adodb.inc.php';

/**
 * Get database connection
 *
 * @return ADOConnection Database connection object
 */
function getDbConnection() {
    static $conn = null;

    if ($conn === null) {
        try {
            $conn = ADONewConnection(DB_TYPE);
            $conn->Connect(DB_PATH);
            $conn->SetFetchMode(ADODB_FETCH_ASSOC);

            // Note: SetExceptionHandler is not available in this version of ADODB
            // We'll handle exceptions manually in our code
        } catch (Exception $e) {
            die('Database connection error: ' . $e->getMessage());
        }
    }

    return $conn;
}

/**
 * Execute a SQL query
 *
 * @param string $sql SQL query
 * @param array $params Query parameters
 * @return ADORecordSet|false Query result
 */
function executeQuery($sql, $params = []) {
    $conn = getDbConnection();
    try {
        return $conn->Execute($sql, $params);
    } catch (Exception $e) {
        error_log('SQL Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get a single row from the database
 *
 * @param string $sql SQL query
 * @param array $params Query parameters
 * @return array|false Row data or false on failure
 */
function getRow($sql, $params = []) {
    $result = executeQuery($sql, $params);
    if ($result && !$result->EOF) {
        return $result->fields;
    }
    return false;
}

/**
 * Get multiple rows from the database
 *
 * @param string $sql SQL query
 * @param array $params Query parameters
 * @return array Array of rows
 */
function getRows($sql, $params = []) {
    $result = executeQuery($sql, $params);
    $rows = [];

    if ($result) {
        while (!$result->EOF) {
            $rows[] = $result->fields;
            $result->MoveNext();
        }
    }

    return $rows;
}

/**
 * Insert a record into the database
 *
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @return int|false Last insert ID or false on failure
 */
function insertRecord($table, $data) {
    $conn = getDbConnection();
    try {
        $result = $conn->AutoExecute($table, $data, 'INSERT');
        if ($result) {
            return $conn->Insert_ID();
        }
        return false;
    } catch (Exception $e) {
        error_log('Insert Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update a record in the database
 *
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @param string $where WHERE clause
 * @param array $params Parameters for WHERE clause
 * @return bool Success or failure
 */
function updateRecord($table, $data, $where, $params = []) {
    $conn = getDbConnection();
    try {
        return $conn->AutoExecute($table, $data, 'UPDATE', $where, $params);
    } catch (Exception $e) {
        error_log('Update Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete a record from the database
 *
 * @param string $table Table name
 * @param string $where WHERE clause
 * @param array $params Parameters for WHERE clause
 * @return bool Success or failure
 */
function deleteRecord($table, $where, $params = []) {
    $conn = getDbConnection();
    try {
        return $conn->Execute("DELETE FROM $table WHERE $where", $params);
    } catch (Exception $e) {
        error_log('Delete Error: ' . $e->getMessage());
        return false;
    }
}
