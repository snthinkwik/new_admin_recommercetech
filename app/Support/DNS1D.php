<?php namespace App\Support;

use Milon\Barcode\DNS1D as BASE_DNS1D;
use Illuminate\Support\Str;

class DNS1D extends BASE_DNS1D
{

	/**
	 * Return a PNG image representation of barcode (requires GD or Imagick library).
	 * @param $code (string) code to print
	 * @param $type (string) type of barcode: <ul><li>C39 : CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9.</li><li>C39+ : CODE 39 with checksum</li><li>C39E : CODE 39 EXTENDED</li><li>C39E+ : CODE 39 EXTENDED + CHECKSUM</li><li>C93 : CODE 93 - USS-93</li><li>S25 : Standard 2 of 5</li><li>S25+ : Standard 2 of 5 + CHECKSUM</li><li>I25 : Interleaved 2 of 5</li><li>I25+ : Interleaved 2 of 5 + CHECKSUM</li><li>C128 : CODE 128</li><li>C128A : CODE 128 A</li><li>C128B : CODE 128 B</li><li>C128C : CODE 128 C</li><li>EAN2 : 2-Digits UPC-Based Extention</li><li>EAN5 : 5-Digits UPC-Based Extention</li><li>EAN8 : EAN 8</li><li>EAN13 : EAN 13</li><li>UPCA : UPC-A</li><li>UPCE : UPC-E</li><li>MSI : MSI (Variation of Plessey code)</li><li>MSI+ : MSI + CHECKSUM (modulo 11)</li><li>POSTNET : POSTNET</li><li>PLANET : PLANET</li><li>RMS4CC : RMS4CC (Royal Mail 4-state Customer Code) - CBC (Customer Bar Code)</li><li>KIX : KIX (Klant index - Customer index)</li><li>IMB: Intelligent Mail Barcode - Onecode - USPS-B-3200</li><li>CODABAR : CODABAR</li><li>CODE11 : CODE 11</li><li>PHARMA : PHARMACODE</li><li>PHARMA2T : PHARMACODE TWO-TRACKS</li></ul>
	 * @param $w (int) Width of a single bar element in pixels.
	 * @param $h (int) Height of a single bar element in pixels.
	 * @param $color (array) RGB (0-255) foreground color for bar elements (background is transparent).
	 * @return image or false in case of error.
	 * @public
	 */
	public function getBarcodePNG($code, $type, $w = 2, $h = 30, $color = array(0, 0, 0), $printText = false) {
		if (!$this->store_path) {
			$this->setStorPath(\Config::get("barcode.store_path"));
		}
		$this->setBarcode($code, $type);
		// calculate image size
		$width = ($this->barcode_array['maxw'] * $w) + 10;
		$height = $h;
		if (function_exists('imagecreate')) {
			// GD library
			$imagick = false;
			$png = imagecreate($width, $height);
			$bgcol = imagecolorallocate($png, 255, 255, 255);
			imagecolortransparent($png, $bgcol);
			$fgcol = imagecolorallocate($png, $color[0], $color[1], $color[2]);
		} elseif (extension_loaded('imagick')) {
			$imagick = true;
			$bgcol = new \imagickpixel('rgb(255,255,255');
			$fgcol = new \imagickpixel('rgb(' . $color[0] . ',' . $color[1] . ',' . $color[2] . ')');
			$png = new \Imagick();
			$png->newImage($width, $height, 'none', 'png');
			$bar = new \imagickdraw();
			$bar->setfillcolor($fgcol);
		} else {
			return false;
		}



		// print bars
		$x = 5;
		//dd($this->barcode_array['bcode']);
		foreach ($this->barcode_array['bcode'] as $k => $v) {
			$bw = round(($v['w'] * $w), 3);
			$bh = round(($v['h'] * $h / $this->barcode_array['maxh']), 3);
			$bh = $bh - 20;

			if ($v['t']) {
				$y = round(($v['p'] * $h / $this->barcode_array['maxh']), 3);
				$y = 5;
				// draw a vertical bar
				if ($imagick) {
					$bar->rectangle($x, $y, ($x + $bw), ($y + $bh));
				} else {
					imagefilledrectangle($png, $x, $y, ($x + $bw) - 1, ($y + $bh), $fgcol);
				}
			}
			$x += $bw;
		}
		$file_name= Str::slug($code);
		$save_file = $this->checkfile($this->store_path . $file_name . ".png");



		if ($imagick) {
			$png->drawimage($bar);
			//echo $png;
		}

		if ($printText) {
			$len = strlen($code);
			$tw = $len * imagefontwidth(5);
			$xpos = ($width - $tw) / 2;
			imagestring ( $png, 5, $xpos, $bh+5, $code, $fgcol );
		}

		$color = imagecolorallocate($png, 0, 0, 0);
		$thickness = 0;
		$x1 = 0;
		$y1 = 0;
		$x2 = imagesx($png) - 1;
		$y2 = imagesy($png) - 1;

		for($i = 0; $i < $thickness; $i++)
		{

			imagerectangle($png, $x1++, $y1++, $x2--, $y2--, $color);
		}



		ob_start();
		// get image out put
		if ($imagick) {
			$png->drawimage($bar);
			echo $png;
		} else {
			imagepng($png);
			imagedestroy($png);
		}
		$image = ob_get_clean();
		$image = base64_encode($image);
		//$image = 'data:image/png;base64,' . base64_encode($image);
		return $image;
	}

}