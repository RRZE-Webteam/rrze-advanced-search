<?php

namespace RRZE\AdvancedSearch;

defined('ABSPATH') || exit;

class Settings
{
    protected $optionName;

    protected $options;

    protected $menuPage = 'rrze-advanced-search';

    public function __construct()
    {
        $this->optionName = Options::getOptionName();
        $this->options = Options::getOptions();
    }

    public function onLoaded()
    {
        add_action('admin_menu', [$this, 'adminMenu']);
        add_action('admin_init', [$this, 'adminInit']);
    }

    public function getMenuPage()
    {
        return $this->menuPage;
    }

    public function adminMenu()
    {
        add_options_page(__('Advanced Search', 'rrze-advanced-search'), __('Advanced Search', 'rrze-advanced-search'), 'manage_options', $this->menuPage, [$this, 'optionsPage']);
    }

    public function optionsPage()
    {
            ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('Advanced Search Settings', 'rrze-advanced-search')); ?></h1>
            <form method="post" action="options.php">
                <?php do_settings_sections($this->menuPage); ?>
                <?php settings_fields($this->menuPage); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function adminInit()
    {
        register_setting($this->menuPage, $this->optionName, [$this, 'optionsValidate']);

        add_settings_section('advanced_search_general_section', false, [$this, 'generalSection'], $this->menuPage);
        add_settings_field('enabled', __('Enable', 'rrze-advanced-search'), [$this, 'enabledField'], $this->menuPage, 'advanced_search_general_section');

        add_settings_section('advanced_search_content_section', false, [$this, 'searchContentSection'], $this->menuPage);
        add_settings_field('columns', __('Post Columns', 'rrze-advanced-search'), [$this, 'columnsField'], $this->menuPage, 'advanced_search_content_section');
        add_settings_field('post_types', __('Post Types', 'rrze-advanced-search'), [$this, 'postTypesField'], $this->menuPage, 'advanced_search_content_section');
        add_settings_field('search_meta_keys', __('Post Meta', 'rrze-advanced-search'), [$this, 'searchMetaKeysField'], $this->menuPage, 'advanced_search_content_section');
        add_settings_field('taxonomies', __('Taxonomies', 'rrze-advanced-search'), [$this, 'taxonomiesField'], $this->menuPage, 'advanced_search_content_section');

        add_settings_section('advanced_search_result_section', false, [$this, 'searchResultSection'], $this->menuPage);
        add_settings_field('order', __('Result Order', 'rrze-advanced-search'), [$this, 'orderField'], $this->menuPage, 'advanced_search_result_section');
        add_settings_field('posts_per_page', __('Posts Per Page', 'rrze-advanced-search'), [$this, 'postsPerPageField'], $this->menuPage, 'advanced_search_result_section');
    }

    public function generalSection()
    {
        echo '<h3 class="title">', __('General', 'rrze-advanced-search'), '</h3>';
    }

    public function enabledField()
    {
        echo '<fieldset>';
        echo '<legend class="screen-reader-text">', __('Enable', 'rrze-advanced-search'), '</legend>';
        echo '<label><input type="checkbox" name="', $this->optionName, '[enabled]" id="advanced-search-enabled" value="1" ', checked($this->options->enabled, 1), '>', __('Enable Advanced Search', 'rrze-advanced-search'), '</label>';
        echo '</fieldset>';
    }

    public function searchContentSection()
    {
        echo '<h3 class="title">', __('Search Content', 'rrze-advanced-search'), '</h3>';
    }

    public function columnsField()
    {
        echo '<fieldset>';
        echo '<legend class="screen-reader-text">' . __('Title', 'rrze-advanced-search') . '</legend>';
        echo '<label><input type="checkbox" name="', $this->optionName, '[columns][title]" id="advanced-search-title" value="1" ', checked($this->options->columns['title'], 1), ' disabled="disabled">', __('Title', 'rrze-advanced-search'), '</label><br>';
        echo '<legend class="screen-reader-text">' . __('Content', 'rrze-advanced-search') . '</legend>';
        echo '<label><input type="checkbox" name="', $this->optionName, '[columns][content]" id="advanced-search-content" value="1" ', checked($this->options->columns['content'], 1), ' disabled="disabled">', __('Content', 'rrze-advanced-search'), '</label><br>';
        echo '<legend class="screen-reader-text">' . __('Excerpt', 'rrze-advanced-search') . '</legend>';
        echo '<label><input type="checkbox" name="', $this->optionName, '[columns][excerpt]" id="advanced-search-excerpt" value="1"', checked($this->options->columns['excerpt'], 1), '>', __('Excerpt', 'rrze-advanced-search'), '</label>';
        echo '</fieldset>';
    }

    public function postTypesField()
    {
        echo '<fieldset>';
        $postTypes = Functions::getPostTypes();
        foreach ($postTypes as $name => $label) {
            $checked = checked(true, in_array($name, $this->options->post_types), false);
            $disabled = in_array($name, ['post', 'page']) ? ' disabled="disabled"' : '';
            echo '<legend class="screen-reader-text">' . $label . '</legend>';
            printf(
                '<label><input type="checkbox" name="%1$s[post_types][%2$s]" id="advanced-search-post-types-%2$s" value="%2$s"%3$s %4$s>%5$s</label><br>',
                $this->optionName,
                $name,
                $checked,
                $disabled,
                $label
            );
        }
        echo '</fieldset>';
    }

    public function searchMetaKeysField()
    {
        echo '<fieldset>';
        echo '<legend class="screen-reader-text">', __('Post Meta', 'rrze-advanced-search'), '</legend>';
        echo '<label><input type="checkbox" name="', $this->optionName, '[search_meta_keys]" id="advanced-search-meta-keys" value="1" ', checked($this->options->search_meta_keys, 1), '>', __('Search post metadata (custom fields)', 'rrze-advanced-search'), '</label>';
        echo '</fieldset>';
    }

    public function taxonomiesField()
    {
        echo '<fieldset>';
        $taxonomies = Functions::getTaxonomies();
        foreach ($taxonomies as $name => $label) {
            $checked = checked(true, in_array($name, $this->options->taxonomies), false);
            echo '<legend class="screen-reader-text">' . $label . '</legend>';
            printf(
                '<label><input type="checkbox" name="%1$s[taxonomies][%2$s]" id="advanced-search-taxonomies-%2$s" value="%2$s" %3$s>%4$s</label><br>',
                $this->optionName,
                $name,
                $checked,
                $label
            );
        }
        echo '</fieldset>';
    }

    public function searchResultSection()
    {
        echo '<h3 class="title">', __('Search Result', 'rrze-advanced-search'), '</h3>';
    }

    public function orderField()
    {
        echo '<legend class="screen-reader-text">', __('Result Order', 'rrze-advanced-search'), '</legend>';
        echo '<select name="', $this->optionName, '[order]" id="advanced-search-order">';
        echo '<option value="none" ', selected($this->options->order, 'none'). '>', __('None', 'rrze-advanced-search'), '</option>';
        echo '<option value="DESC" ', selected($this->options->order, 'DESC'). '>', __('Descending', 'rrze-advanced-search'), '</option>';
        echo '<option value="ASC" ', selected($this->options->order, 'ASC'). '>', __('Ascending', 'rrze-advanced-search'), '</option>';
        echo '</select>';
    }

    public function postsPerPageField()
    {
        echo '<fieldset>';
        echo '<legend class="screen-reader-text">', __('Posts Per Page', 'rrze-advanced-search'), '</legend>';
        echo '<input type="number" name="', $this->optionName, '[posts_per_page]" id="advanced-search-posts-per-page" value="', $this->options->posts_per_page, '" step="1" min="5" placeholder="20">';
        echo '</fieldset>';
    }

    public function optionsValidate($input)
    {
        $input['enabled'] = !empty($input['enabled']) ? 1 : 0;

        $input['columns'] = [
            'title' => 1,
            'content' => 1,
            'excerpt' => !empty($input['columns']['excerpt']) ? 1 : 0
        ];

        $postTypes = !empty($input['post_types']) ? (array) $input['post_types'] : [];
        $allPostTypes = Functions::getPostTypes();
        foreach($postTypes as $key => $name) {
            if (!isset($allPostTypes[$name])) {
                unset($postTypes[$key]);
            }
        }
        $postTypes[] = 'post';
        $postTypes[] = 'page';
        $input['post_types'] = array_values(array_unique($postTypes));

        $input['search_meta_keys'] = !empty($input['search_meta_keys']) ? 1 : 0;
        if ($input['search_meta_keys']) {
            $metaKeys = [];
            foreach ($input['post_types'] as $postType) {
                $metaKeys = array_merge($metaKeys, Functions::getMetaKeys($postType));
            }
            $input['meta_keys'] = $metaKeys;
        }

        $taxonomies = !empty($input['taxonomies']) ? (array) $input['taxonomies'] : [];
        $allTaxonomies = Functions::getTaxonomies();
        foreach($taxonomies as $key => $name) {
            if (!isset($allTaxonomies[$name])) {
                unset($taxonomies[$key]);
            }
        }
        $input['taxonomies'] = array_values(array_unique($taxonomies)); 
        
        $postsPerPage = !empty($input['posts_per_page']) ? absint($input['posts_per_page']) : 20;
        $input['posts_per_page'] = $postsPerPage >= 5 ? $postsPerPage : 20;

        $input['order'] = !empty($input['order']) && in_array($input['order'], ['none', 'DESC', 'ASC']) ? $input['order'] : 'none';

        return $input;
    }
}
