<?php

/*
 * Plugin Name: Mormat Scheduler
 * Plugin URI: http://github.com/mormat/wp-scheduler
 * Description: Provides a custom type for rendering a scheduler with events
 * Version: 0.0.1
 * Requires at least: 6.1
 * Requires PHP: 7.2
 * Author: Mathieu MOREL
 * Author: URI: http://github.com/mormat
 */

function mormat_scheduler_init() {

    register_post_type('mormat_scheduler', 
        array(
            'labels' => array(
                'name'          => __('Schedulers',    'textdomain'),
                'singular_name' => __('Scheduler',     'textdomain'),
                'add_new_item'  => __('Add scheduler', 'textdomain'),
            ),
            'public'       => true,
            'has_archive'  => true,
            'show_in_menu' => true,
            'rewrite'			  => array( 'slug' => 'mormat_scheduler'),
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
        __('Events manager'),
        'mormat_scheduler_add_meta_boxes_html',
		'mormat_scheduler'
    );
    
}

function mormat_scheduler_add_meta_boxes_html() {

	$jsonEvents = get_post_meta( get_the_ID(), 'mormat_scheduler_jsonEvents', true);

	echo '<input name="mormat_scheduler[jsonEvents]" value="' . esc_attr($jsonEvents) . '" style="width: 100%" />';

	echo '<div class="mormat_scheduler_eventsManager"></div>';
}

add_action('add_meta_boxes', 'mormat_scheduler_add_meta_boxes');

function mormat_scheduler_wp_enqueue_scripts() {

	wp_register_script( 'mormat_scheduler', plugin_dir_url( __FILE__ ) . 'index.js', [ 'jquery' ]);
	
	wp_enqueue_script(  'mormat_scheduler' );
	
	wp_register_script( 'mormat_scheduler_dist', plugin_dir_url( __FILE__ ) . 'dist/index.js', [ 'jquery' ]);
	
	wp_enqueue_script(  'mormat_scheduler_dist' );

}

add_action('wp_enqueue_scripts', 'mormat_scheduler_wp_enqueue_scripts');

function mormat_scheduler_admin_enqueue_scripts() {

	wp_register_script( 'mormat_scheduler_admin', plugin_dir_url( __FILE__ ) . 'admin.js', [ 'jquery' ]);
	
	wp_enqueue_script(  'mormat_scheduler_admin' );

	wp_register_script( 'mormat_scheduler_dist', plugin_dir_url( __FILE__ ) . 'dist/index.js', [ 'jquery' ]);
	
	wp_enqueue_script(  'mormat_scheduler_dist' );

}

add_action('admin_enqueue_scripts', 'mormat_scheduler_admin_enqueue_scripts');

function mormat_scheduler_save_postdata( $post_id ) {
    
    if ( array_key_exists ( 'mormat_scheduler', $_POST ) ) {
        
        $mormat_scheduler = $_POST['mormat_scheduler'] + array( 
        	'jsonEvents' => ''
        );
        
		update_post_meta($post_id, 'mormat_scheduler_jsonEvents', $mormat_scheduler['jsonEvents']);
        
//        error_log('saving scheduler infos ' . var_export($mormat_scheduler['events'], true));
    }
    
}

add_action( 'save_post', 'mormat_scheduler_save_postdata');

function mormat_scheduler_content_filter( $content ) {

	if ( get_post()->post_type === 'mormat_scheduler' ) {
		var_dump('MKMLQKMLKQSMLDQKSDML');	
	}
	
	return '<p>ICI !!!</p>' . $content . '<p>LA !!!</p>';

}
