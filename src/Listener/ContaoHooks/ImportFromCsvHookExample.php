<?php

declare(strict_types=1);

/*
 * This file is part of Import From CSV Bundle.
 *
 * (c) Marko Cupic 2021 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/import-from-csv-bundle
 */

/**
 * Run in a custom namespace, so the class can be replaced.
 */

namespace Markocupic\ImportFromCsvBundle\Listener\ContaoHooks;

/**
 * Class ImportFromCsvHookExample.
 */
class ImportFromCsvHookExample
{
    /**
     * cURL error messages.
     */
    private $curlErrorMsg;

    /**
     * @param null $objBackendModule
     */
    public function addGeolocation(array $arrCustomValidation, $objBackendModule = null): array
    {
        /**
         * $arrCustomValidation = array(.
         *
         * 'strTable' => 'tablename',
         * 'arrDCA' => 'Datacontainer array (DCA) of the current field.',
         * 'fieldname' => 'fieldname',
         * 'value' => 'value',
         * 'arrayLine' => 'Contains the current line/dataset as associative array.',
         * 'line' => 'current line in the csv-spreadsheet',
         * 'objCsvFile' => 'the Contao file object'
         * 'objCsvReader => 'the League\Csv\Reader object
         * 'skipWidgetValidation' => 'Skip widget-input-validation? (default is set to false)',
         * 'hasErrors' => 'Should be set to true if custom validation fails. (default is set to false)',
         * 'errorMsg' => 'Define a custom text message if custom validation fails.',
         * 'doNotSave' => 'Set this item to true if you don't want to save the datarecord into the database. (default is set to false)',
         * 'blnTestMode' => 'If set to 'true' the import runs in test mode, and there will be no inserts. (default is set to false)'
         * );
         */

        // tl_member
        if ('tl_member' === $arrCustomValidation['strTable']) {
            // Get geolocation from a given address
            if ('geolocation' === $arrCustomValidation['fieldname']) {
                // Do custom validation and skip the Contao-Widget-Input-Validation
                $arrCustomValidation['skipWidgetValidation'] = true;

                $strStreet = $arrCustomValidation['arrayLine']['street'];
                $strCity = $arrCustomValidation['arrayLine']['city'];
                $strCountry = $arrCustomValidation['arrayLine']['country'];

                $strStreet = str_replace(' ', '+', $strStreet);
                $strCity = str_replace(' ', '+', $strCity);
                $strAddress = $strStreet.',+'.$strCity.',+'.$strCountry;

                // Get Position from GoogleMaps
                $arrPos = $this->curlGetCoordinates(sprintf('http://maps.googleapis.com/maps/api/geocode/json?address=%s&sensor=false', $strAddress));

                if (\is_array($arrPos['results'][0]['geometry'])) {
                    $latPos = $arrPos['results'][0]['geometry']['location']['lat'];
                    $lngPos = $arrPos['results'][0]['geometry']['location']['lng'];

                    // Save geolocation in $arrCustomValidation['value']
                    $arrCustomValidation['value'] = $latPos.','.$lngPos;
                } else {
                    // Error handling
                    if ('' !== $this->curlErrorMsg) {
                        $arrCustomValidation['errorMsg'] = $this->curlErrorMsg;
                    } else {
                        $arrCustomValidation['errorMsg'] = sprintf('Setting geolocation for (%s) failed!', $strAddress);
                    }
                    $arrCustomValidation['hasErrors'] = true;
                    $arrCustomValidation['doNotSave'] = true;
                }
            }
        }

        return $arrCustomValidation;
    }

    /**
     * Curl helper method.
     *
     * @param $url
     *
     * @return bool|mixed
     */
    public function curlGetCoordinates($url)
    {
        // is cURL installed on the webserver?
        if (!\function_exists('curl_init')) {
            $this->curlErrorMsg = 'Sorry cURL is not installed on your webserver!';

            return false;
        }

        // Set a timout to avoid the OVER_QUERY_LIMIT
        usleep(25000);

        // Create a new cURL resource handle
        $ch = curl_init();

        // Set URL to download
        curl_setopt($ch, CURLOPT_URL, $url);

        // Should cURL return or print out the data? (true = return, false = print)
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Timeout in seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        // Download the given URL, and return output
        $arrOutput = json_decode(curl_exec($ch), true);

        // Close the cURL resource, and free system resources
        curl_close($ch);

        return $arrOutput;
    }
}