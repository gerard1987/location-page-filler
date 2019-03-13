<?php
/*
Plugin Name:  Advanza check Url data 
Plugin URI:   http://www.waydesign.nl
Description:  Retrieve url data from database
Version:      0.1
Author:       http://www.waydesign.nl
Author URI:   http://www.waydesign.nl
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  wporg
Domain Path:  /languages
Shortcode Syntax: [pagelinks]
*/

// Plugin updater

require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://advandm297.297.axc.nl/advanza-check-url-data.json',
	__FILE__,
	'write_url_data_updater'
);

// Plugin registration
register_activation_hook( __FILE__, array( 'dynamic_input', 'install' ) );

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
/**
 * Get csv file with location name's for validation
 */
class check_location
{
    /**
     * Get an array of a csv file of city names 
     * to determine wether its a valid city name
     */
    function check_location(){

        // Set variables for location
        $currentDirectory = getcwd();
        $Loc_url = plugin_dir_url( __FILE__ ) . 'includes/steden.csv';
        // Turn the csv file into a array
        $csv = array_map('str_getcsv', file($Loc_url));

        // Remove whitespace's and set case insensitive for check in array
        $new_csv = [];
        foreach ($csv as $item) {
            $item = preg_replace('/[;]/', '', $item);

            $new_item[0] = trim(strtolower($item[0]));
            $new_item[1] = trim(strtolower($item[1]));

            array_push($new_csv, $new_item[0]);
            array_push($new_csv, $new_item[1]);
        }
        return $new_csv;
    }
}// End of class

/**
 * Get the page results from the database and write them to the location page
 */
class write_url_to_page 
{

    public function create_page_obj(){
        $url_array = [];
        $loc = [];
        $province_url_place = [];

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

        // Check for each loc name, if it exists in location array, and find second array item (province)
        foreach ($loc as $item) {

            $obj = (object) array(
                'place' => '',
                'province' => '',
                'url' => '',
            );

            $loc_trim = preg_replace("/[\W\-]/", ' ', $item);
            /**
             * Issue with multiple valid location names (Bergen op zoom, Bergen)
             * Try to find literal preg match
             */
                // foreach ($check_location as $check_loc) {
                    
                //     $pattern = '/^(' . $loc_trim . '),(.+)/im';

                //     if (preg_match($pattern, $check_loc)) {
                //         $key = array_search($loc_trim, $check_location);
                //         $valid_loc = $item;
        
                //         $obj->place = $loc_trim;
                //         $obj->province = $check_location[$key + 1];
                //     }
                // }

            // WORKING DOES NOT GET EVERY PAGE
            if (in_array($loc_trim, $check_location)) {
                $key = array_search($loc_trim, $check_location);
                $valid_loc = $item;

                $obj->place = $loc_trim;
                $obj->province = $check_location[$key + 1];
            }
            foreach ($url_array as $url) {
                if (strpos($url, $item) !== false && $item === $valid_loc) {
                    // echo 'VALID URL ' . $url . '<br>';
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

        foreach ($province_url_place as $url_obj) {
            $province = $url_obj->province;
            if (isset($province)) {
                    if (in_array($province, $province_check)){
                        array_push($province_array, $province);
                    }
            }
        }

        $write_url_to_page = new write_url_to_page();
        $check_duplicate = $write_url_to_page->check_duplicate($province_array);

        foreach($check_duplicate as $unique_province) {
            echo '<div class="col-md-3">' . $unique_province;
                    foreach($province_url_place as $item){
                        $province = $item->province;
                        $place = $item->place;
                        $url = $item->url;
                        if ($unique_province === $province){
                            // echo 'province\'s Match !! ' . '<br>';
                            echo '<p><a href="' . $url . '">' . $place . '</a></p>';
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


// add_shortcode( 'pagelinks', array( 'write_url_data', 'advanza_url_write_locations' ));
add_shortcode( 'testlinks', array( 'write_url_to_page', 'retrieve_page' ));