<?php
/**
 * Plugin Name: Table Plugin
 * Description: Perform asynchronous API operations with polling for completion and log the response.
 * Version: 1.0
 */

// Include auth.php
require_once(plugin_dir_path(__FILE__) .'textedits.php');

add_shortcode('display_text_edits', 'display_text_edits_shortcode');

// Shortcode callback function
function display_text_edits_shortcode() {
    $output = ''; // Initialize output variable

    // Form HTML
    $output .= '<form method="post">';
    $output .= '<label for="project_id">Enter Project ID:</label><br>';
    $output .= '<input type="text" id="project_id" name="project_id"><br>';
    $output .= '<input type="submit" value="Submit">';
    $output .= '</form>';

    // Check if form is submitted
    if (isset($_POST['project_id'])) {
        $project_id = sanitize_text_field($_POST['project_id']); // Sanitize input

        // Fetch the data based on the project ID
        $data = get_text_edit($project_id); // Assuming get_text_edit() retrieves the data

        // Check if data is retrieved
        if ($data !== false) {
            error_log('Data received: ' . $data);

            // Decode the JSON string into an array
            $decoded_data = json_decode($data, true);

            // Check if decoding was successful
            if ($decoded_data !== null) {
                if (isset($decoded_data['counts']) && !empty($decoded_data['counts'])) {

                    $output .= '<h2>Displaying data for Project ID: ' . $project_id . '</h2>';
                    // Generate the HTML table with Bootstrap classes
                    $output .= '<div class="table-responsive">';
                    $output .= '<table class="table table-striped">';
                    $output .= '<thead>';
                    $output .= '<tr>';
                    $output .= '<th style="background-color: #007bff !important; color: #fff !important;">Uid</th>';
                    $output .= '<th style="background-color: #007bff !important; color: #fff !important;">Texts</th>';
                    $output .= '<th style="background-color: #007bff !important; color: #fff !important;">Edits</th>';
                    $output .= '<th style="background-color: #007bff !important; color: #fff !important;">Words</th>';
                    $output .= '<th style="background-color: #007bff !important; color: #fff !important;">Chars</th>';
                    $output .= '<th style="background-color: #007bff !important; color: #fff !important;">Edit Distance</th>';
                    $output .= '<th style="background-color: #007bff !important; color: #fff !important;">Source</th>';
                    $output .= '<th style="background-color: #007bff !important; color: #fff !important;">Target</th>';
                    $output .= '</tr>';
                    $output .= '</thead>';
                    $output .= '<tbody>';
                    foreach ($decoded_data['counts'] as $item) {
                        $output .= '<tr>';
                        $output .= '<td>' . $item['uid'] . '</td>';
                        $output .= '<td>' . $item['texts'] . '</td>';
                        $output .= '<td>' . $item['edits'] . '</td>';
                        $output .= '<td>' . $item['words'] . '</td>';
                        $output .= '<td>' . $item['chars'] . '</td>';
                        $output .= '<td>' . $item['editDistanceSum'] . '</td>';
                        $output .= '<td>' . $decoded_data['locales'][$item['src']] . '</td>';
                        $output .= '<td>' . $decoded_data['locales'][$item['trg']] . '</td>';
                        $output .= '</tr>';
                    }
                    $output .= '</tbody>';
                    $output .= '</table>';
                    $output .= '</div>';
                } else {
                    $output .= 'No valid data available.';
                }
            } else {
                $output .= 'Failed to decode JSON data.';
            }
        } else {
            $output .= 'Failed to retrieve data.';
        }
    }

    return $output;
}
