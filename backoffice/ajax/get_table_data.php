<?php
/**
 * Author:     CriticalGears (http://www.CriticalGears.io)
 * Website:    http://www.CriticalGears.io
 * Support:    http://CriticalGears.io/support-tickets/
 * Version:    2.3.3
 *
 * Copyright:   (c)    CriticalGears.io
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "../includes/bootstrap.php";

// Log the request
if (!function_exists('log_message')) {
    function log_message($message) {
        $logFile = dirname(__DIR__) . '/debug.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    }
}

// Get and sanitize the section parameter
$section = $a->esc("section");
log_message("Processing section: $section");

if (empty($section)) {
    $error = 'Section parameter is required';
    log_message("Error: $error");
    http_response_code(400);
    echo json_encode(['error' => $error]);
    exit;
}

// Define the path to the section's settings file
$sectionFile = dirname(__DIR__) . "/{$section}/settings.php";
log_message("Looking for section file: $sectionFile");

// Check if the section settings file exists
if (!file_exists($sectionFile)) {
    $error = "Section file not found: $sectionFile";
    log_message("Error: $error");
    http_response_code(404);
    echo json_encode(['error' => $error]);
    exit;
}

// Include the section settings file
log_message("Including section file: $sectionFile");
include_once $sectionFile;

// Check if required variables are set
if (!isset($pt_table_data) || !is_array($pt_table_data)) {
    $error = 'Invalid table data configuration. $pt_table_data is not set or not an array';
    log_message("Error: $error");
    http_response_code(500);
    echo json_encode(['error' => $error]);
    exit;
}

// Make sure required variables have default values
$pt_table = $pt_table ?? '';
$pt_id = $pt_id ?? 'id';

log_message("Table: $pt_table, ID field: $pt_id");
log_message("Table data: " . print_r($pt_table_data, true));

// Initialize the data table and return the JSON response
try {
    log_message("Creating PT_Data_Table instance");
    $table = new PT_Data_Table($pt_table_data);
    
    log_message("Calling PT_Data_Table::simple()");
    $result = PT_Data_Table::simple($_GET, $pt_table, $pt_id, $pt_table_data);
    
    log_message("Sending JSON response");
    header('Content-Type: application/json');
    echo json_encode($result);
} catch (Exception $e) {
    $error = 'Failed to process data: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    log_message("Exception: $error");
    log_message("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode(['error' => $error]);
}

ob_end_flush();
