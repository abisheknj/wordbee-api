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
    $token =  get_cached_api_token();
    return decrypt_token($token);
}

// Function to make the API call to retrieve list of projects
function get_project_list($client_name = '', $date_from = '', $date_to = '', $language = '', $start_index = 0, $projects_per_page = 10) {
    // Get the token
    $token = get_auth_token();

    // API endpoint URL
    $url = 'https://td.eu.wordbee-translator.com/api/projects/list';

    // Construct the query
    $query = array();
    if (!empty($client_name)) {
        $query[] = "{client}.StartsWith(\"" . sanitize_text_field($client_name) . "\")";
    }
    if (!empty($date_from)) {
        $query[] = "{dtreceived} >= DateTime(" . date('Y, m, d', strtotime($date_from)) . ")";
    }
    if (!empty($date_to)) {
        $query[] = "{dtreceived} <= DateTime(" . date('Y, m, d', strtotime($date_to)) . ")";
    }
    if (!empty($language)) {
        $query[] = "{srct} == \"" . esc_attr($language) . "\"";
    }

    // Combine queries with 'And' if necessary
    $query_string = implode(' And ', $query);

    // Request body with pagination parameters
    $request_body = json_encode(array(
        "query" => $query_string,
        "skip" => $start_index,
        "take" => $projects_per_page
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
        return $data;
    } else {
        // Log error message if API call failed
        error_log('Failed to retrieve project list. Error: ' . wp_remote_retrieve_response_message($response));
        return false; // API call failed
    }
}

function enqueue_project_scripts() {
    // Enqueue CSS file
    wp_enqueue_style('multiselect-css', plugin_dir_url(__FILE__) . 'projects.css');

    // Enqueue JavaScript file
    wp_enqueue_script('project-list-js', plugin_dir_url(__FILE__) . 'projects.js', array('jquery'), null, true);
}

// Hook the function to the 'wp_enqueue_scripts' action
add_action('wp_enqueue_scripts', 'enqueue_project_scripts');

// Shortcode function to display project list table
function project_list_shortcode() {
    
    // Initialize variables
    $project_list = false;
    $output = '';
    $start_index = 0;
    $total_pages = 0;
    // Determine current page number
    $current_page = 0;

    // Check if the form is submitted
    if (isset($_POST['client_name']) || isset($_POST['date_from']) || isset($_POST['date_to']) || isset($_POST['language'])) {
        // Get search parameters from POST request
        $client_name = isset($_POST['client_name']) ? sanitize_text_field($_POST['client_name']) : '';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : '';

        // Define pagination parameters
        $start_index = isset($_POST['skip']) ? intval($_POST['skip']) : 0;
        $projects_per_page = 10; // Adjust this as needed

        // Call the function to get the project list
        $data = get_project_list($client_name, $date_from, $date_to, $language, $start_index, $projects_per_page);

        $project_list = $data['rows'];
        $total = $data['total'];

        // Calculate total number of pages
        $total_pages = ceil($total / $projects_per_page);

    // Determine current page number
         $current_page = floor($start_index / $projects_per_page) + 1;
    }

    // Calculate total number of pages
   

    // Start building form HTML
    $output .= '<form id="projectForm" method="post" style="display: flex; flex-direction: row; justify-content: center; margin-top: 20px;">';
    $output .= '<div style="margin-right: 20px;">';
    $output .= '<label for="client_name">Search Project:</label><br>';
    $output .= '<input type="text" name="client_name" style="margin-top: 5px;" value="' . (isset($_POST['client_name']) ? esc_attr($client_name) : '') . '"><br>';
    $output .= '</div>';

    $output .= '<div style="margin-right: 20px;">';
    $output .= '<label for="date_from">Date From:</label><br>';
    $output .= '<input type="date" id="date_from" name="date_from" style="margin-top: 5px;" value="' . (isset($_POST['date_from']) ? esc_attr($date_from) : '') . '"><br>';
    $output .= '</div>';
    
    $output .= '<div style="margin-right: 20px;">';
    $output .= '<label for="date_to">Date To:</label><br>';
    $output .= '<input type="date" id="date_to" name="date_to" style="margin-top: 5px;" value="' . (isset($_POST['date_to']) ? esc_attr($date_to) : '') . '"><br>';
    $output .= '</div>';

    $output .= '<div>';
    $output .= '<label for="language">Language:</label><br>';
    $output .= '<select id="language" name="language">';
    $output .= '<option value="en-US" ' . selected('en-US', isset($_POST['language']) ? $_POST['language'] : '', false) . '>en-US</option>';
    $output .= '<option value="en-UK" ' . selected('en-UK', isset($_POST['language']) ? $_POST['language'] : '', false) . '>en-UK</option>';
    $output .= '<option value="fr-FR" ' . selected('fr-FR', isset($_POST['language']) ? $_POST['language'] : '', false) . '>fr-FR</option>';
    // Add more options as needed
    $output .= '</select>';
    $output .= '</div>';

    $output .= '<input type="hidden" id="skip" name="skip" value="' . esc_attr($start_index) . '">'; // Add hidden field for skip value
    
    $output .= '<input type="submit" value="Get Results" style="margin-top: 10px;">';
    $output .= '</form>';

    // Only generate the table if the form is submitted
    if ($project_list) {
        // Start building table HTML
        $output .= '<table border="1" style="margin-top: 20px; width: 100%; border-collapse: collapse;">';
        $output .= '<tr>';
        $output .= '<th>Project ID (id)</th>';
        $output .= '<th>Reference (reference)</th>';
        $output .= '<th>Client (client)</th>';
        $output .= '<th>Status (statust)</th>';
        $output .= '<th>Source Language (srct)</th>';
        $output .= '<th>Date Received (dtreceived)</th>';
        $output .= '<th>Manager Name (managernm)</th>';
        $output .= '<th>Actions</th>';
        $output .= '</tr>';
    
        foreach ($project_list as $project) {
            $output .= '<tr>';
            $output .= '<td>' . esc_html($project['id']) . '</td>';
            $output .= '<td>' . esc_html($project['reference']) . '</td>';
            $output .= '<td>' . esc_html($project['client']) . '</td>';
            $output .= '<td>' . esc_html($project['statust']) . '</td>';
            $output .= '<td>' . esc_html($project['srct']) . '</td>';
            $output .= '<td>' . esc_html(date('Y-m-d H:i:s', strtotime($project['dtreceived']))) . '</td>';
            $output .= '<td>' . esc_html($project['managernm']) . '</td>';
            $output .= '<td><a href="' . esc_url(admin_url('admin-post.php?action=view_report&project_id=' . $project['id'])) . '">View Report</a></td>';
            $output .= '</tr>';
        }
    
        $output .= '</table>';

        $output .= '<div class="pagination">';
        $output .= '<span>Page ' . $current_page . ' of ' . $total_pages . '</span>';
        $output .= '<button class="pagination-button" onclick="changePage(0)" ' . ($start_index === 0 ? 'disabled' : '') . '>';
        $output .= '<i class="fas fa-chevron-left"></i> First</button>';
        $output .= '<button class="pagination-button" onclick="changePage(' . max(0, $start_index - $projects_per_page) . ')" ' . ($start_index === 0 ? 'disabled' : '') . '>';
        $output .= '<i class="fas fa-chevron-left"></i> Previous</button>';
        $output .= '<button class="pagination-button" onclick="changePage(' . ($start_index + $projects_per_page) . ')" ' . ($start_index + $projects_per_page >= $total ? 'disabled' : '') . '>';
        $output .= 'Next  <i class="fas fa-chevron-right"></i></button>';
        $output .= '<button class="pagination-button" onclick="changePage(' . (($total_pages - 1) * $projects_per_page) . ')" ' . ($start_index + $projects_per_page >= $total ? 'disabled' : '') . '>';
        $output .= 'Last  <i class="fas fa-chevron-right"></i></button>';
        $output .= '</div>';
        $output .= '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">';
        
    } else {
        // Handle case where no projects are found
        $output .= '<div>No projects found.</div>';
    }
    
    // Return the form and possibly the table HTML
    return $output;
}


// Register shortcode
add_shortcode('project_list', 'project_list_shortcode');
