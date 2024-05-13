<?php

namespace Test;

require_once __DIR__ . '/../../om4-custom-css.php';

use OM4_Custom_CSS;
use PHPUnit\Framework\TestCase;

class FileCompareTest extends TestCase {

	/** @return array<string[]> */
	public function inputOutputProvider(): array {
		$buffer = array();
		$path   = dirname( __DIR__ ) . '/inputs/example/';
		$files  = scandir( $path );
		assert( is_array( $files ) );
		foreach ( $files as $value ) {
			if ( ! in_array( $value, array( '.', '..' ), true ) ) {
				if ( is_file( $path . DIRECTORY_SEPARATOR . $value ) ) {
					$buffer[] = array( 'example/' . $value );
				}
			}
		}
		$path  = dirname( __DIR__ ) . '/inputs/feature/';
		$files = scandir( $path );
		assert( is_array( $files ) );
		foreach ( $files as $value ) {
			if ( ! in_array( $value, array( '.', '..' ), true ) ) {
				if ( is_file( $path . DIRECTORY_SEPARATOR . $value ) ) {
					$buffer[] = array( 'feature/' . $value );
				}
			}
		}
		return $buffer;
	}

	/** @dataProvider inputOutputProvider */
	public function testCompareInputOutput( string $name ): void {
		global $input_file;
		global $output_file;
		global $output_content;

		$input_file    = dirname( __DIR__ ) . "/inputs/$name";
		$output_file   = dirname( __DIR__ ) . '/outputs/' . \str_replace( 'scss', 'css', $name );
		$customiser    = new OM4_Custom_CSS();
		$input_content = $customiser->get_custom_css();
		$this->assertEquals( file_get_contents( $input_file ), $input_content );

		$customiser->save_custom_css_to_file();
		// Remove comment line.
		$output = substr( $output_content, strpos( $output_content, "\n" ) + 1 ) . "\n";

		$expected = file_get_contents( $output_file );
		if ( $expected !== $output ) {
			file_put_contents( $output_file . '-new.css', $output );
		}

		$this->assertEquals( $expected, $output );
	}

	/** @return array<string[]> */
	public function scssProvider(): array {
		return [
			[
				<<<'END_OF_SCSS'
.test {
  foo : bar;
END_OF_SCSS
					,
				'unclosed block',
			],
			[
				<<<'END_OF_SCSS'
.test {
}}
END_OF_SCSS
				,
				'unexpected }',
			],
			[
				<<<'END_OF_SCSS'
.test { color: #fff / 0; }
END_OF_SCSS
				,
				'color: Can\'t divide by zero',
			],
			[
				<<<'END_OF_SCSS'
.test {
  @include foo();
}
END_OF_SCSS
				,
				'Undefined mixin foo',
			],
			[
				<<<'END_OF_SCSS'
@mixin do-nothing() {
}

.test {
  @include do-nothing($a: "hello");
}
END_OF_SCSS
				,
				'Mixin or function doesn\'t have an argument named $a.',
			],
			array(
				<<<'END_OF_SCSS'
div {
  color: darken(cobaltgreen, 10%);
}
END_OF_SCSS
				,
				'expecting color',
			),
			[
				<<<'END_OF_SCSS'
BODY {
    DIV {
        $bg: red;
    }

    background: $bg;
}
END_OF_SCSS
				,
				'Undefined variable $bg',
			],
			[
				<<<'END_OF_SCSS'
@mixin example {
    background: $bg;
}

P {
    $bg: red;

    @include example;
}
END_OF_SCSS
				,
				'Undefined variable $bg',
			],
			[
				<<<'END_OF_SCSS'
a.important {
  @extend .notice;
}
END_OF_SCSS
				,
				'was not found',
			],
			[
				<<<'END_OF_SCSS'
@import "missing";
END_OF_SCSS
				,
				'file not found for @import',
			],
		];
	}

	/** @dataProvider scssProvider */
	public function testCompileException( string $code, string $message ): void {
		$this->expectExceptionMessage( $message );

		global $input_content;
		global $output_content;

		$customiser     = new OM4_Custom_CSS();
		$input_content  = $code;
		$loaded_content = $customiser->get_custom_css();
		$this->assertEquals( $input_content, $loaded_content );

		$customiser->save_custom_css_to_file();
	}

	/** @return array<string[]> */
	public function utfProvider(): array {
		return [
			[ 'span { content: "😎";}', '"span{content:\"\ud83d\ude0e\"}"' ],
			[ 'span { content: "\1F600";}', '"span{content:\"\ud83d\ude00\"}"' ],
			[ 'span { content: "\0021";}', '"span{content:\"!\"}"' ],
			[ 'span { content: "\01b1";}', '"span{content:\"\u01b1\"}"' ],
			[ 'span { content: "\f0da ";}', '"span{content:\"\uf0da \"}"' ],
			[ 'span { content: " ";}', '"span{content:\"\uf0da \"}"' ],
		];
	}

	/** @dataProvider utfProvider */
	public function testCompileUtf( string $code, string $result ): void {
		global $input_content;
		global $output_content;

		$customiser     = new OM4_Custom_CSS();
		$input_content  = $code;
		$loaded_content = $customiser->get_custom_css();
		$this->assertEquals( $input_content, $loaded_content );

		$customiser->save_custom_css_to_file();
		// Remove comment line.
		$output = json_encode( substr( $output_content, strpos( $output_content, "\n" ) + 1 ) );
		$this->assertEquals( $result, $output );
	}
}
