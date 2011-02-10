<?php

/**
 * CLI Helper
 *
 * @author Kohana-Team http://kohanaframework.org
 * @homepage https://github.com/witty/helpers/blob/master/cli.php
 * @version 0.1.0
 */
class Cli {

	/**
	 * return one or more options
	 * standard CLI syntax:
	 *
	 * <pre>
	 * php index.php --username=john.smith --password=secret --var="some value with spaces"
	 *
	 * // Get the values of "username" and "password"
	 * $auth = CLI::options('username', 'password');
	 * </pre>
	 *
	 * @param   string  option name
	 * @param   ...
	 * @return  array
	 */
	public static function options($options) {
		// Get all of the requested options
		$options = func_get_args();

		// Found option values
		$values = array();

		// Skip the first option, it is always the file executed
		for ($i = 1; $i < $_SERVER['argc']; $i++) {
			if ( ! isset($_SERVER['argv'][$i])) {
				// No more args left
				break;
			}

			// Get the option
			$opt = $_SERVER['argv'][$i];

			if (substr($opt, 0, 2) !== '--') {
				// This is not an option argument
				continue;
			}

			// Remove the "--" prefix
			$opt = substr($opt, 2);

			if (strpos($opt, '=')) {
				// Separate the name and value
				list ($opt, $value) = explode('=', $opt, 2);
			} else {
				$value = NULL;
			}

			if (in_array($opt, $options)) {
				// Set the given value
				$values[$opt] = $value;
			}
		}

		return $values;
	}

}
