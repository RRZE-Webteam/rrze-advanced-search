<?php

namespace RRZE\AdvancedSearch;

defined('ABSPATH') || exit;

class Functions
{
	public static function getPostTypes()
	{
		$postTypeAry = [];
		$args = [
			'show_ui' => true,
			'public' => true
		];
		$allPostTypes = get_post_types($args, 'objects');
		if (is_array($allPostTypes) && !empty($allPostTypes)) {
			foreach ($allPostTypes as $name => $postObj) {
				$postTypeAry[$name] = !empty($postObj->labels->name) ? $postObj->labels->name : $name;
			}
		}
		return $postTypeAry;
	}

	public static function getTaxonomies()
	{
		$taxAry = [];
		$args = [
			'show_ui' => true,
			'public' => true
		];
		$allTaxs = get_taxonomies($args, 'objects');
		if (is_array($allTaxs) && !empty($allTaxs)) {
			foreach ($allTaxs as $name => $taxObj) {
				$postTypes = [];
				foreach ($taxObj->object_type as $objectType) {
					$postTypeObj = get_post_type_object($objectType);
					$postTypes[] = !empty($postTypeObj->labels->singular_name) ? $postTypeObj->labels->singular_name : $objectType;
				}
				$postTypes = implode(', ', $postTypes);
				$label = !empty($taxObj->labels->name) ? $taxObj->labels->name : $name;
				$taxAry[$name] = sprintf('%s (%s)', $label, $postTypes);
			}
		}
		return $taxAry;
	}

	public static function getMetaKeys($postType = 'post')
	{
		global $wpdb;
		$query = "SELECT DISTINCT($wpdb->postmeta.meta_key) 
			FROM $wpdb->posts 
			LEFT JOIN $wpdb->postmeta 
			ON $wpdb->posts.ID = $wpdb->postmeta.post_id 
			WHERE $wpdb->posts.post_type = '%s' 
			AND $wpdb->postmeta.meta_key != '' 
			AND $wpdb->postmeta.meta_key NOT RegExp '(^[_0-9].+$)' 
			AND $wpdb->postmeta.meta_key NOT RegExp '(^[0-9]+$)'";
		return $wpdb->get_col($wpdb->prepare($query, $postType));
	}
}
