<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Formatting;

/**
 * StringFormatter class.
 */
final class StringFormatter {
	/**
	 * Adds proper indentation to a string.
	 *
	 * @param string $str The string to indent.
	 * @param array  $options The options for the indentation.
	 * @return string Indented string.
	 */
	public static function indent_string( string $str, array $options = [] ): string {
		$skip_first_line = $options['skip_first_line'] ?? false;
		$indent_count = $options['indent_count'] ?? 1;
		$indent_char = $options['indent_char'] ?? "\t";
	
		$lines = explode( "\n", $str );
		$result = [];

		if ( $skip_first_line ) {
			if ( count( $lines ) <= 1 ) {
				return $str;
			}

			// Keep first line as is
			$result[] = array_shift( $lines );
		}
		
		// Add indentation to remaining lines
		$indent = str_repeat( $indent_char, $indent_count );
		foreach ( $lines as $line ) {
			$result[] = $indent . $line;
		}
		
		return implode( "\n", $result );
	}
}
