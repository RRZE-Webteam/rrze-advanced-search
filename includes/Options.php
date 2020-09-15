<?php

namespace RRZE\AdvancedSearch;

defined('ABSPATH') || exit;

class Options
{
    /**
     * Option name
     * @var string
     */
    protected static $optionName = 'rrze_advanced_search';

    /**
     * Default options
     * @return array
     */
    protected static function defaultOptions(): array
    {
        $options = [
            'enabled' => 0,
            'columns' => [
                'title' => 1,
                'content' => 1,
                'excerpt' => 0
            ],
            'post_types' => ['post', 'page'],
            'search_meta_keys' => 0,
            'meta_keys' => [],
            'taxonomies' => [],
            'posts_per_page' => 20,
            'order' => 'DESC'
        ];

        return $options;
    }

    /**
     * Returns the options.
     * @return object
     */
    public static function getOptions(): object
    {
        $defaults = self::defaultOptions();

        $options = (array) get_option(self::$optionName);
        $options = wp_parse_args($options, $defaults);
        $options = array_intersect_key($options, $defaults);

        return (object) $options;
    }

    /**
     * Returns the name of the option.
     * @return string
     */
    public static function getOptionName(): string
    {
        return self::$optionName;
    }
}
