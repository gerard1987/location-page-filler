<?php
/*
Plugin Name:  location-page-filler
Plugin URI:   https://github.com/gerard1987/location-page-filler
Description:  Retrieve url data from database and post the url's to the desired page.
Version:      0.1
Author:       Gerard de Way
Author URI:   https://github.com/gerard1987/location-page-filler
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  wporg
Domain Path:  /languages
Shortcode Syntax: [locationlinks] [pages_with_content]
*/

// Define global constants
if (!defined('plugin_dir')){
    define('plugin_dir', plugin_dir_path( __FILE__ ));
}

// Includes
include_once plugin_dir . 'includes/database_pages.php';
include_once plugin_dir . 'includes/check_location.php';
include_once plugin_dir . 'includes/write_url_data.php';

// Plugin updater
require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/gerard1987/location-page-filler.json',
	__FILE__,
	'write_url_to_page_updater'
);

// Plugin registration
register_activation_hook( __FILE__, array( 'dynamic_input', 'install' ) );

/**
 * Gets the pages with content, and checks if its a valid location and writes them to the location page
 */
class write_url_to_page 
{
    /**
     * Returns a obj with properties to build valid location url.
     */
    public function create_page_obj(){
        $url_array = [];
        $loc = [];
        $province_url_place = [];
        $provinces = [];

        // Check for each page with content if it exists in the csv file
        $check_location = new check_location;
        $check_location = $check_location->check_location();        

        // Get all the page's out of the database and check if there is content
        $database_pages = new database_pages;
        $db_array = $database_pages->get_db_pages();

        // For each database page with content, strip location name out of the path
        foreach ($db_array as $db_link) {
            
            $link = basename($db_link);

            $preg_result = [];
            preg_match('/-(.*)/', $link , $preg_result);
            
            $temp = $preg_result[1];

            strtolower($temp);

            array_push($loc, $temp);
            array_push($url_array, $db_link);
        }

        /**
         * Check for each loc name, if it exists in location array, and find second array item (province)
         * Make sure location page name is exact as the place name in array.
         */        
        foreach ($loc as $item) {

            $obj = (object) array(
                'place' => '',
                'province' => '',
                'url' => '',
            );

            $loc_trim = preg_replace("/[\W\-]/", ' ', $item);
            
            // Add temp province array, for validating cities and province's with same name.
            for ($i = 0; $i < count($check_location); $i += 2) {
                $provinces[] = $check_location[$i + 1];
            }
            
            // check page with content, wether its a location and add the properties to obj to build url.
            if (in_array($loc_trim, $check_location)) {
                $key = array_search($loc_trim, $check_location);
                $valid_loc = $item;

                $obj->place = $loc_trim;
                $obj->province = $check_location[$key + 1];

                // For duplicate name of place and province.
                if (in_array($loc_trim, $provinces)) {
                    $obj->province = $loc_trim;
                }
            }

            // Check of database url of matching item, and add to obj
            foreach ($url_array as $url) {
                if (strpos($url, $item) !== false && $item === $valid_loc) {
                    $obj->url = $url;
                }
            }
            array_push($province_url_place, $obj);
        }
        return $province_url_place;
    }

    /**
     * Check for every page with content if it's a valid location name, and export array
     */
    public function retrieve_page(){
        
        $province_array = [];

        $province_check = ['groningen', 'friesland', 'drenthe', 'overijssel', 'flevoland', 'gelderland', 'utrecht', 'noord-holland', 'zuid-holland', 'zeeland', 'noord-brabant', 'limburg'];

        // Dump all the objects inside obj array and check if province matches
        $write_url_to_page = new write_url_to_page();
        $province_url_place = $write_url_to_page->create_page_obj();

        // Skip empty obj's and create valid province array.
        foreach ($province_url_place as $url_obj) {
            $province = $url_obj->province;
            if (isset($province)) {
                    if (in_array($province, $province_check)){
                        array_push($province_array, $province);
                    }
            }
        }

        // Check the array for duplicate's and write the province's to page
        $write_url_to_page = new write_url_to_page();
        $check_duplicate = $write_url_to_page->check_duplicate($province_array);
        
        echo '<div class="col-md-12"><h3> Locaties </h3></div>';
        foreach($check_duplicate as $unique_province) {
            echo '<div class="col-md-3">' . '<strong>' . ucwords($unique_province) . '</strong>';
                    foreach($province_url_place as $item){
                        $province = $item->province;
                        $place = $item->place;
                        $url = $item->url;
                        if ($unique_province === $province){
                            // echo 'province\'s Match !! ' . '<br>';
                            echo '<p><a href="' . $url . '">' . ucfirst($place) . '</a></p>';
                        }
                    }
            echo '</div>';
        }
        return $loc_array;
    }

    public function check_duplicate ($province_array){
        $province_no_duplicate = array_unique($province_array);
        return $province_no_duplicate;
    }
} // End of Class

add_shortcode( 'locationlinks', array( 'write_url_to_page', 'retrieve_page' ));
add_shortcode( 'pages_with_content', array( 'write_url_data', 'advanza_url_write_locations' ));
