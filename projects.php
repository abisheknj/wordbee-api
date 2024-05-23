<?php
/*
Plugin Name: Project List Plugin
Description: Display a table of projects.
Version: 1.0
*/

// Include auth.php
require_once(plugin_dir_path(__FILE__) .'auth.php');
require_once 'encryption.php';

// Function to get token
function get_auth_token() {
    // Get the token
    $token =  get_cached_api_token();;
    error_log($token);
    error_log(decrypt_token($token));
    return decrypt_token($token);
}

// Function to make the API call to retrieve list of projects
function get_project_list() {
    // Get the token
    $token = get_auth_token();

    // API endpoint URL
    $url = 'https://td.eu.wordbee-translator.com/api/projects/list';

    // Request body
    $request_body = json_encode(array(
        "query" => '{status} = 1 AND  {reference}.StartsWith("a")'
    ));

    // Make the API call with token in header using wp_remote_post()
    $response = wp_remote_post($url, array(
        'body' => $request_body,
        'headers' => array(
            'Content-Type' => 'application/json',
            'X-Auth-Token' =>  $token, 
            'X-Auth-AccountId' => 'transladiem'
        )
    ));

    // Check if the request was successful
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        // Decode the JSON response body
        $data = json_decode(wp_remote_retrieve_body($response), true);
        error_log('Retrieved project list data: ' . print_r($data, true));
        
        // Return the rows
        return $data['rows'];
    } else {
        // Log error message if API call failed
        error_log('Failed to retrieve project list. Error: ' . wp_remote_retrieve_response_message($response));
        return false; // API call failed
    }
}

// Shortcode function to display project list table
function project_list_shortcode() {
    error_log("project_list_shortcode function called");
    // Call the function to get the project list
    $project_list = get_project_list();

    // Start building table HTML
    $output = '<form method="post" style="display: flex; flex-direction: row; justify-content: center; margin-top: 20px;">';
    $output .= '<div style="margin-right: 20px;">';
    $output .= '<label for="project_id">Search Project:</label><br>';
    $output .= '<input type="text"  name="project_name" style="margin-top: 5px;"><br>';
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

    $output .= '<table border="1">';
    $output .= '<tr>';
    $output .= '<th>Project ID (id)</th>';
    $output .= '<th>Reference  (reference)</th>';
    $output .= '<th>Client (client)</th>';
    $output .= '<th>Status (statust)</th>';
    $output .= '<th>Source Language (srct)</th>';
    $output .= '<th>Date Received (dtreceived)</th>';
    $output .= '<th>Manager Name  (managernm)</th>';
    $output .= '<th>Actions</th>';
    $output .= '</tr>';

    if ($project_list) {
        foreach ($project_list as $project) {
            $output .= '<tr>';
            $output .= '<td>' . $project['id'] . '</td>';
            $output .= '<td>' . $project['reference'] . '</td>';
            $output .= '<td>' . $project['client'] . '</td>';
            $output .= '<td>' . $project['statust'] . '</td>';
            $output .= '<td>' . $project['srct'] . '</td>';
            $output .= '<td>' . date('Y-m-d H:i:s', strtotime($project['dtreceived'])) . '</td>';
            $output .= '<td>' . $project['managernm'] . '</td>';
            $output .= '<td><a href="' . admin_url('admin-post.php?action=view_report&project_id=' . $project['id']) . '">View Report</a></td>'; // Link to trigger the function via admin-post.php
            $output .= '</tr>';
        }
    } else {
        // Handle case where no projects are found
        $output .= '<tr><td colspan="7">No projects found.</td></tr>';
    }

    $output .= '</table>';

    // Return the table HTML
    return $output;
}

// Register shortcode
add_shortcode('project_list', 'project_list_shortcode');
?>
