<?php

/*
 * Plugin Name: Mormat Scheduler
 * Plugin URI: https://github.com/mormat/mormat-scheduler
 * Description: Add a Google-like scheduler to your WordPress site
 * Version: 0.1.2
 * Requires at least: 6.4
 * Requires PHP: 7.2
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: Mathieu MOREL
 * Author URI: http://github.com/mormat
 * Text Domain: mormat-scheduler
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define('MORMAT_SCHEDULER_EVENTS_TABLENAME', 'mormat_scheduler_events');

define('MORMAT_SCHEDULER_NONCE_ACTION', 'mormat_scheduler_events');

function mormat_scheduler_init() {
    
    load_plugin_textdomain(
        'mormat-scheduler',
        false,
        basename( dirname( __FILE__ ) ) . '/languages'
    );
    
    add_shortcode('mormat_scheduler', 'mormat_scheduler_shortcode');
    
}

add_action( 'init', 'mormat_scheduler_init' );

function mormat_scheduler_shortcode($atts = [], $content = null) {
    
    $defaults = [
        'initial_date' => null,
        'default_view' => 'week',
        'height'       => '640px',
        'events_namespace' => '',
        'locale'       => get_locale()
    ];
    
    $params  = (is_array($atts) ? $atts : []) + $defaults;
    
    $params['events'] = mormat_scheduler_get_events($params['events_namespace']);
    $params['editable']  = is_user_logged_in();
    $params['draggable'] = is_user_logged_in();
    
    $params['urls'] = [];
    foreach (['save_event', 'delete_event'] as $ajaxAction) {
        $rawUrl = admin_url('admin-ajax.php?' . http_build_query([
            'action' => 'mormat_scheduler_' . $ajaxAction,
        ]));
        $securedUrl = wp_nonce_url($rawUrl, MORMAT_SCHEDULER_NONCE_ACTION);
        $params['urls'][$ajaxAction] = $securedUrl;
    }    
    
    
    $params['labels'] = [
        'header.today' => __( 'Today', 'mormat-scheduler'),
        'header.day'   => __( 'Day',   'mormat-scheduler'),
        'header.month' => __( 'Month', 'mormat-scheduler'),
        'header.week'  => __( 'Week',  'mormat-scheduler')
    ];
    
    $jsonParams = json_encode($params);
    
    if ($content !== null) {
        $content .= '<div class="mormat_scheduler"'.
                    '     data-params="'.esc_attr($jsonParams).'"'.
                    '></div>';
    }
    
    return $content;
}

function mormat_scheduler_wp_enqueue_scripts() {

    $plugin_data = get_file_data(__FILE__, ['version' => 'Version'], 'plugin');
            
    wp_enqueue_script( 
        'mormat_scheduler', 
        plugins_url( '/index.js', __FILE__ ), 
        [ 'jquery' ],
        $plugin_data['version']
    );

    wp_enqueue_script( 
        'mormat_scheduler_dist', 
        plugins_url( '/dist/mormat_standalone_scheduler.js', __FILE__ ), 
        [ ] ,
        $plugin_data['version']
    );
    
    wp_enqueue_style( 
        'mormat_scheduler_style', 
        plugins_url( '/index.css', __FILE__ ), 
        [], 
        $plugin_data['version']
    );
    
}

add_action('wp_enqueue_scripts', 'mormat_scheduler_wp_enqueue_scripts');

function mormat_scheduler_activation_hook() {
    
    global $wpdb;

    $tablename = $wpdb->prefix . MORMAT_SCHEDULER_EVENTS_TABLENAME;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $tablename (  
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT, 
        namespace tinytext NOT NULL,
        label text NOT NULL, 
        start datetime DEFAULT '0000-00-00 00:00:00' NOT NULL, 
        end datetime DEFAULT '0000-00-00 00:00:00' NOT NULL, 
        bgColor tinytext, 
        data text,
        created_by bigint(20) unsigned DEFAULT 0 NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        updated_at datetime DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY  (id),
        KEY namespace (namespace),
        KEY start (start),
        KEY end (end)
    ) $charset_collate;";
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    
}

register_activation_hook(__FILE__, 'mormat_scheduler_activation_hook');

function mormat_scheduler_get_events($namespace) {
    
    global $wpdb;
    
    $tablename = $wpdb->prefix . MORMAT_SCHEDULER_EVENTS_TABLENAME;
    
    $sql = $wpdb->prepare( 
        'SELECT id, label, start, end, bgColor'.
        ' FROM ' . $tablename .
        ' WHERE namespace = %s',
        $namespace 
    );
    
    return $wpdb->get_results($sql, ARRAY_A);
    
}

function mormat_scheduler_ajax_save_event()
{
    
    check_ajax_referer(MORMAT_SCHEDULER_NONCE_ACTION);

    $id = absint($_POST['id']);
    
    $recordset = [
        'label'     => sanitize_text_field($_POST['label']),
        'start'     => sanitize_text_field($_POST['start']),
        'end'       => sanitize_text_field($_POST['end']),
        'bgColor'   => sanitize_text_field($_POST['bgColor']),
        'namespace' => sanitize_text_field($_POST['namespace']),
    ];
    
    foreach (['label', 'start', 'end'] as $key) {
        if (!$recordset[$key]) {
            wp_send_json_error(sprintf("`%s` required", $key));
        }
    }
    
    $results = [];
    
    global $wpdb;

    $tablename = $wpdb->prefix . MORMAT_SCHEDULER_EVENTS_TABLENAME;
    if ($id) {
        $recordset['updated_at'] = date('Y-m-d h:i:s');
        $wpdb->update($tablename, $recordset, ['id' => $id]);
    } else {
        $recordset['created_by'] = get_current_user_id();
        $recordset['created_at'] = date('Y-m-d h:i:s');
        $wpdb->insert($tablename, $recordset);
        $results['id'] = $wpdb->insert_id;
    }
    
    wp_send_json_success($results);
    
}

add_action("wp_ajax_mormat_scheduler_save_event", "mormat_scheduler_ajax_save_event");

function mormat_scheduler_ajax_delete_event() {
    
    check_ajax_referer(MORMAT_SCHEDULER_NONCE_ACTION);
    
    $id = absint($_POST['id']);

    if (!$id) {
        wp_send_json_error("`id` required");
    }
    
    global $wpdb;
    
    $tablename = $wpdb->prefix . MORMAT_SCHEDULER_EVENTS_TABLENAME;
    
    $wpdb->delete($tablename, ['id' => $id]);
    
    wp_send_json_success([]);   
    
}

add_action("wp_ajax_mormat_scheduler_delete_event", "mormat_scheduler_ajax_delete_event");
