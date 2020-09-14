<?php

namespace RRZE\AdvancedSearch;

defined('ABSPATH') || exit;

class Main
{
	protected $options;

	protected $settings;

	public function __construct()
	{
		$this->options = Options::getOptions();
	}

	public function onLoaded()
	{
		add_filter('plugin_action_links_' . plugin()->getBaseName(), [$this, 'settingsLink']);

		// Settings
		$this->settings = new Settings();
		$this->settings->onLoaded();

		if (!$this->options->enabled) {
			return;
		}
		if (is_admin() && !(defined('DOING_AJAX') && DOING_AJAX && !$this->doingAjaxActions())) {
			return;
		}
		$search = new Search();
		$search->onLoaded();
	}

	public function settingsLink($links)
	{
		$settingsLink = sprintf(
			'<a href="%s">%s</a>',
			admin_url('options-general.php?page=' . $this->settings->getMenuPage()),
			__('Settings', 'rrze-advanced-search')
		); 
		array_unshift($links, $settingsLink); 
		return $links; 
	}

	public function doingAjaxActions()
	{
		$ajaxActions = [
			'query-attachments',
			'menu-quick-search',
			'acf/fields'
		];
		$currentAction = !empty($_REQUEST['action']) ? $_REQUEST['action'] : false;
		foreach ($ajaxActions as $action) {
			if (strpos($currentAction, $action) !== false) {
				return true;
			}
		}
		return false;
	}
}
