<?php
/**
 * Checks all pages with content, against links on location page. 
 * Displays links of pages with content, but not on the location page.
 */
class write_url_data {

    // Plugin install
    static function install() {
    // do not generate any output here
    } 
    /**
     * Checks all pages with content, against links on location page. 
     * Displays links of pages with content, but not on the location page.
     */
    public function advanza_url_write_locations($arr){
            // Declare variables          
            $link_array = [];
            
             // Get all the page's out of the database and check if there is content
            $database_pages = new database_pages;
            $url_array = $database_pages->get_db_pages();

             // Use curl to get statuscode for each page
            for ($i = 0; $i < count($url_array); $i++){
                $url = $url_array[$i];

                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_NOBODY, true);
                $result = curl_exec($curl);

                if ($result !== false) {
                    // check response code
                    $statuscode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                }
            }

            /**
             * Get the content from location page
             * and use xpath to get all the page links inside <article> element
             */
            $loc_url = 'http://' . $_SERVER['SERVER_NAME'] . '/' . 'locaties/';
            $html = file_get_contents( $loc_url);

            $dom = new DOMDocument;
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            $node = $xpath->query('//article//a/@href');

            /**
             * Check the links from the location page against the database if the page exists
             */
            echo '<div style="padding:20px;">';
            echo '<h1>Location Url\'s without a page (404)</h1>';
            foreach($node as $item) {
                $link = $item->nodeValue;
                array_push($link_array, $link);
                if (!in_array($link, $url_array)){
                    echo 'ON LOCATION PAGE BUT NOT A PAGE !! :: <code>' . $link . '</code>' . $statuscode . '<br>';
                }
            }
            echo '</div>';
           
            /**
             * Check if the database page with content exists on the location page with a relative URL
             */
            echo '<div style="padding:20px; background: #f5f5f5;">';
            echo '<h2>Page\'s with content but not on Location page</h2>';
            // Check for relative links
            foreach ($url_array as $db_url) {
                $db_array = parse_url($db_url, PHP_URL_PATH);
                $path_array = [];
                foreach ($link_array as $link) {
                    parse_url($link, PHP_URL_PATH);
                    array_push($path_array, $link);
                } if (!in_array($db_url, $path_array)) {
                    echo 'NOT ON LOCATION PAGE BUT PAGE EXISTS : <code>' . $db_url . '</code>' . $statuscode . '<br>';
                } else if (in_array($db_url, $path_array)) {
                    // echo 'RELATIVE page exists and on location page !! : <code>' . $db_url . '</code><br>';
                }
            }
            echo '</div>';
        return $url_array;
    }
} // End of Class
