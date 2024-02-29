<?php

/*
 * Plugin Name: Mormat Scheduler
 * Plugin URI: https://github.com/mormat/wp-scheduler
 * Description: Provides a custom type for rendering a scheduler with events
 * Version: 0.0.1
 * Requires at least: 6.1
 * Requires PHP: 7.2
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: Mathieu MOREL
 * Author URI: http://github.com/mormat
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function mormat_scheduler_init() {

    register_post_type('mormat_scheduler', 
        array(
			'label'        => __( 'Schedulers',          'mormat-scheduler'),
			'description'  => __( 'Display a scheduler', 'mormat-scheduler'),
            'public'       => true,
            'has_archive'  => true,
            'show_in_menu' => true,
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
        __('Events list', 'mormat-scheduler'),
        'mormat_scheduler_add_meta_boxes_html',
		'mormat_scheduler'
    );
    
}

function mormat_scheduler_add_meta_boxes_html() {

	$eventsList = get_post_meta( get_the_ID(), 'mormat_scheduler_events_csv', true);

	echo '<textarea name="mormat_scheduler_events_csv">';
	echo esc_textarea($eventsList);
	echo '</textarea>';
	
	echo '<div class="mormat_scheduler_eventsList"></div>';
		
}

add_action('add_meta_boxes', 'mormat_scheduler_add_meta_boxes');

function mormat_scheduler_wp_enqueue_scripts() {

	wp_register_script( 'mormat_scheduler', plugin_dir_url( __FILE__ ) . 'index.js', [ 'jquery' ]);
	
	wp_enqueue_script(  'mormat_scheduler' );
	
	wp_register_script( 'mormat_scheduler_dist', plugin_dir_url( __FILE__ ) . 'dist/mormat_scheduler.js', [ 'jquery' ]);
	
	wp_enqueue_script(  'mormat_scheduler_dist' );

}

add_action('wp_enqueue_scripts', 'mormat_scheduler_wp_enqueue_scripts');

function mormat_scheduler_admin_enqueue_scripts() {

	wp_register_script( 'mormat_scheduler_admin', plugin_dir_url( __FILE__ ) . 'admin.js', [ 'jquery' ]);
	
	wp_enqueue_script(  'mormat_scheduler_admin' );

	wp_register_script( 'mormat_scheduler_dist', plugin_dir_url( __FILE__ ) . 'dist/mormat_scheduler.js', [ 'jquery' ]);
	
	wp_enqueue_script(  'mormat_scheduler_dist' );

}

add_action('admin_enqueue_scripts', 'mormat_scheduler_admin_enqueue_scripts');

function mormat_scheduler_save_postdata( $post_id ) {
    
    if ( array_key_exists ( 'mormat_scheduler_events_csv', $_POST ) ) {
        
        $cleanedValue = sanitize_textarea_field($_POST['mormat_scheduler_events_csv']);
        
		update_post_meta($post_id, 'mormat_scheduler_events_csv', $cleanedValue);
        
    }
    
}

add_action( 'save_post', 'mormat_scheduler_save_postdata');

function mormat_scheduler_content_filter( $content ) {

	$post = get_post();

	if ( get_post()->post_type === 'mormat_scheduler' ) {
		
		$value = get_post_meta($post->ID, 'mormat_scheduler_events_csv', true);
		
		$content .= '<p class="mormat-scheduler-Scheduler">' . esc_html($value) . '</p>';
		
	}
	
	return $content;

}

add_filter( 'the_content', 'mormat_scheduler_content_filter');


