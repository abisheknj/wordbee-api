<?php
/**
 * Plugin Name: Get Text Edit Test
 * Description: Test the get_text_edit function with multiple project IDs and calculate the time taken for each call.
 * Version: 1.0
 */

// Include textedits.php
require_once(plugin_dir_path(__FILE__) . 'textedits.php');

// Function to test get_text_edit with multiple project IDs
function test_get_text_edit() {
    // List of specific project IDs to test
    $project_ids = array(5605, 5598, 5597, 5585, 5578, 5577, 5576, 5573, 5568, 5565, 5552, 5551, 5550, 5546, 5542, 5531, 5505, 5480, 5460);

    // Source and target languages
    $source_language = 'en';
    $target_language = 'es';

    // Date filter (example)
    $date_filter = array(
        'dateFrom' => '2023-01-01',
        'dateTo' => '2023-12-31'
    );

    // Initialize counters and time tracker
    $executed_count = 0;
    $total_time = 0;

    // Initialize the output variable
    $output = '';

    // Loop through each project ID
    foreach ($project_ids as $project_id) {
        // Start time
        $start_time = microtime(true);

        // Call the get_text_edit function and handle errors
        $response = get_text_edit($project_id, $source_language, $target_language, $date_filter);

        // End time
        $end_time = microtime(true);

        // Calculate the duration
        $duration = $end_time - $start_time;

        // Accumulate total time
        $total_time += $duration;

        // Increment the executed count for successful calls
        if ($response !== false && !isset($response['error'])) {
            $executed_count++;
        } else {
            // Log the error details
            $error_message = isset($response['error']) ? $response['error'] : 'Unknown error';
            error_log("Error retrieving data for project ID $project_id: $error_message");
        }
    }

    $count = get_counter();

    // Build the output HTML
    $output .= '<h2>Project Test Results</h2>';
    $output .= '<p>Number of Projects Executed: ' . $executed_count . '</p>';
    $output .= '<p>Successfull api call count: ' . $count . '</p>';
    $output .= '<p>Total Time Taken: ' . $total_time . ' seconds</p>';

    // Return the output
    return $output;
}

// Register the shortcode to display results
add_shortcode('project_test', 'test_get_text_edit');
