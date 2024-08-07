<?php

namespace Drupal\dhl_location_finder\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;
class LocationController extends ControllerBase {

  public function results(Request $request) {
    // Get query parameters from the request.
    $countryCode = $request->query->get('country');
    $city = $request->query->get('city');
    $postal_code = $request->query->get('postalCode');
    $apiKey = '8GxxEnGtdaHkkGFGbkGKd2y0xtkl9Ksa'; // Replace with your actual API key.

    $client = new Client();

    try {
      //  API request to the DHL Location Finder API.
      $response = $client->request('GET', 'https://api.dhl.com/location-finder/v1/find-by-address', [
        'headers' => [
          'DHL-API-Key' => $apiKey,
        ],
        'query' => [
          'countryCode' => $countryCode,
          'addressLocality' => $city,
          'postalCode' => $postal_code,
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      // Check for the 'locations' key in the response data.
      if (!isset($data['locations']) || empty($data['locations'])) {
        return new Response('No locations found.', Response::HTTP_NOT_FOUND, ['Content-Type' => 'application/x-yaml']);
      }


      $filteredLocations = [];
      foreach ($data['locations'] as $location) {
        $openingHours = $location['openingHours'];
        $openOnWeekend = false;

        // Iterate through opening hours to check for weekend availability.
        foreach ($openingHours as $hours) {
          if (strpos($hours['dayOfWeek'], 'Saturday') !== false || strpos($hours['dayOfWeek'], 'Sunday') !== false) {
            $openOnWeekend = true;
            break;
          }
        }

        if (!$openOnWeekend) {
          continue; // Skip locations not open on weekends.
        }
// Filter the results to include only locations with odd street numbers.
/* $filteredLocations = [];
foreach ($data['locations'] as $location) {
  $streetAddress = $location['place']['address']['streetAddress'];
  preg_match('/\d+/', $streetAddress, $matches);

  if (!empty($matches) && (int)$matches[0] % 2 !== 0) {
    // Add the location to the filtered list if it has an odd street number.
    $filteredLocations[] = $location;
  }
} */
        // Remove locations with odd street numbers.
        $streetAddress = $location['place']['address']['streetAddress'];
        preg_match('/\d+/', $streetAddress, $matches);
        if (!empty($matches) && (int)$matches[0] % 2 !== 0) {
          continue; // Skip odd-numbered addresses.
        }

        // Add the location to the filtered list.
        $filteredLocations[] = $location;
      }
  /*     $responseData = '';
      foreach ($filteredLocations as $location) {
        $responseData .= '<div class="location-item">';
        $responseData .= '<h3>Location Name: ' . htmlspecialchars($location['name']) . '</h3>';
        $responseData .= '<h3>Address: </h3>';
        $responseData .= '<p>Street Address:' . htmlspecialchars($location['place']['address']['streetAddress']) . '</p>';
        $responseData .= '<p>Address Locality: ' . htmlspecialchars($location['place']['address']['addressLocality']) . ', ' . htmlspecialchars($location['place']['address']['postalCode']) . '</p>';
        $responseData .= '<p>CountryC ode: ' . htmlspecialchars($location['place']['address']['countryCode']) . '</p>';
        $responseData .= '<p>Postal Code:: ' . htmlspecialchars($location['place']['address']['postalCode:']) . '</p>';
        $responseData .= '<h3>Opening Hours:</h3>';
        $responseData .= '<ul>';
        foreach ($location['openingHours'] as $hours) {
          $day = str_replace('http://schema.org/', '', $hours['dayOfWeek']);
          $responseData .= '<li>' . htmlspecialchars($day) . ': ' . htmlspecialchars($hours['opens']) . ' - ' . htmlspecialchars($hours['closes']) . '</li>';
        }
        $responseData .= '</ul>';
        $responseData .= '</div>';
      }
      return [
        '#markup' => $responseData,
      ]; */

      $yamlData = [];
      foreach ($filteredLocations as $location) {
        $yamlData[] = [
          'locationName' => $location['name'],
          'address' => [
            'countryCode' => $location['place']['address']['countryCode'],
            'postalCode' => $location['place']['address']['postalCode'],
            'addressLocality' => $location['place']['address']['addressLocality'],
            'streetAddress' => $location['place']['address']['streetAddress'],
          ],
          'openingHours' => $location['openingHours'],
        ];
      }

      // Convert the data to YAML format.
      $yamlResponse = Yaml::dump(['locations' => $yamlData], 2, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
      return [
        '#markup' => '
          <div>
            <h2>Location Results</h2>
            <p>Here are the results in YAML format:</p>
            <pre style="max-width: 100%; white-space: pre-wrap; word-wrap: break-word;">' . htmlspecialchars($yamlResponse, ENT_QUOTES, 'UTF-8') . '</pre>
          </div>
        ',
    ];
/*
      return new Response($yamlResponse, Response::HTTP_OK, [
        'Content-Type' => 'application/x-yaml',
        'Content-Disposition' => 'inline; filename="locations.yaml"',
      ]);
 */

    } catch (\Exception $e) {
      // Log the error
      \Drupal::logger('dhl_location_finder')->error($e->getMessage());
      $yamlResponse = Yaml::dump(['error' => 'Unable to fetch locations.'], 2, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
      return [
        '#markup' => '<pre>' . htmlspecialchars($yamlResponse) . '</pre>',
      ];
      /* return new Response($yamlResponse, Response::HTTP_INTERNAL_SERVER_ERROR, [
        'Content-Type' => 'application/x-yaml',
        'Content-Disposition' => 'inline; filename="error.yaml"',
      ]); */
    }
  }
}
