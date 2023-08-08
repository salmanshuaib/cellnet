<?php
/*
Plugin Name: My Webhooks
Description: Custom plugin to handle incoming webhooks from Mobilephones. 
Requisites: "WP REST API" plugin and "League Table Grid" plugin.
Version: 0.0.9
Delicensed: CC0 by Salman SHUAIB.
*/

function resolute($phNum) {
    $digits = str_split($phNum);
    while (count($digits) > 1) {
        $digits = str_split(array_sum($digits));
    }
    return intval($digits[0]);
}

// Webhook handler function
function handle_webhook_request(WP_REST_Request $request) {
    // Extract the necessary information from the request
    $headers = $request->get_headers();
    $request_body = $request->get_body();
    $content_type = $headers['Content-Type'];

    // Process the request data as needed
    $occurred_at = $headers['OccurredAt'];
    $from_number = $headers['FromNumber'];
    $text = $headers['text'];

    // Handle plain text content
    if ($content_type === 'text/plain') {
        $text = $request_body;
    }
    // Handle JSON content
    elseif ($content_type === 'application/json') {
        $json_data = json_decode($request_body, true);
        if ($json_data !== null) {
            $text = isset($json_data['text']) ? $json_data['text'] : '';
            $from_number = isset($json_data['FromNumber']) ? $json_data['FromNumber'] : '';
        }
    }

    // Perform actions based on the webhook data
    // Create a new post with the received data
    $post_data = array(
        'post_title'   => 'USA Scores A Penalty VS Legion Number ' . resolute($from_number),
        'post_content' => $text,   // Use the extracted text here
        'post_status'  => 'publish',
        'post_author'  => 1, // Change this to the desired author ID
    );

    $post_id = wp_insert_post($post_data);

    // Send a response if necessary
    if ($post_id) {
        // Post created successfully
        return new WP_REST_Response('Post created', 200);
    } else {
        // Error occurred while creating the post
        return new WP_REST_Response('Error creating post', 500);
    }
}

// Register the custom webhook route
function register_custom_webhook_route() {
    register_rest_route('my-webhooks/v1', '/webhook/text', array(
        'methods' => 'POST',
        'callback' => 'handle_webhook_request',
    ));
}
add_action('rest_api_init', 'register_custom_webhook_route');
