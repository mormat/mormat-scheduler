<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function mormat_scheduler_block_init() {
    register_block_type( __DIR__ . '/build' );
}

add_action( 'init', 'mormat_scheduler_block_init' );

add_action( "wp_ajax_nopriv_mormat_scheduler_get_events", "mormat_scheduler_get_events" );

function mormat_scheduler_get_events()
{
    $schedulerName = sanitize_text_field($_GET['scheduler_name']);
        
    $posts = get_posts([
        'post_type'   => 'mormat_scheduler',
        'post_status' => 'private',
        'title'  => $schedulerName,
    ]);
        
    if ($posts) {
        $recordsets = json_decode($posts[0]->post_content, true);
    } else {
        $recordsets = [];
    }
    
    $rows = [];
    foreach ($recordsets as $id => $values) {
        $rows[] = ['id' => $id ] + $values;
    }
    
    wp_send_json($rows);
    
    wp_die();
    
}

add_action("wp_ajax_nopriv_mormat_scheduler_post_event", "mormat_scheduler_post_event");

function mormat_scheduler_post_event()
{
    
    $schedulerName = sanitize_text_field($_GET['scheduler_name']);
    
    $id = sanitize_text_field($_POST['id']);
    
    $recordset = [
        'label'   => sanitize_text_field($_POST['label']),
        'start'   => sanitize_text_field($_POST['start']),
        'end'     => sanitize_text_field($_POST['end']),
        'bgColor' => sanitize_text_field($_POST['bgColor'])
    ];
    
    $posts = get_posts([
        'post_type'   => 'mormat_scheduler',
        'post_status' => 'private',
        'title'       => $schedulerName,
    ]);
    
    if ($posts) {
        
        $recordsets = json_decode($posts[0]->post_content, true);
        $recordsets[$id] = $recordset;
        
        wp_update_post([
            'ID' => $posts[0]->ID,
            'post_content' => json_encode($recordsets)
        ]);
        
    } else {
        
        $recordsets = [ $id => $recordset ];
        
        wp_insert_post([
            'post_type'    => 'mormat_scheduler',
            'post_title'   => $schedulerName,
            'post_status'  => 'private',
            'post_content' => json_encode($recordsets)
        ]);
        
    }
    
    wp_die();
    
}

function mormat_scheduler_activation_hook()
{
    
    global $wpdb;

    $tablename = $wpdb->prefix . "mormat_scheduler";
    $eventsTablename = $wpdb->prefix . "mormat_scheduler_events";
    
    $charset_collate = $wpdb->get_charset_collate();

    $sql = [    
        "CREATE TABLE $tablename (  
            id int(9) NOT NULL AUTO_INCREMENT, 
            scheduler_name text NOT NULL, 
            PRIMARY KEY  (id) 
        ) $charset_collate;",
        "CREATE TABLE $eventsTablename (  
            id int(9) NOT NULL AUTO_INCREMENT, 
            label text NOT NULL, 
            start datetime DEFAULT '0000-00-00 00:00:00' NOT NULL, 
            end datetime DEFAULT '0000-00-00 00:00:00' NOT NULL, 
            bgColor tinytext, 
            mormat_scheduler_id int(9) NOT NULL,
            FOREIGN KEY (mormat_scheduler_id) REFERENCES $tablename(id) ON DELETE CASCADE,
            PRIMARY KEY  (id) 
        ) $charset_collate;"
    ];
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    
}

register_activation_hook(__FILE__, 'mormat_scheduler_activation_hook');

function mormat_scheduler_get_scheduler_id($schedulerName = '')
{
    global $wpdb;
    
    $tablename = $wpdb->prefix . "mormat_scheduler";
    
    $query = $wpdb->prepare("SELECT id FROM " .
        $tablename . " WHERE " .
        "scheduler_name = %s ", $schedulerName );
    
    $result = $wpdb->get_var($query);
    if ($result !== null) {
        return (int) $result;
    }
    
    $values = ['scheduler_name' => $schedulerName];
    if ($wpdb->insert($tablename, $values)) {
        return $wpdb->insert_id;
    }
    
    return null;
}

/*
class Mormat_Scheduler_Events_Rest_Controller
{
    
    const TABLE_NAME = "mormat_scheduler_events";
    
    function getEvents($request)
    {
        
        $schedulerId = absint( $request->get_param('scheduler_id') );
        $start = $request->get_param('start');
        
        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT * ".
            " FROM " . $wpdb->prefix . self::TABLE_NAME . 
            " WHERE mormat_scheduler_id = %d",
            $schedulerId 
        );
    
        $results = $wpdb->get_results($query);
        $results[] = ['start' => $start ];
        
        return rest_ensure_response($results);
    }
    
    function putEvent($request)
    {
        
        $params = $request->get_json_params();
        
        $values = [
            'label'    => sanitize_text_field($params['label']),
            'start'    => sanitize_text_field($params['start']),
            'end'      => sanitize_text_field($params['end']),
            'bgColor'  => sanitize_text_field($params['bgColor']),
        ];
        
        $where = [ 
            'id'       => absint($params['id'])
        ];
        
        global $wpdb;
        $rows = $wpdb->update(
            $wpdb->prefix . self::TABLE_NAME, 
            $values, 
            $where
        );
        if ($rows !== false) {
            return rest_ensure_response($values);
        }
        
        return new WP_Error( 
            'rest_api_sad', 
            esc_html__( 'Failed to update scheduler event', 'mormat-scheduler'), 
            [ 'status' => 500 ] 
        );
    }
    
    function postEvent($request) 
    {
        
        $params = $request->get_json_params();
        
        $values = [
            'label'    => sanitize_text_field($params['label']),
            'start'    => sanitize_text_field($params['start']),
            'end'      => sanitize_text_field($params['end']),
            'bgColor'  => sanitize_text_field($params['bgColor']),
            'mormat_scheduler_id' => $request->get_param('scheduler_id'),
        ];
        
        global $wpdb;
        $rows = $wpdb->insert(
            $wpdb->prefix . self::TABLE_NAME, 
            $values
        );
        if ($rows !== false) {
            return rest_ensure_response($values);
        }
        
        return new WP_Error( 
            'rest_api_sad', 
            esc_html__( 'Failed to create scheduler event', 'mormat-scheduler'), 
            [ 'status' => 500 ] 
        );

    }
    
}


add_action( 'rest_api_init', function() {
    
    $controller = new Mormat_Scheduler_Events_Rest_Controller();
    
    register_rest_route( 'mormat_scheduler', '/(?P<scheduler_id>\d+)/events', [
        [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [$controller, 'getEvents']
        ],
        [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => [$controller, 'postEvent']
        ],
        [
            'methods'  => WP_REST_Server::EDITABLE,
            'callback' => [$controller, 'putEvent']
        ]
    ] );
    
} );
*/

function mormat_scheduler_init() {

    register_post_type('mormat_scheduler', 
        array(
            'label'        => __( 'Schedulers',          'mormat-scheduler'),
            'description'  => __( 'Display a scheduler', 'mormat-scheduler'),
            'public'       => true,
            'has_archive'  => true,
            'show_in_menu' => true,
            'menu_icon'    => 'dashicons-calendar',
            'rewrite'	   => array( 'slug' => 'mormat_scheduler'),
            'labels' => array(
                'name'               => __('Schedulers',      'mormat-scheduler'),
                'singular_name'      => __('Scheduler',       'mormat-scheduler'),
                'menu_name'          => __( 'Schedulers',     'mormat-scheduler'),
                'all_items'          => __( 'All schedulers', 'mormat-scheduler'),
                'view_item'          => __( 'View scheduler', 'mormat-scheduler'),
                'add_new_item'       => __( 'Add a new scheduler', 'mormat-scheduler'),
                'add_new'            => __( 'Add a scheduler',     'mormat-scheduler'),
                'edit_item'          => __( 'Edit scheduler',      'mormat-scheduler'),
                'update_item'        => __( 'Update scheduler',    'mormat-scheduler'),
                'search_items'       => __( 'Search scheduler',    'mormat-scheduler'),
                'not_found'          => __( 'Scheduler not found', 'mormat-scheduler'),
                'not_found_in_trash' => __( 'Scheduler not found in trash', 'mormat-scheduler'),

            ),
            'supports' => ['title']
        )
    );
    
}

add_action('init', 'mormat_scheduler_init');

function mormat_scheduler_activate() {

	flush_rewrite_rules();
	
}

register_activation_hook( __FILE__, 'mormat_scheduler_activate' );

function mormat_scheduler_add_meta_boxes() {

    add_meta_box(
        'mormat_scheduler_box_id',
        __('Scheduler configuration', 'mormat-scheduler'),
        'mormat_scheduler_add_meta_boxes_html',
        'mormat_scheduler',
        'normal',
        'high'
    );
    
}

function mormat_scheduler_add_meta_boxes_html() {

    $eventsList = get_post_meta( get_the_ID(), 'mormat_scheduler_events_tsv', true);

    wp_nonce_field('mormat_scheduler', 'mormat_scheduler_nonce');

    ?>

    <div class="mormat_scheduler_config">
        <textarea name="mormat_scheduler_events_tsv">
            <?php echo esc_textarea($eventsList); ?>
        </textarea>
    </div>

    <?php
		
}

add_action('add_meta_boxes', 'mormat_scheduler_add_meta_boxes');

function mormat_scheduler_wp_enqueue_scripts() {

    /*
    wp_register_script( 'mormat_scheduler', plugin_dir_url( __FILE__ ) . 'index.js', [ 'jquery' ]);

    wp_enqueue_script(  'mormat_scheduler' );

    wp_register_script( 'mormat_scheduler_dist', plugin_dir_url( __FILE__ ) . 'dist/mormat_standalone_scheduler.js', [ 'jquery' ]);

    wp_enqueue_script(  'mormat_scheduler_dist' );
    */

}

add_action('wp_enqueue_scripts', 'mormat_scheduler_wp_enqueue_scripts');

function mormat_scheduler_admin_enqueue_scripts() {

    /*
    wp_register_script( 'mormat_scheduler_admin', plugin_dir_url( __FILE__ ) . 'admin.js', [ 'jquery' ]);

    wp_enqueue_script(  'mormat_scheduler_admin' );

    wp_register_script( 'mormat_scheduler_dist', plugin_dir_url( __FILE__ ) . 'dist/mormat_standalone_scheduler.js', [ 'jquery' ]);

    wp_enqueue_script(  'mormat_scheduler_dist' );
    */
    
}

add_action('admin_enqueue_scripts', 'mormat_scheduler_admin_enqueue_scripts');

function mormat_scheduler_save_postdata( $post_id ) {
    
    if ( array_key_exists ( 'mormat_scheduler_events_tsv', $_POST ) ) {
        
        var_dump(sanitize_text_field($_POST['machin']));
        die();
        
        check_admin_referer('mormat_scheduler', 'mormat_scheduler_nonce');
        
        $cleanedValue = sanitize_textarea_field($_POST['mormat_scheduler_events_tsv']);
        
        update_post_meta($post_id, 'mormat_scheduler_events_tsv', $cleanedValue);
        
    }
    
}

add_action( 'save_post', 'mormat_scheduler_save_postdata');

function mormat_scheduler_content_filter( $content ) {

	$post = get_post();

	if ( get_post()->post_type === 'mormat_scheduler' ) {
		
		$value = get_post_meta($post->ID, 'mormat_scheduler_events_tsv', true);
		
		$content .= '<p class="mormat-scheduler-Scheduler">' . esc_html($value) . '</p>';
		
	}
	
	return $content;

}

add_filter( 'the_content', 'mormat_scheduler_content_filter');


function mormat_scheduler_post_event()
{
    
    $schedulerName = sanitize_text_field($_GET['scheduler_name']);
    
    $id = sanitize_text_field($_POST['id']);
    
    $recordset = [
        'label'   => sanitize_text_field($_POST['label']),
        'start'   => sanitize_text_field($_POST['start']),
        'end'     => sanitize_text_field($_POST['end']),
        'bgColor' => sanitize_text_field($_POST['bgColor'])
    ];
    
    $delete = boolval($_POST['delete']);
    
    $posts = get_posts([
        'post_type' => 'mormat_scheduler',
        'title'     => $schedulerName
    ]);
    
    $postarr = [ ];
    if ($posts) {
       
        $postarr['ID'] = $posts[0]->ID;
        
        $recordsets = json_decode($posts[0]->post_content, true);

    } else {
        
        $recordsets = [];
        
    }
    
    if ($delete) {
        unset($recordsets[$id]);
    } else {
        $recordsets[$id] = $recordset;
    }
    
    
    wp_insert_post($postarr + [
        'post_status'  => 'publish',
        'post_type'    => 'mormat_scheduler',
        'post_title'   => $schedulerName,
        'post_content' => json_encode($recordsets)
    ]);
    
    wp_die();
    
}

add_action("wp_ajax_mormat_scheduler_post_event", "mormat_scheduler_post_event");
add_action("wp_ajax_nopriv_mormat_scheduler_post_event", "mormat_scheduler_post_event");


function mormat_scheduler_get_events($schedulerName)
{
    $posts = get_posts([
        'post_type'   => 'mormat_scheduler',
        'title'       => $schedulerName,
    ]);
        
    if ($posts) {
        $recordsets = json_decode($posts[0]->post_content, true);
    } else {
        $recordsets = [];
    }
    
    $rows = [];
    foreach ($recordsets as $id => $values) {
        $rows[] = ['id' => $id ] + $values;
    }
    
    return $rows;
}


function mormat_scheduler_init() {

    register_post_type('mormat_scheduler', 
        array(
            'label'        => __( 'Schedulers',          'mormat-scheduler'),
            'description'  => __( 'Display a scheduler', 'mormat-scheduler'),
            'public'       => true,
            'has_archive'  => false,
            'show_in_menu' => false,
            'menu_icon'    => 'dashicons-calendar',
            'rewrite'	   => array( 'slug' => 'mormat_scheduler'),
            'labels' => array(
                'name'               => __('Schedulers',      'mormat-scheduler'),
                'singular_name'      => __('Scheduler',       'mormat-scheduler'),
                'menu_name'          => __( 'Schedulers',     'mormat-scheduler'),
                'all_items'          => __( 'All schedulers', 'mormat-scheduler'),
                'view_item'          => __( 'View scheduler', 'mormat-scheduler'),
                'add_new_item'       => __( 'Add a new scheduler', 'mormat-scheduler'),
                'add_new'            => __( 'Add a scheduler',     'mormat-scheduler'),
                'edit_item'          => __( 'Edit scheduler',      'mormat-scheduler'),
                'update_item'        => __( 'Update scheduler',    'mormat-scheduler'),
                'search_items'       => __( 'Search scheduler',    'mormat-scheduler'),
                'not_found'          => __( 'Scheduler not found', 'mormat-scheduler'),
                'not_found_in_trash' => __( 'Scheduler not found in trash', 'mormat-scheduler'),

            ),
            'supports' => ['title']
        )
    );
    
}

add_action('init', 'mormat_scheduler_init');



function mormat_scheduler_activate() {

    flush_rewrite_rules();
	
}

register_activation_hook( __FILE__, 'mormat_scheduler_activate' );