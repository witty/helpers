<?php
/**
 * URL helper class.
 *
 * @author Kohana-Team http://kohanaframework.org
 * @homepage https://github.com/witty/helpers/blob/master/url.php
 * @version 0.1.0
 */
class Url {

	/**
	 * Gets the base URL to the application.
	 *
	 * @param   mixed    $protocol Protocol string, or [Request]
	 * @param   boolean  $index    Add index file to URL?
	 * @return  string
	 */
	public static function base()
	{
		return Witty::instance('Request')->base_url;
	}

	/**
	 * Fetches an absolute site URL based on a URI segment.
	 *
	 *     echo URL::site('foo/bar');
	 *
	 * @param   string  $uri        Site URI to convert
	 * @return  string
	 * @uses    URL::base
	 */
	public static function site($uri = '')
	{
		// Chop off possible scheme, host, port, user and pass parts
		$path = preg_replace('~^[-a-z0-9+.]++://[^/]++/?~', '', trim($uri, '/'));

		// Concat the URL
		return URL::base().'/'.$path;
	}

	/**
	 * Merges the current GET parameters with an array of new or overloaded
	 * parameters and returns the resulting query string.
	 *
	 *     // Returns "?sort=title&limit=10" combined with any existing GET values
	 *     $query = URL::query(array('sort' => 'title', 'limit' => 10));
	 *
	 * Typically you would use this when you are sorting query results,
	 * or something similar.
	 *
	 * [!!] Parameters with a NULL value are left out.
	 *
	 * @param   array    $params   Array of GET parameters
	 * @param   boolean  $use_get  Include current request GET parameters
	 * @return  string
	 */
	public static function query(array $params = NULL, $use_get = TRUE)
	{
		if ($use_get)
		{
			if ($params === NULL)
			{
				// Use only the current parameters
				$params = $_GET;
			}
			else
			{
				// Merge the current and new parameters
				$params = array_merge($_GET, $params);
			}
		}

		if (empty($params))
		{
			// No query parameters
			return '';
		}

		// Note: http_build_query returns an empty string for a params array with only NULL values
		$query = http_build_query($params, '', '&');

		// Don't prepend '?' to an empty string
		return ($query === '') ? '' : ('?'.$query);
	}

}
