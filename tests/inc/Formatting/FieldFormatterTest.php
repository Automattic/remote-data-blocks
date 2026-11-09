<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Formatting;

use Parsedown;
use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Formatting\FieldFormatter;

final class FieldFormatterTest extends TestCase {
	public function test_format_markdown_renders_markdown(): void {
		$this->assertSame(
			'<p>A <strong>bold</strong> <a href="https://example.com">link</a></p>',
			FieldFormatter::format_markdown( 'A **bold** [link](https://example.com)' )
		);
	}

	/**
	 * @dataProvider provide_unsafe_markdown
	 */
	public function test_format_markdown_escapes_unsafe_markup( string $markdown, string $expected ): void {
		$this->assertSame( $expected, FieldFormatter::format_markdown( $markdown ) );
	}

	public static function provide_unsafe_markdown(): array {
		return [
			'script block' => [
				"<script>alert(1)</script>\n\nText",
				"<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>\n<p>Text</p>",
			],
			'inline script' => [
				'Text <script>alert(1)</script>',
				'<p>Text &lt;script&gt;alert(1)&lt;/script&gt;</p>',
			],
			'inline image with event handler' => [
				'<img src=x onerror="alert(document.domain)">',
				'<p>&lt;img src=x onerror="alert(document.domain)"&gt;</p>',
			],
			'html block' => [
				"<div onclick=\"alert(1)\">\nText\n</div>",
				"<p>&lt;div onclick=\"alert(1)\"&gt;\nText\n&lt;/div&gt;</p>",
			],
			'javascript link' => [
				'[link](javascript:alert(1))',
				'<p><a href="javascript%3Aalert(1)">link</a></p>',
			],
			'data url link' => [
				'[link](data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==)',
				'<p><a href="data%3Atext/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">link</a></p>',
			],
			'javascript image' => [
				'![alt](javascript:alert(1))',
				'<p><img src="javascript%3Aalert(1)" alt="alt" /></p>',
			],
		];
	}

	public function test_format_markdown_does_not_change_shared_parsedown_instance(): void {
		FieldFormatter::format_markdown( '<b>x</b>' );

		$this->assertSame( '<p><b>x</b></p>', Parsedown::instance()->text( '<b>x</b>' ) );
	}
}
