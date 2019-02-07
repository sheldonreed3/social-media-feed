<?php

namespace Drupal\social_feeds_data\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\social_feeds_data\Import\SocialFeedsDataImport;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class SocialFeedsDataAPI {
    private $headers = ['Access-Control-Allow-Origin' => '*'];

    public function get(Request $request)
    {
        // This condition checks the `Content-type` and makes sure to
        // decode JSON string from the request body into array.
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), TRUE);
            $request->request->replace(is_array($data) ? $data : []);
        }

        $response['data'] = $this->getData();
        $response['method'] = 'GET';

        return new JsonResponse($response, 200, $this->headers);
    }

    public function post(Request $request)
    {
        // This condition checks the `Content-type` and makes sure to
        // decode JSON string from the request body into array.
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), TRUE);
            $request->request->replace(is_array($data) ? $data : []);
        }

        $response['data'] = $this->getData();
        $response['method'] = 'POST';

        return new JsonResponse($response, 200, $this->headers);
    }

    private function getData() {
        $feed = new SocialFeedsDataImport();
        return $feed->get_aggregated_data();
    }
}