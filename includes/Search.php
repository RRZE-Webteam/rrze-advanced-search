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
        add_filter('posts_orderby', [$this, 'postsOrderby'], 999, 2);        
        add_action('pre_get_posts', [$this, 'preGetPosts'], 999);
    }

    public function postSearch($search, $query)
    {
        global $wpdb;

        if (empty($search) || !empty($query->query_vars['suppress_filters'])) {
            return $search;
        }

        $search = '';
        $searchAnd = '';

        $termsRelationType = 'AND';

        foreach ((array) $query->query_vars['search_terms'] as $term) {
            $term = $wpdb->esc_like($term);
            $term1 = $term . '%';
            $term2 = '%' . $term . '%';
            $term3 = '%' . $term;
            $searchOr = '';
            $search .= "{$searchAnd} (";

            if (!empty($this->options->columns['title'])) {
                $search .= $wpdb->prepare("($wpdb->posts.post_title LIKE '%s')", $term1);
                $searchOr = ' OR ';
                $search .= $searchOr;
                $search .= $wpdb->prepare("($wpdb->posts.post_title LIKE '%s')", $term2);
                $searchOr = ' OR ';
                $search .= $searchOr;
                $search .= $wpdb->prepare("($wpdb->posts.post_title LIKE '%s')", $term3);
                $searchOr = ' OR ';                               
            }

            if (!empty($this->options->columns['content'])) {
                $search .= $searchOr;
                $search .= $wpdb->prepare("($wpdb->posts.post_content LIKE '%s')", $term1);
                $searchOr = ' OR ';
                $search .= $searchOr;
                $search .= $wpdb->prepare("($wpdb->posts.post_content LIKE '%s')", $term2);
                $searchOr = ' OR ';
                $search .= $searchOr;
                $search .= $wpdb->prepare("($wpdb->posts.post_content LIKE '%s')", $term3);
                $searchOr = ' OR ';                                
            }

            if (!empty($this->options->columns['excerpt'])) {
                $search .= $searchOr;
                $search .= $wpdb->prepare("($wpdb->posts.post_excerpt LIKE '%s')", $term2);
                $searchOr = ' OR ';
            }

            if ($this->options->search_meta_keys && !empty($this->options->meta_keys)) {
                $metaKeyOr = '';
                foreach ($this->options->meta_keys as $key_slug) {
                    $search .= $searchOr;
                    $search .= $wpdb->prepare("$metaKeyOr (pmeta.meta_key = '%s' AND pmeta.meta_value LIKE '%s')", $key_slug, $term2);
                    $searchOr = '';
                    $metaKeyOr = ' OR ';
                }
                $searchOr = ' OR ';
            }

            if (!empty($this->options->taxonomies)) {
                $taxOr = '';
                foreach ($this->options->taxonomies as $tax) {
                    $search .= $searchOr;
                    $search .= $wpdb->prepare("$taxOr (termtax.taxonomy = '%s' AND terms.name LIKE '%s')", $tax, $term2);
                    $searchOr = '';
                    $taxOr = ' OR ';
                }
                $searchOr = ' OR ';
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

    public function postsOrderby($orderby, $query) 
    {
        global $wpdb;
        if (empty($query->is_search) || empty($query->get('s')) || !empty($query->query_vars['suppress_filters'])) {
            return $orderby;
        }
        $order = in_array($this->options->order, ['DESC', 'ASC']) ? $this->options->order : 'DESC';
        $orderby = '(CASE ';
        foreach ((array) $query->query_vars['search_terms'] as $term) {
            $term = $wpdb->esc_like($term);
            $term1 = $term . '%';
            $term2 = '%' . $term . '%';
            $term3 = '%' . $term;        
            $orderby .= $wpdb->prepare("WHEN $wpdb->posts.post_title LIKE '%s' THEN 1 ", $term1);
            $orderby .= $wpdb->prepare("WHEN $wpdb->posts.post_title LIKE '%s' THEN 2 ", $term2);
            $orderby .= $wpdb->prepare("WHEN $wpdb->posts.post_content LIKE '%s' THEN 3 ", $term1);            
            $orderby .= $wpdb->prepare("WHEN $wpdb->posts.post_title LIKE '%s' THEN 4 ", $term3);
            $orderby .= $wpdb->prepare("WHEN $wpdb->posts.post_content LIKE '%s' THEN 5 ", $term2);
            $orderby .= $wpdb->prepare("WHEN $wpdb->posts.post_content LIKE '%s' THEN 6 ", $term3);                        
        }
        $orderby .= "ELSE 7 END), $wpdb->posts.post_modified {$order}";       
        return $orderby;
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
            $join .= " LEFT JOIN $wpdb->terms terms ON (termtax.term_id = terms.term_id) ";
        }

        return $join;
    }
}
