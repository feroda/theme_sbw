<?php

elgg_register_event_handler('init', 'system', 'theme_sbw_init');

function theme_sbw_init() {
	elgg_unregister_plugin_hook_handler('prepare', 'menu:site', '_elgg_site_menu_setup');
	elgg_unregister_plugin_hook_handler('output:before', 'page', '_elgg_views_send_header_x_frame_options');

	elgg_register_plugin_hook_handler('register', 'menu:topbar', 'theme_sbw_topbar_menu');

	elgg_require_js('theme_sbw');

	elgg_extend_view('elgg.css', 'theme_sbw.css');
	elgg_extend_view('page/elements/sidebar', 'sidebar/donate');
	elgg_extend_view('page/elements/footer', 'sidebar/donate');
	elgg_extend_view('page/elements/footer', 'copyright');

	elgg_unextend_view('page/elements/sidebar', 'search/header');

	// Profile page avatar is not in a column like in default Elgg, so there is no
	// space available for content such as badges. Instead we add them after the
	// content menu in profile/owner_block.php
	elgg_unextend_view('icon/user/default', 'badges/icon');
	elgg_unextend_view('icon/user/default', 'lastlogin/profile_extend');
	elgg_unextend_view('icon/user/default', 'elggx_userpoints/icon');

	if (!elgg_is_logged_in()) {
		elgg_extend_view('page/elements/topbar', 'language_selector/default');
	}

	elgg_register_page_handler('cover', 'theme_sbw_cover_page_handler');
	elgg_register_action("cover/upload", __DIR__ . "/actions/cover/upload.php");
	elgg_register_action("cover/crop", __DIR__ . "/actions/cover/crop.php");
	elgg_register_action("cover/remove", __DIR__ . "/actions/cover/remove.php");

	elgg_register_plugin_hook_handler('entity:cover:sizes', 'user', 'theme_sbw_cover_sizes');
	elgg_register_plugin_hook_handler('entity:cover:url', 'user', 'theme_sbw_cover_url');
	elgg_register_plugin_hook_handler('entity:cover:file', 'user', 'theme_sbw_cover_icon_file');

	elgg_register_plugin_hook_handler('register', 'menu:user_hover', 'theme_sbw_hover_menu');

	// Add groups link to owner block/hover menus
	elgg_register_plugin_hook_handler('register', 'menu:owner_block', 'theme_sbw_owner_block_menu');

	// Register a plugin hook handler for the entity menu
	elgg_register_plugin_hook_handler('register', 'menu:site', 'theme_sbw_iconized_menu');

	elgg_unregister_menu_item('footer', 'powered');
}

/**
 * Add icons to the menu
 */
function theme_sbw_iconized_menu($hook, $type, $items, $params) {

	$name_to_icon = array(
		"activity" => "globe",
		"bookmarks" => "thumb-tack",
		"thewire" => "pencil-square-o",
		"groups" => "circle-o",
		"file" => "files-o",
		"pages" => "file-text-o",
		"members" => "user",
		"blog" => "rss",
		"videolist" => "video-camera",
		"event_manager" => "calendar",
		"poll" => "check-square-o",
		"photos" => "camera",
		"FAQ" => "question-circle-o",
		"scheduling" => "calendar-check-o",
		"etherpad" => "wpforms",
		"discussion" => "comments-o",
		"translation_editor" => "language",
		"dashboard" => "home",
	);
	$icon_name = "pippo"; //set a fake default
	foreach ($items as $key => $item) {

		$icon_name = $name_to_icon[$item->getName()];
		$html_icon = elgg_view_icon($icon_name);
		$item_content = $item->getText();
		$pos = strpos($item_content, $html_icon);
		if ($pos === false) {
			$item->setText( $html_icon . $item_content);
		}
	}

	return $items;
}

/**
 * Display a page for uploading and cropping a cover image
 *
 * @param array $page
 */
function theme_sbw_cover_page_handler($page) {
	if (!isset($page[1])) {
		$user = elgg_get_logged_in_user_entity();
	} else {
		// TODO Handle cases where username is invalid
		$user = get_user_by_username($page[1]);
	}

	echo elgg_view_resource("cover/edit", array(
		'entity' => $user,
	));

	return true;
}

/**
 * Define icon sizes for the profile cover image
 *
 * @param string $hook   "entity:cover:sizes"
 * @param string $type   "user"
 * @param array  $icon   Array of icon sizes
 * @param array  $params Hook params
 * @return array Array of icon sizes
 */
function theme_sbw_cover_sizes($hook, $type, $sizes, $params) {
	return array(
		'topbar' => array('w' => 320, 'h' => 95, 'square' => FALSE, 'upscale' => TRUE),
		'tiny' => array('w' => 900, 'h' => 200, 'square' => FALSE, 'upscale' => TRUE),
		'small' => array('w' => 320, 'h' => 180, 'square' => FALSE, 'upscale' => TRUE),
		'medium' => array('w' => 400, 'h' => 200, 'square' => FALSE, 'upscale' => TRUE),
		'large' => array('w' => 1000, 'h' => 400, 'square' => FALSE, 'upscale' => TRUE),
		'master' => array('w' => 1000, 'h' => 990, 'square' => FALSE, 'upscale' => TRUE),
		'original' => array(),
	);
}

/**
 * Set custom file location for profile cover image
 *
 * @param string    $hook   "entity:cover:file"
 * @param string    $type   "user"
 * @param \ElggIcon $icon   Icon file
 * @param array     $params Hook params
 * @return \ElggIcon
 */
function theme_sbw_cover_icon_file($hook, $type, $icon, $params) {
	$entity = elgg_extract('entity', $params);
	$size = elgg_extract('size', $params, 'medium');

	$icon->owner_guid = $entity->guid;
	$icon->setFilename("cover/{$entity->guid}{$size}.jpg");

	return $icon;
}

/**
 * Return an URL for profile cover image
 *
 * @return string $url
 */
function theme_sbw_cover_url($hook, $type, $url, $params) {
	$user = elgg_extract('entity', $params);
	$size = elgg_extract('size', $params, 'medium');

	if (!$user instanceof ElggUser) {
		return;
	}

	$default_url = elgg_get_simplecache_url("icons/user/default{$size}.gif");

	if (!isset($user->covertime)) {
		return $default_url;
	}

	$filehandler = new ElggFile();
	$filehandler->owner_guid = $user->guid;
	$filehandler->setFilename("cover/{$user->guid}{$size}.jpg");
	$use_cookie = elgg_get_config('walled_garden'); // don't serve avatars with public URLs in a walled garden mode
	$url = elgg_get_inline_url($filehandler, $use_cookie);

	return $url ? : $default_url;
}

/**
 *
 */
function theme_sbw_topbar_menu($hook, $type, $menu, $params) {
	$user = elgg_get_logged_in_user_entity();

	foreach ($menu as $item) {
		switch($item->getName()) {
			case 'friends' :
			case 'messages' :
				$item -> setSection('alt');
				break;
			case 'account' :
				$item -> setPriority('1000');
				$item -> setText(elgg_view_icon('cog'));
				break;
			case 'profile' :
				$icon = elgg_view('output/img', array('src' => $user -> getIconURL('tiny'), ));
				$item -> setText("{$icon}<span>{$user->name}</span>");
				break;
			case 'site_notifications' :
				$item -> setText(elgg_view_icon('flag'));
				$item -> setParentName('');
				break;
		}
	}

	$menu[] = ElggMenuItem::factory(array(
		'name' => 'search_icon',
		'text' => elgg_view_icon('search') . elgg_view('search/header'),
		'href' => false,
		'section' => 'alt',
		'priority' => 1,
	));

	$menu[] = ElggMenuItem::factory(array(
		'name' => 'site_menu_toggle',
		'text' => elgg_view_icon('bars'),
		'href' => '',
		'priority' => 1,
	));

	if (!elgg_is_logged_in()) {
		$icon_dash_or_signin = 'sign-in';
	} else {
		$icon_dash_or_signin = 'home';
	}
	$menu[] = ElggMenuItem::factory(array(
		'name' => 'dashboard',
		'text' => elgg_view_icon($icon_dash_or_signin),
		'href' => '/dashboard',
		'section' => 'alt',
	));

	return $menu;
}

/**
 * Setup the user_hover menu
 *
 * @param string $hook
 * @param string $type
 * @param array  $menu
 * @param array  $params
 * @return array $menu
 */
function theme_sbw_hover_menu($hook, $type, $menu, $params) {
	$user = elgg_extract('entity', $params);

	if ($user->guid == elgg_get_logged_in_user_guid()) {
		$url = "cover/edit/$user->username";
		$item = new \ElggMenuItem('acover:edit', elgg_echo('cover:edit'), $url);
		$item->setSection('action');
		$menu[] = $item;
	}

	if (elgg_is_admin_logged_in()) {
		$url = "cover/edit/$user->username";
		$item = new \ElggMenuItem('cover:edit', elgg_echo('cover:edit'), $url);
		$item->setSection('admin');
		$menu[] = $item;
	}

	return $menu;
}


/**
 * Add a menu item to an ownerblock
 */
function theme_sbw_owner_block_menu($hook, $type, $return, $params) {

    if (elgg_instanceof($params['entity'], 'user')) {

        $user = elgg_get_logged_in_user_entity();

        $url =  "groups/owner/$user->username";
        $item = new ElggMenuItem('groups:owned', elgg_echo('groups:owned'), $url);
        elgg_register_menu_item('page', $item);

        $url = "groups/member/$user->username";
        $item = new ElggMenuItem('groups:member', elgg_echo('groups:yours'), $url);
        elgg_register_menu_item('page', $item);
    }

    return $return;
}

