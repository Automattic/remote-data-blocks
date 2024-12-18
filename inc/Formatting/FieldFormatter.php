<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Formatting;

use NumberFormatter;
use function get_locale;

/**
 * FieldFormatter class.
 */
final class FieldFormatter {
	/**
	 * Format a number as a currency.
	 *
	 * @psalm-suppress UnusedPsalmSuppress
	 * @psalm-suppress UndefinedClass
	 */
	public static function format_currency( mixed $value, string $iso_4127_currency_code = 'USD', ?string $locale = null ): string {
		$format = numfmt_create( $locale ?? get_locale(), NumberFormatter::CURRENCY );
		return numfmt_format_currency( $format, (float) $value, $iso_4127_currency_code );
	}
}
