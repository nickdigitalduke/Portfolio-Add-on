<?php
function create_overview_page() {
	global $wpdb;
	$tablename = $wpdb->prefix . "posts";

	$post_type		= "page";
	$post_title		= "Portfolio";
	$post_content	= "";
	$post_status	= "publish";
	$post_author	= 1;
	$post_name		= "portfolio";

	if (!get_page_by_path( $post_name, OBJECT, 'page')) {
		$sql = $wpdb->prepare("INSERT INTO `$tablename` (`post_type`, `post_title`, `post_content`, `post_status`, `post_author`, `post_name`) values (%s, %s, %s, %s, %d, %s)", $post_type, $post_title, $post_content, $post_status, $post_author, $post_name);

		$wpdb->query($sql);	
	}
}
register_activation_hook(PORTFOLIO_PLUGIN_FILE, function() {
    create_overview_page();
    import_elementor_template_on_activation();
	my_plugin_register_acf_fields();
});

function delete_overview_page() {
    $page_slug = 'overview';
    $page = get_page_by_path($page_slug);
    if ($page) {
        wp_delete_post($page->ID, true);
    }
}
register_deactivation_hook(PORTFOLIO_PLUGIN_FILE, function(){
	delete_overview_page();
// 	delete_elementor_templates_on_deactivation();
});