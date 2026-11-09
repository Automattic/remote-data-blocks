<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Formatting;

use NumberFormatter;
use Parsedown;
use function get_locale;

/**
 * FieldFormatter class.
 */
final class FieldFormatter {
	private static ?Parsedown $markdown_parser = null;

	/**
	 * Format a number as a currency.
	 *
	 * @psalm-suppress UnusedPsalmSuppress
	 * @psalm-suppress UndefinedClass
	 */
	public static function format_currency( mixed $value, ?string $iso_4127_currency_code = null, ?string $locale = null ): string {
		// The PHP 'intl' extension is not available in WordPress Playground.
		if ( ! class_exists( 'NumberFormatter' ) || ! function_exists( 'numfmt_create' ) ) {
			return strval( $value );
		}

		$format = numfmt_create( $locale ?? get_locale(), NumberFormatter::CURRENCY );
		$currency_code = $iso_4127_currency_code ?? $format->getTextAttribute( NumberFormatter::CURRENCY_CODE );
		return numfmt_format_currency( $format, (float) $value, $currency_code );
	}

	/**
	 * Format markdown as HTML.
	 *
	 * Remote markdown is untrusted. Safe mode escapes raw HTML in the markdown,
	 * drops event handler attributes, and neutralizes unsafe URL schemes in
	 * links and images. We use a private parser instance so that safe mode does
	 * not leak into other code that shares the global Parsedown::instance().
	 */
	public static function format_markdown( string $value ): string {
		if ( null === self::$markdown_parser ) {
			self::$markdown_parser = new Parsedown();
			self::$markdown_parser->setSafeMode( true );
		}

		return self::$markdown_parser->text( $value );
	}
}
