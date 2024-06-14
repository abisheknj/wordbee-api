<?php
/**
 * Plugin Name: Table Plugin
 * Description: Perform asynchronous API operations with polling for completion and log the response.
 * Version: 1.0
 */

// Include auth.php
require_once(plugin_dir_path(__FILE__) .'textedits.php');

require_once(plugin_dir_path(__FILE__) .'documents.php');

add_shortcode('display_text_edits', 'display_text_edits_shortcode');


// Hook the function to the 'wp_enqueue_scripts' action
add_action('wp_enqueue_scripts', 'enqueue_project_scripts');

// Shortcode callback function
function display_text_edits_shortcode() {
    $output = ''; // Initialize output variable
    if (isset($_GET['project_id'])) {
        $project_id = sanitize_text_field($_GET['project_id']); // Sanitize input
        $output .= '<h4>Generate report for Project ID: <span style="font-weight: normal; color: #777;">' . $project_id . '</span></h4>';
    }
    
    if (isset($_GET['reference_name'])) {
        $reference_name = sanitize_text_field($_GET['reference_name']);
        $output .= '<h4>Project Name: <span style="font-weight: normal; color: #777;">' . $reference_name . '</span></h4>';
    }
    
    // Modify the form HTML to include date filter inputs
    $output .= '<form method="post" style="display: flex; flex-direction: row; justify-content: center; margin-top: 20px;">';

    // $output .= '<div style="margin-right: 20px;">';
    // $output .= '<label for="source_language">Source Language:</label><br>';
    // $output .= '<select id="source_language" name="source_language" style="margin-top: 5px;">';
    // // Add options dynamically based on available languages
    // $output .= '<option value="en">English</option>';
    // $output .= '<option value="fr">French</option>';
    // // Add more options as needed
    // $output .= '</select><br>';
    // $output .= '</div>';

    // $output .= '<div style="margin-right: 20px;">';
    // $output .= '<label for="target_language">Target Language:</label><br>';
    // $output .= '<select id="target_language" name="target_language" style="margin-top: 5px;">';
    // // Add options dynamically based on available languages
    // $output .= '<option value="es">Spanish</option>';
    // $output .= '<option value="de">German</option>';
    // // Add more options as needed
    // $output .= '</select><br>';
    // $output .= '</div>';
   
    $output .= '<div style="margin-right: 20px;">';
    $output .= '<label for="date_from">Date From:</label><br>';
    $output .= '<input type="date" id="date_from" name="date_from" style="margin-top: 5px;"><br>';
    $output .= '</div>';

    $output .= '<div>';
    $output .= '<label for="date_to">Date To:</label><br>';
    $output .= '<input type="date" id="date_to" name="date_to" style="margin-top: 5px;"><br>';
    $output .= '</div>';

    $output .= '<input type="submit" value="Get Report" style="margin-top: 10px;">';
    $output .= '</form>';

    // Get the Project ID from the URL parameters
    if (isset($_GET['project_id'])) {
        $project_id = sanitize_text_field($_GET['project_id']); // Sanitize input
       
        // Handle form submission and retrieve selected values
        if (isset($_POST['date_from']) && isset($_POST['date_to'])) {
            $date_from = sanitize_text_field($_POST['date_from']); // Sanitize input
            $date_to = sanitize_text_field($_POST['date_to']); // Sanitize input

            // Construct the date filter parameters
            $date_filter = array(
                'dateFrom' => $date_from,
                'dateTo' => $date_to
            );

            // Fetch the data based on the project ID, languages, and date filter


            $data = get_text_edit($project_id, $date_filter);
    
            $word_count = get_total_word_count($project_id);
            
            // error_log($documents);
            // Check if data is retrieved
            if ($data !== false) {
                // Decode the JSON string into an array
                $decoded_data = json_decode($data, true);

                // Check if decoding was successful
                if ($decoded_data !== null) {
                    if (isset($decoded_data['counts']) && !empty($decoded_data['counts'])) {
                        
                        $edits = 0;
                        foreach ($decoded_data['counts'] as $item) {
                            $edits += $item['edits'];
                        }
            
                        // Calculate edit distance percentage
                        $edit_distance_percentage = ($edits / $word_count) * 100;
            
                        // Generate the HTML for the card
                        $output .= '<div class="card" style="margin-top: 20px;">';
                        $output .= '<div class="card-body">';
                        $output .= '<h5 class="card-title">Text edit report</h5>';
                        $output .= '<p class="card-text">Total Word Count: ' . $word_count . '</p>';
                        $output .= '<p class="card-text">Edit Distance Percentage: ' . round($edit_distance_percentage, 2) . '%</p>';
                        $output .= '</div>';
                        $output .= '</div>';


                        // Generate the HTML table with Bootstrap classes
                        $output .= '<div class="table-responsive">';
                        $output .= '<table class="table table-striped">';
                        $output .= '<thead>';
                        $output .= '<tr>';
                        // $output .= '<th style="background-color: #007bff !important; color: #fff !important;">Uid</th>';
                        $output .= '<th style="background-color: #007bff !important; color: #fff !important;">Texts</th>';
                        $output .= '<th style="background-color: #007bff !important; color: #fff !important;">Edits</th>';
                        $output .= '<th style="background-color: #007bff !important; color: #fff !important;">Words</th>';
                        $output .= '<th style="background-color: #007bff !important; color: #fff !important;">Chars</th>';
                        $output .= '<th style="background-color: #007bff !important; color: #fff !important;">Edit Distance</th>';
                        // $output .= '<th style="background-color: #007bff !important; color: #fff !important;">Source</th>';
                        // $output .= '<th style="background-color: #007bff !important; color: #fff !important;">Target</th>';
                        $output .= '</tr>';
                        $output .= '</thead>';
                        $output .= '<tbody>';
                        foreach ($decoded_data['counts'] as $item) {
                            $output .= '<tr>';
                            // $output .= '<td>' . esc_html($item['uid']) . '</td>';
                            $output .= '<td>' . esc_html($item['texts']) . '</td>';
                            $output .= '<td>' . esc_html($item['edits']) . '</td>';
                            $output .= '<td>' . esc_html($item['words']) . '</td>';
                            $output .= '<td>' . esc_html($item['chars']) . '</td>';
                            $output .= '<td>' . esc_html($item['editDistanceSum']) . '</td>';
                            // $output .= '<td>' . esc_html($decoded_data['locales'][$item['src']]) . '</td>';
                            // $output .= '<td>' . esc_html($decoded_data['locales'][$item['trg']]) . '</td>';
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
    } else {
        $output .= 'No project ID provided.';
    }

    return $output;
}


