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

// Modify the form HTML to include date filter inputs
$output .= '<form method="post" style="display: flex; flex-direction: row; justify-content: center; margin-top: 20px;">';
$output .= '<div style="margin-right: 20px;">';
$output .= '<label for="project_id">Enter Project ID:</label><br>';
$output .= '<input type="text" id="project_id" name="project_id" style="margin-top: 5px;"><br>';
$output .= '</div>';

$output .= '<div style="margin-right: 20px;">';
$output .= '<label for="source_language">Source Language:</label><br>';
$output .= '<select id="source_language" name="source_language" style="margin-top: 5px;">';
// Add options dynamically based on available languages
$output .= '<option value="en">English</option>';
$output .= '<option value="fr">French</option>';
// Add more options as needed
$output .= '</select><br>';
$output .= '</div>';

$output .= '<div style="margin-right: 20px;">';
$output .= '<label for="target_language">Target Language:</label><br>';
$output .= '<select id="target_language" name="target_language" style="margin-top: 5px;">';
// Add options dynamically based on available languages
$output .= '<option value="es">Spanish</option>';
$output .= '<option value="de">German</option>';
// Add more options as needed
$output .= '</select><br>';
$output .= '</div>';

$output .= '<div style="margin-right: 20px;">';
$output .= '<label for="date_from">Date From:</label><br>';
$output .= '<input type="date" id="date_from" name="date_from" style="margin-top: 5px;"><br>';
$output .= '</div>';

$output .= '<div>';
$output .= '<label for="date_to">Date To:</label><br>';
$output .= '<input type="date" id="date_to" name="date_to" style="margin-top: 5px;"><br>';
$output .= '</div>';

$output .= '<input type="submit" value="Apply" style="margin-top: 10px;">';
$output .= '</form>';


// Handle form submission and retrieve selected values
if (isset($_POST['project_id'])) {
    $project_id = sanitize_text_field($_POST['project_id']); // Sanitize input
    $source_language = sanitize_text_field($_POST['source_language']); // Sanitize input
    $target_language = sanitize_text_field($_POST['target_language']); // Sanitize input
    $date_from = sanitize_text_field($_POST['date_from']); // Sanitize input
    $date_to = sanitize_text_field($_POST['date_to']); // Sanitize input

    // Construct the date filter parameters
    $date_filter = array(
        'dateFrom' => $date_from,
        'dateTo' => $date_to
    );

    // Fetch the data based on the project ID, languages, and date filter
    $data = get_text_edit($project_id, $source_language, $target_language, $date_filter);

     

        // Check if data is retrieved
        if ($data !== false) {
            

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
