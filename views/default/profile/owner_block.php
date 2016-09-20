<?php
/**
 * Profile owner block
 */

$user = elgg_get_page_owner_entity();

if (!$user) {
	// no user so we quit view
	echo elgg_echo('viewfailure', array(__FILE__));
	return TRUE;
}

$icon = elgg_view_entity_icon($user, 'large', array(
	'use_hover' => false,
	'use_link' => false,
	'img_class' => 'photo u-photo',
));

// grab the actions and admin menu items from user hover
$menu = elgg()->menus->getMenu('user_hover', [
	'entity' => $user,
	'username' => $user->username,
]);

$actions = $menu->getSection('action', []);
$admin = $menu->getSection('admin', []);

$profile_actions = '';
if (elgg_is_logged_in() && $actions) {
	$profile_actions = '<ul class="elgg-menu profile-action-menu mvm">';
	foreach ($actions as $action) {
		$item = elgg_view_menu_item($action, array('class' => 'elgg-button elgg-button-action'));
		$profile_actions .= "<li class=\"{$action->getItemClass()}\">$item</li>";
	}
	$profile_actions .= '</ul>';
}

// if admin, display admin links
$admin_links = '';
if (elgg_is_admin_logged_in() && elgg_get_logged_in_user_guid() != elgg_get_page_owner_guid()) {
	$text = elgg_echo('admin:options');

	$admin_links = '<ul class="profile-admin-menu-wrapper">';
	$admin_links .= "<li><a rel=\"toggle\" href=\"#profile-menu-admin\">$text&hellip;</a>";
	$admin_links .= '<ul class="profile-admin-menu" id="profile-menu-admin">';
	foreach ($admin as $menu_item) {
		$admin_links .= elgg_view('navigation/menu/elements/item', array('item' => $menu_item));
	}
	$admin_links .= '</ul>';
	$admin_links .= '</li>';
	$admin_links .= '</ul>';
}

// content links
$content_menu = elgg_view_menu('owner_block', array(
	'entity' => elgg_get_page_owner_entity(),
	'class' => 'profile-content-menu',
));

$details = elgg_view('profile/details');

$river = elgg_list_river(array(
	'subject_guid' => $user->guid,
	'pagination' => false,
	'limit' => 8,
));

$cover_url = $user->getIconURL(array(
	'type' => 'cover',
	'size' => 'master',
));

$userpoints = elgg_view('elggx_userpoints/icon', array(
	'size' => 'large',
	'entity' => $user,
));

$badges = elgg_view('badges/icon', array(
	'size' => 'large',
	'entity' => $user,
));

$last_login = elgg_view('lastlogin/profile_extend', array(
	'size' => 'large',
	'entity' => $user,
));

echo <<<HTML

<div id="profile-owner-block">
	<div class="elgg-col elgg-col-1of1 profile-header" style="background: url($cover_url) no-repeat;">
		$icon
		$profile_actions
	</div>
	<div class="elgg-col elgg-col-2of3">
		$details
		$river
	</div>
	<div class="elgg-col elgg-col-1of3">
		<div class="elgg-inner">
			$content_menu
			$admin_links
			$last_login
			$userpoints
			$badges
		</div>
	</div>
</div>

HTML;
