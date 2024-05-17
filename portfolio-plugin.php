<?php
/*
Plugin Name: Portfolio Plugin
Description: This plugin adds portfolio functionality to your WordPress website.
Version: 1.0
Author: Digital Duke
*/
include($_SERVER['DOCUMENT_ROOT'].'/wp-load.php');
define('PORTFOLIO_PLUGIN_FILE', __FILE__);

function my_plugin_register_acf_fields() {
    $path = plugin_dir_path(__FILE__) . 'templates/acf-fields-group.json';
	$acf_json_data = file_get_contents($path);
	$data = json_decode( $acf_json_data, true );

	if ( $acf_json_data && $data ) {
		foreach ( $data as $field ) {
			acf_import_field_group( $field );
		}
	}
}

function import_elementor_template_on_activation(){
    require_once(ABSPATH . 'wp-content/plugins/elementor/elementor.php');

	if (!class_exists('Elementor\Plugin')) {
        return 'Elementor is not active.';
    }
    $template1_file_path = plugin_dir_path(__FILE__) . 'templates/single-portfolio.json';
    if (!file_exists($template1_file_path)) {
        return 'Template 1 file not found.';
    }
    $template1_content = file_get_contents($template1_file_path);
    $template1_data = json_decode($template1_content, true);

    if (!$template1_data || json_last_error() !== JSON_ERROR_NONE || !is_array($template1_data)) {
        return 'Invalid JSON 1 file.';
    }
	
	$template1_manager = \Elementor\Plugin::$instance->templates_manager;
	$imported_template1 = $template1_manager->import_template([
		'fileData' => base64_encode( $template1_content ),  
		'fileName' => 'single-portfolio.json', 
	]);
	if (is_wp_error($imported_template1)) {
		$error_message = $imported_template1->get_error_message();
		return $error_message;
	}
	
// 	update_option('page_on_front', $imported_template1['template_id']);
// 	update_option('show_on_front', 'page');
	
	$template2_file_path = plugin_dir_path(__FILE__) . 'templates/portfolio-loop-item.json';
	if (!file_exists($template2_file_path)) {
		return 'Template 1 file not found.';
	}
	$template2_content = file_get_contents($template2_file_path);
	$template2_data = json_decode($template2_content, true);

	if (!$template2_data || json_last_error() !== JSON_ERROR_NONE || !is_array($template2_data)) {
		return 'Invalid JSON 1 file.';
	}

	$template2_manager = \Elementor\Plugin::$instance->templates_manager;
	$imported_template2 = $template2_manager->import_template([
		'fileData' => base64_encode( $template2_content ),  
		'fileName' => 'portfolio-loop-item.json', 
	]);
	if (is_wp_error($imported_template2)) {
		$error_message = $imported_template2->get_error_message();
		return $error_message;
	}
	
	update_option('page_on_front', $imported_template2['template_id']);
	update_option('show_on_front', 'page');
	
	$template3_file_path = plugin_dir_path(__FILE__) . 'templates/portfolio-overview.json';
	if (!file_exists($template3_file_path)) {
	  return 'Template 1 file not found.';
	}
	$template3_content = file_get_contents($template3_file_path);
	$template3_data = json_decode($template3_content, true);

	if (!$template3_data || json_last_error() !== JSON_ERROR_NONE || !is_array($template3_data)) {
	  return 'Invalid JSON 1 file.';
	}

	$template3_manager = \Elementor\Plugin::$instance->templates_manager;
	$imported_template3 = $template3_manager->import_template([
	  'fileData' => base64_encode( $template3_content ),  
	  'fileName' => 'portfolio-overview.json', 
	]);
	if (is_wp_error($imported_template3)) {
	  $error_message = $imported_template3->get_error_message();
	  return $error_message;
	}
	
// 	update_option('page_on_front', $imported_template3['template_id']);
// 	update_option('show_on_front', 'page');

	return 'Template imported successfully and set as the front page.';
}

include( plugin_dir_path( __FILE__ ) . 'includes/init-page.php');

// Start portfolio tab
function custom_portfolio_post_type() {
    register_post_type('portfolio', array(
        'labels' => array(
            'name' => __('Portfolio'),
            'singular_name' => __('Portfolio Item'),
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'taxonomies' => array('portfolio_category', 'portfolio_tag'),
    ));
}
add_action('init', 'custom_portfolio_post_type');

// Add custom columns to portfolio admin screen
function custom_portfolio_columns($columns) {
    unset($columns['date']);
    $columns['author'] = __('Author');
    $columns['portfolio_category'] = __('Portfolio Category');
    $columns['portfolio_tags'] = __('Portfolio Tags');
    $columns['date'] = __('Date');
    return $columns;
}
add_filter('manage_portfolio_posts_columns', 'custom_portfolio_columns');

function fill_custom_portfolio_columns($column, $post_id) {
    switch ($column) {
        case 'portfolio_category':
            $categories = get_the_terms($post_id, 'portfolio_category');
            if ($categories && !is_wp_error($categories)) {
                $category_names = array();
                foreach ($categories as $category) {
                    $category_names[] = $category->name;
                }
                echo implode(', ', $category_names);
            } else {
                echo __('No categories');
            }
            break;

        case 'portfolio_tags':
            $tags = get_the_terms($post_id, 'portfolio_tag');
            if ($tags && !is_wp_error($tags)) {
                $tag_names = array();
                foreach ($tags as $tag) {
                    $tag_names[] = $tag->name;
                }
                echo implode(', ', $tag_names);
            } else {
                echo __('No tags');
            }
            break;

        default:
            break;
    }
}
add_action('manage_portfolio_posts_custom_column', 'fill_custom_portfolio_columns', 10, 2);

// Register custom taxonomies
function custom_portfolio_taxonomies() {
    register_taxonomy(
        'portfolio_category',
        'portfolio',
        array(
            'label' => __('Portfolio Categories'),
            'rewrite' => array('slug' => 'portfolio-category'),
            'hierarchical' => true,
        )
    );
    register_taxonomy(
        'portfolio_tag',
        'portfolio',
        array(
            'label' => __('Portfolio Tags'),
            'rewrite' => array('slug' => 'portfolio-tag'),
            'hierarchical' => false,
        )
    );
}
add_action('init', 'custom_portfolio_taxonomies');

// Add shortcodes
function portfolio_shortcodes_init() {
    function portfolio_title_shortcode() {
        global $post;
        if ($post->post_type === 'portfolio') {
            $title = get_the_title($post->ID);
            return $title;
        }
    }
    add_shortcode('portfolio_title', 'portfolio_title_shortcode');

    function portfolio_description_shortcode() {
        return get_post_field('post_content');
    }
    add_shortcode('portfolio_description', 'portfolio_description_shortcode');

    function portfolio_categories_shortcode() {
        $categories = get_the_terms(get_the_ID(), 'portfolio_category');
        $category_names = array();
        if ($categories && !is_wp_error($categories)) {
            foreach ($categories as $category) {
                $category_names[] = $category->name;
            }
        }
        return implode(', ', $category_names);
    }
    add_shortcode('portfolio_category', 'portfolio_categories_shortcode');

    function portfolio_tags_shortcode() {
        $tags = get_the_terms(get_the_ID(), 'portfolio_tag');
        $output = '';

        if ($tags && !is_wp_error($tags)) {
            foreach ($tags as $tag) {
                $output .= '<label>' . esc_html($tag->name) . '</label>, ';
            }
            $output = rtrim($output, ', ');
        }

        return $output;
    }
    add_shortcode('portfolio_tags', 'portfolio_tags_shortcode');

    function portfolio_image_shortcode() {
        return get_the_post_thumbnail();
    }
    add_shortcode('portfolio_thumb', 'portfolio_image_shortcode');

    function portfolio_acf_shortcodes() {
        $fields = array(
            'korte_omschrijving' => 'portfolio_small_description',
            'details' => 'portfolio_details',
            'galerij' => 'portfolio_gallery',
            'tesimonial' => 'portfolio_testimonial'
        );
        foreach ($fields as $field_name => $shortcode_name) {
            add_shortcode($shortcode_name, function () use ($field_name) {
                $field_value = get_field($field_name);
                return $field_value;
            });
        }
    }
    add_action('init', 'portfolio_acf_shortcodes');
}
add_action('init', 'portfolio_shortcodes_init');