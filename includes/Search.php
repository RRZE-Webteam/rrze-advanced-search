<?php

namespace RRZE\AdvancedSearch;

defined('ABSPATH') || exit;

/**
 * [Search description]
 */
class Search
{
    protected $options;

    public function __construct()
    {
        $this->options = Options::getOptions();
    }

    public function onLoaded()
    {
        add_filter('posts_search', [$this, 'postSearch'], 999, 2);
        add_action('pre_get_posts', [$this, 'preGetPosts'], 999);
    }

    public function postSearch($search, $wp_query)
    {
        global $wpdb;

        if (empty($search) || !empty($wp_query->query_vars['suppress_filters'])) {
            return $search;
        }

        $q = $wp_query->query_vars;
        $search = $searchAnd = '';

        $termsRelationType = 'OR';

        foreach ((array)$q['search_terms'] as $term) {
            $term = '%' . $wpdb->esc_like($term) . '%';
            $logicalOr = '';
            $search .= "{$searchAnd} (";

            if (!empty($this->options->columns['title'])) {
                $search .= $wpdb->prepare("($wpdb->posts.post_title LIKE '%s')", $term);
                $logicalOr = ' OR ';
            }

            if (!empty($this->options->columns['content'])) {
                $search .= $logicalOr;
                $search .= $wpdb->prepare("($wpdb->posts.post_content LIKE '%s')", $term);
                $logicalOr = ' OR ';
            }

            if (!empty($this->options->columns['excerpt'])) {
                $search .= $logicalOr;
                $search .= $wpdb->prepare("($wpdb->posts.post_excerpt LIKE '%s')", $term);
                $logicalOr = ' OR ';
            }

            if ($this->options->search_meta_keys && !empty($this->options->meta_keys)) {
                $metaKeyOr = '';
                foreach ($this->options->meta_keys as $key_slug) {
                    $search .= $logicalOr;
                    $search .= $wpdb->prepare("$metaKeyOr (pmeta.meta_key = '%s' AND pmeta.meta_value LIKE '%s')", $key_slug, $term);
                    $logicalOr = '';
                    $metaKeyOr = ' OR ';
                }
                $logicalOr = ' OR ';
            }

            if (!empty($this->options->taxonomies)) {
                $taxOr = '';
                foreach ($this->options->taxonomies as $tax) {
                    $search .= $logicalOr;
                    $search .= $wpdb->prepare("$taxOr (termtax.taxonomy = '%s' AND est.name LIKE '%s')", $tax, $term);
                    $logicalOr = '';
                    $taxOr = ' OR ';
                }
                $logicalOr = ' OR ';
            }
            $search .= ")";
            $searchAnd = " $termsRelationType ";
        }

        if (!empty($search)) {
            $search = " AND ({$search}) ";
            if (!is_user_logged_in()) {
                $search .= " AND ($wpdb->posts.post_password = '') ";
            }
        }

        add_filter('posts_join_request', [$this, 'joinTable']);
        add_filter('posts_distinct_request', function () {
            return 'DISTINCT';
        });

        return $search;
    }

    public function preGetPosts($query)
    {
        if (empty($query->is_search) || empty($query->get('s'))) {
            return;
        }

        if (isset($_GET['post_type']) && in_array(esc_attr($_GET['post_type']), (array) $this->options->post_types)) {
            $query->query_vars['post_type'] = (array) esc_attr($_GET['post_type']);
        } else {
            $query->query_vars['post_type'] = (array) $this->options->post_types;
        }

        if (is_array($query->get('post_type')) && in_array('attachment', $query->get('post_type'))) {
            $query->set('post_status', ['publish', 'inherit']);
            if (is_user_logged_in()) {
                $query->set('post_status', ['publish', 'inherit', 'private']);
                $query->set('perm', 'readable');
            }
        }

        if (($postsPerPage = absint($this->options->posts_per_page)) >= 5) {
            $query->set('posts_per_page', $postsPerPage);
        }

        $query->set('orderby', 'relevance');

        if (in_array($this->options->order, array('DESC', 'ASC'), true) && $this->options->order !== 'DESC') {
            $query->set('order', $this->options->order);
        }
    }

    public function joinTable($join)
    {
        global $wpdb;

        if (!empty($this->options->meta_keys)) {
            $join .= " LEFT JOIN $wpdb->postmeta pmeta ON ($wpdb->posts.ID = pmeta.post_id) ";
        }

        if (!empty($this->options->taxonomies)) {
            $join .= " LEFT JOIN $wpdb->term_relationships termrel ON ($wpdb->posts.ID = termrel.object_id) ";
            $join .= " LEFT JOIN $wpdb->term_taxonomy termtax ON (termrel.term_taxonomy_id = termtax.term_taxonomy_id) ";
            $join .= " LEFT JOIN $wpdb->terms est ON (termtax.term_id = est.term_id) ";
        }

        return $join;
    }
}
