<?php
/**
 * Get all the page's out of the database and check if there is content
 */
class database_pages {

    public function get_db_pages (){
        $url_array = [];
        global $wpdb;
        $results = $wpdb->get_results("
            SELECT *
            FROM $wpdb->posts
                WHERE post_type = 'page' and post_content IS NOT NULL and post_content != ''
        ");
        // Iterate thrue the result to replace guid on revision page's
        foreach($results as $page) {
            $db_url = $page->guid;
            $db_name = $page->post_name;
                if (preg_match('/(\?page_id=.*)/',$db_url)) {
                    $db_url = strtolower(preg_replace('/(\?page_id=(.*))/', $db_name, $db_url));
                    $db_url = $db_url . '/'; //Permalink compability 
                }
            array_push($url_array, $db_url);
        }
        return $url_array;
    }

}