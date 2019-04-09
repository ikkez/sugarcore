<?php

namespace Sugar\Utility;

class CLI extends \Prefab {

	const ESC="\33";

	protected $colors = [
		'black',
		'red',
		'green',
		'yellow',
		'blue',
		'magenta',
		'cyan',
		'white',
		'-',
		'default'
	];


	protected $styles = [
		'normal',
		'bold',
		'faint',
		'standout',
		'underline',
		'blink',
		'-',
		'invert',
		'invisible',
	];


	/**
	 * render a colorized and styled string
	 * @param $value
	 * @param null $color
	 * @param null $background
	 * @param null $styles
	 * @return string
	 */
	function string($value,$color=NULL,$background=NULL,$styles=null) {
		$format=[];

		if ($id=array_search($color, $this->colors))
			$format[]='3'.$id;

		if ($id=array_search($background, $this->colors))
			$format[]='4'.$id;

		if ($styles) {
			if (is_string($styles))
				$styles = \Base::instance()->split($styles);
			foreach ($styles as $style)
				if ($id=array_search($style, $this->styles))
					$format[]=$id;
		}

		return ($format)
			? self::ESC.'['.implode(';',$format)."m".$value.self::ESC.'[0m'
			: $value;
	}


	/**
	 * clear console screen
	 */
	function clear() {
		echo self::ESC."[2J"."\n\r";
	}


	/**
	 * disable any existing buffer to force instant console output
	 */
	function noBuffer(){
		while (@ob_end_flush())
			ob_implicit_flush(true);
	}


	/**
	 * render a two-column list item
	 * @param string $left
	 * @param string $right
	 * @param int $l_width
	 * @param int $max
	 * @param int $gutter_width
	 * @return string
	 */
	function paddedItem($left,$right,$l_width=26,$max=78,$gutter_width=2) {
		$gutter=str_repeat(' ',$gutter_width);
		$left = $gutter.$left.$gutter;
		$lw = strlen($left);
		$out= $this->string($left,'cyan',NULL);
		$padding = str_repeat('.',max($l_width-$lw-$gutter_width,0)).$gutter;
		$out.= $this->string($padding,'white',NULL,'faint');
		$out.=$this->multiRow($right,$max-$l_width,$l_width);
		return $out;
	}

	/**
	 * render a sub-item within a two-column list
	 * @param $left
	 * @param $right
	 * @param int $l_width
	 * @param int $max
	 * @param int $gutter_width
	 * @return string
	 */
	function paddedSubItem($left,$right,$l_width=26,$max=78,$gutter_width=2) {
		$gutter=str_repeat(' ',$gutter_width);
		$lw = strlen($left)+2+($gutter_width*2);
		$left = $gutter.'⤷ '.$left.$gutter;
		$out= $this->string($left,'yellow',NULL);
		$padding = str_repeat('.',max($l_width-$lw-$gutter_width,0)).$gutter;
		$out.= $this->string($padding,'white',NULL,'faint');
		$out.=$this->multiRow($right,$max-$l_width,$l_width);
		return $out;
	}

	/**
	 * render multi-line text to a certain width, optionally indent on 2nd+ row
	 * @param $text
	 * @param int $width
	 * @param int $indentOn2ndRow
	 * @return string
	 */
	function multiRow($text,$width=30,$indentOn2ndRow=0) {
		$out = '';
		if (strlen($text) > $width) {
			$text = wordwrap($text,$width);
			$text = explode("\n",$text);
			foreach ($text as $i=>$line)
				$out.= $this->string((($i>0)?str_repeat(' ',$indentOn2ndRow):'').$line).PHP_EOL;
		} else
			$out.= $this->string($text).PHP_EOL;
		return $out;
	}

	/**
	 * render an error message
	 * @param $text
	 * @param null $code
	 * @return string
	 */
	function errorString($text,$code=NULL) {
		$out = $this->string(' ERROR '.($code?$code.' ':''),'white','red','blink').' ';
		$out.=$this->string($text,'red').PHP_EOL;
		return $out;
	}

	/**
	 * render a success message
	 * @param $text
	 * @return string
	 */
	function successString($text) {
		$out = $this->string(' SUCCESS ','green',null,'invert').' ';
		$out.=$this->string($text,'green').PHP_EOL;
		return $out;
	}
}