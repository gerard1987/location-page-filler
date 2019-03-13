<?php
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
        $Loc_url = plugin_dir_path( __FILE__ ) . 'steden.csv';
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