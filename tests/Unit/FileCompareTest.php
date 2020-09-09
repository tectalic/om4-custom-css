<?php

namespace Test;

require_once(__DIR__ . '/../../om4-custom-css.php');

use PHPUnit\Framework\TestCase;
use OM4_Custom_CSS;

class FileCompareTest extends TestCase
{
	public function inputOutputProvider()
	{
		$buffer = [];
		$path = dirname(__DIR__) . '/inputs/example/';
		foreach (scandir($path) as $key => $value) {
			if (!in_array($value, [".", ".."])) {
				if (is_file($path . DIRECTORY_SEPARATOR . $value)) {
					$buffer[] = ['example/' . $value];
				}
			}
		}
		$path = dirname(__DIR__) . '/inputs/feature/';
		foreach (scandir($path) as $key => $value) {
			if (!in_array($value, [".", ".."])) {
				if (is_file($path . DIRECTORY_SEPARATOR . $value)) {
					$buffer[] = ['feature/' . $value];
				}
			}
		}
		return $buffer;
	}

	/** @dataProvider inputOutputProvider */
	public function testCompareInputOutput($name)
	{
		global $inputFile;
		global $outputFile;
		global $outputContent;

		$inputFile    = dirname(__DIR__) . "/inputs/$name";
		$outputFile   = dirname(__DIR__) . '/outputs/' . \str_replace('scss', 'css', $name);
		$customiser   = new OM4_Custom_CSS();
		$inputContent = $customiser->get_custom_css();
		$this->assertEquals(file_get_contents($inputFile), $inputContent);

		$customiser->save_custom_css_to_file();
		// Remove comment line.
		$output = substr($outputContent, strpos($outputContent, "\n") + 1) . "\n";

		$expected = file_get_contents($outputFile);
		if ($expected !== $output) {
			file_put_contents($outputFile . '-new.css', $output);
		}

		$this->assertEquals($expected, $output);
	}

	public function scssProvider()
    {
        return [
            [<<<'END_OF_SCSS'
.test {
  foo : bar;
END_OF_SCSS
                ,
                'unclosed block'
            ],
            [<<<'END_OF_SCSS'
.test {
}}
END_OF_SCSS
                ,
                'unexpected }'
            ],
            [<<<'END_OF_SCSS'
.test { color: #fff / 0; }
END_OF_SCSS
                ,
                'color: Can\'t divide by zero'
            ],
            [<<<'END_OF_SCSS'
.test {
  @include foo();
}
END_OF_SCSS
                ,
                'Undefined mixin foo'
            ],
            [<<<'END_OF_SCSS'
@mixin do-nothing() {
}

.test {
  @include do-nothing($a: "hello");
}
END_OF_SCSS
                ,
                'Mixin or function doesn\'t have an argument named $a.'
            ],
            array(<<<'END_OF_SCSS'
div {
  color: darken(cobaltgreen, 10%);
}
END_OF_SCSS
                ,
                'expecting color'
            ),
            [<<<'END_OF_SCSS'
BODY {
    DIV {
        $bg: red;
    }

    background: $bg;
}
END_OF_SCSS
                ,
                'Undefined variable $bg'
            ],
            [<<<'END_OF_SCSS'
@mixin example {
    background: $bg;
}

P {
    $bg: red;

    @include example;
}
END_OF_SCSS
                ,
                'Undefined variable $bg'
            ],
            [<<<'END_OF_SCSS'
a.important {
  @extend .notice;
}
END_OF_SCSS
                ,
                'was not found'
            ],
            [<<<'END_OF_SCSS'
@import "missing";
END_OF_SCSS
                ,
                'file not found for @import'
            ],
        ];
    }

	/** @dataProvider scssProvider */
	public function testCompileException($code, $message)
	{
		$this->expectExceptionMessage($message);

		global $inputContent;
		global $outputContent;

		$customiser    = new OM4_Custom_CSS();
		$inputContent  = $code;
		$loadedContent = $customiser->get_custom_css();
		$this->assertEquals($inputContent, $loadedContent);

		$customiser->save_custom_css_to_file();
	}

	public function utfProvider()
    {
        return [
            ['span { content: "😎";}', '"span{content:\"\ud83d\ude0e\"}"'],
            ['span { content: "\1F600";}', '"span{content:\"\ud83d\ude00\"}"'],
            ['span { content: "\0021";}', '"span{content:\"!\"}"'],
            ['span { content: "\01b1";}', '"span{content:\"\u01b1\"}"'],
            ['span { content: "\f0da ";}', '"span{content:\"\uf0da \"}"'],
            ['span { content: " ";}', '"span{content:\"\uf0da \"}"'],
        ];
    }

	/** @dataProvider utfProvider */
	public function testCompileUtf($code, $result)
	{
		global $inputContent;
		global $outputContent;

		$customiser    = new OM4_Custom_CSS();
		$inputContent  = $code;
		$loadedContent = $customiser->get_custom_css();
		$this->assertEquals($inputContent, $loadedContent);

		$customiser->save_custom_css_to_file();
		// Remove comment line.
		$output = json_encode(substr($outputContent, strpos($outputContent, "\n") + 1));
		$this->assertEquals($result, $output);
	}
}
