<?php
// Include the file containing the make_list_api_call() function
require_once 'wordbee.php';

function display_api_data_as_list() {
    error_log( 'display function is working');
    // Include the file containing the make_list_api_call() function

    // Call the make_list_api_call() function to fetch the data
    $data = make_list_api_call();

    // Initialize output variable
    $output = '';

    error_log( 'Data came : ' . $data);

    // Check if data is available
    if ($data) {
        // Start building the list
        $output .= '<ul>';

        // Iterate over each row and add it to the list
        foreach ($data['rows'] as $row) {
            $output .= '<li>';
            $output .= 'Reference: ' . $row['reference'] . '<br>';
            $output .= 'Status: ' . $row['statust'] . '<br>';
            $output .= 'Task: ' . $row['taskt'];
            $output .= '</li>';
        }

        // Close the list
        $output .= '</ul>';
    } else {
        // No data available
        $output = 'No data available.';
    }

    // Return the output
    return $output;
}




// Register shortcode to display API data as a list
add_shortcode('api_list', 'display_api_data_as_list');
