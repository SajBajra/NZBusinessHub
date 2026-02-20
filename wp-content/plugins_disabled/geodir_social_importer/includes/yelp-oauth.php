<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
 * Yelp Client for v3 API (named Yelp Fusion)
 */
class Geodir_Yelp
{
    /**
     *
     * @var string
     */
    protected $api_url = 'https://api.yelp.com/v3/';

    /**
     *
     * @var string
     */
    protected $api_key;

    /**
     *
     * @var string
     */
    protected $error = '';

    /**
     *
     * @var string
     */
    protected $response_type = 'json';

    /**
     *
     * @var string
     */
    protected $last_response = '';

    /**
     *
     * @param string $api_key
     * @throws Exception
     */
    public function __construct($api_key)
    {
        if (empty($api_key)) {
            throw new Exception('Yelp: invalid api_key');
        }

        $this->api_key = $api_key;
    }

	/**
	 *
	 * @param array $params
	 *      [term, location, latitude, longitude, radius ...]
	 *      See documentation for full list of params
	 *
	 * @return bool|mixed
	 */
    public function search(array $params)
    {
        return $this->get('businesses/search', $params);
    }

    /**
     * This endpoint returns the detail information of a business
     *
     * @param string $id
     *            The business id
     * @return mixed|boolean
     */
    public function business($id)
    {
        return $this->get('businesses/' . $id);
    }

    /**
     * This endpoint returns the up to three reviews of a business
     *
     * @param string $id
     *            The business id
     * @return mixed|boolean
     */
    public function reviews($id)
    {
        return $this->get('businesses/' . $id . '/reviews');
    }

    /**
     *
     * @param string $cmd
     *            Endpoint uri param for a command e.g. businesess/search
     * @param array $data
     *            (optional)
     * @param array $headers
     *            (optional)
     * @return mixed|boolean
     */
    protected function get($cmd, $data = array(), $headers = array())
    {
        $headers[] = 'Authorization: Bearer ' . $this->api_key;
        $headers[] = 'cache-control: no-cache';

        return $this->exec($this->api_url . $cmd, 'get', $data, $headers);
    }

    /**
     *
     * @param string $cmd
     *            Endpoint uri param for a command
     * @param array $data
     *            (optional)
     * @param array $headers
     *            (optional)
     * @return mixed|boolean
     */
    protected function post($cmd, $data = array(), $headers = array())
    {
        $headers[] = 'Authorization: Bearer ' . $this->api_key;
        $headers[] = 'cache-control: no-cache';

        return $this->exec($enpoint, $cmd, 'post', $data, $headers);
    }

    /**
     *
     * @param string $url
     *            The URL
     * @param string $method
     *            Allowed values "get" or "post"
     * @param array $data
     *            (optional) Associative array with parameters & values to send
     * @param array $headers
     *            (optional) Extra headers if needed
     * @return mixed array|false
     */
    protected function exec($url, $method = 'get', $data = array(), $headers = array())
    {
        $this->error = '';

        $ch = curl_init();

        if ($method == 'get') {
            $url .= '?' . http_build_query($data);
        }

        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false
        );

        if (! empty($headers)) {
            $options[CURLOPT_HTTPHEADER] = $headers;
        }

        if ($method == 'post') {
            $options[CURLOPT_POSTFIELDS] = http_build_query($data);
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);

        $this->last_response = $response;

        if ($errno = curl_errno($ch)) {
            $error_message = curl_error($ch);
            $this->error = 'Error: ' . $errno . ': ' . $error_message;
            return false;
        }

        if (! $response) {
            $this->error = __('Error: Invalid response from Yelp API', 'gd-social-importer');
            return false;
        }

        curl_close($ch);

        if ($this->response_type == 'json') {
            $response = json_decode($response, true);
        }

        if ($response === null) {
            $this->error = __('Error: Could not parse response from Yelp API', 'gd-social-importer');
            return false;
        }

        return $response;
    }

    /**
     *
     * @return string
     */
    public function get_error()
    {
        return $this->error;
    }

    /**
     *
     * @return string
     */
    public function last_response()
    {
        return $this->last_response;
    }
}
