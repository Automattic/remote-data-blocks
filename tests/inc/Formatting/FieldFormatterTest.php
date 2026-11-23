<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Formatting;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Formatting\FieldFormatter;

final class FieldFormatterTest extends TestCase {
	public function test_format_markdown_renders_markdown(): void {
		$this->assertSame(
			'<p>A <strong>bold</strong> <a href="https://example.com">link</a></p>',
			FieldFormatter::format_markdown( 'A **bold** [link](https://example.com)' )
		);
	}

	public function test_format_markdown_preserves_raw_html(): void {
		$this->assertSame(
			'<p>A <em>formatted</em> value</p>',
			FieldFormatter::format_markdown( 'A <em>formatted</em> value' )
		);
	}
}
