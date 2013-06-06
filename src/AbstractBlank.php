<?php

/**
 * AbstractBlank class file.
 *
 * @author Victor Zhuk <chewire@gmail.com>
 * @copyright Copyright &copy; 2013 Victor Zhuk
 * @license http://www.yiiframework.com/license/
 * @package yii-printblank
 * @since 1.0
 */

class AbstractBlank extends CComponent
{

	private $_imageResource = NULL;

	private $_composeContent = array();

    private $_params = array();

    public function __construct($params = array())
    {
        if (!is_array($params)) {
            throw new PrintBlankException('Blank`s params should be an array');
        }
        $this->_params = $params;
		$path = $this->path('formPath') . DIRECTORY_SEPARATOR . $this->imageFileName();
		$this->_imageResource = $this->_openBlankImage($path, $this->imageType());
    }

    public function imageFileName()
	{
		return get_class($this) . image_type_to_extension($this->imageType());
	}

	public function csvFileName()
	{
		return get_class($this). '.csv';
	}

    public function blocks()
	{
		return $this->_openCsvDescription($this->path('csvPath') . DIRECTORY_SEPARATOR . $this->csvFileName());
	}

    final public function font($name)
	{
		if (isset($name) && $name !== '') {
			return (isset($this->_params['fonts'][$name])) ? $this->_params['fonts'][$name] : dirname(__FILE__);
		}
		throw new PrintBlankException('Invalid or empty font name');
	}

    final public function path($name)
	{
		if (isset($name) && $name !== '') {
			return (isset($this->_params['paths'][$name])) ? Yii::getPathOfAlias($this->_params['paths'][$name]) : dirname(__FILE__);
		}
		throw new PrintBlankException('Invalid or empty font name');
	}

	final public function imageType()
	{
		return (isset($this->_params['imageType'])) ? $this->_params['imageType'] : IMAGETYPE_PNG;
	}

	final public function compose($content)
	{
		if (is_array($content) && count($content) > 0) {
			$this->_composeContent= $content;
		}
		return $this;
	}

	final private function _composeValue($text)
	{
		$name = str_replace('%', '', $text[0]);
		return isset($this->_composeContent[$name]) ? $this->_composeContent[$name] : "";
	}

	final public function save($filename = NULL, $quality = 0, $filters = 0)
	{
		if (!is_null($this->_imageResource)) {
			$im = $this->_drawBlocks($this->_imageResource);
			return $this->_saveBlankImage($im, $this->imageType(), $filename, $quality, $filters);
		}
		throw new PrintBlankException('Image resourse is empty');
	}

	private function _drawBlocks($imageResource)
	{
		if (isset($imageResource) && !is_null($imageResource)) {
			$blocks = $this->blocks();
			if (is_array($blocks) && count($blocks) > 0) {
				foreach ($blocks as $block) {
                    $font = $this->path('fontPath') . DIRECTORY_SEPARATOR . $this->font($block['font-face']);
					$colorRGB = $this->_hex2rgb($block['font-color']);
					$text = preg_replace_callback('/\%(\w+)\%/i', array($this, '_composeValue'), $block['text']);
					imagettftext($imageResource,
								 $block['font-size'],
								 0,
								 $block['x-point'],
								 $block['y-point'],
								 imagecolorallocate($imageResource, $colorRGB[0], $colorRGB[1], $colorRGB[2]),
								 $font,
								 $this->_textFormat($font, $block['font-size'], $block['width'], $text)
								);
				}
			}
		}
		return $imageResource;
	}

	private function _textFormat($fontFace, $fontSize, $width, $text)
	{
		// Избегаем ошибки,когда возвращается вместто нуля пустота
		if ($text === '0') {
			return $text;
		}
		// Если в строке есть длинные слова, разбиваем их на более короткие
		// Разбиваем текст по строкам
		$strings   = explode("\n",
							 preg_replace('/([^\s]{24})[^\s]/su', '\\1 ',
										  str_replace(array("\r", "\t"), array("\n", ' '), $text)
										 )
							);
		$textOut   = array(0 => '');
		$i = 0;
		foreach ($strings as $str) {
			// Уничтожаем совокупности пробелов, разбиваем по словам
			$words = array_filter(explode(' ', $str));
			foreach ($words as $word)  {
				// Какие параметры у текста в строке?
				$sizes = imagettfbbox($fontSize, 0, $fontFace, $textOut[$i] . $word . ' ');
				// Если размер линии превышает заданный, принудительно
				// перескакиваем на следующую строку
				// Иначе пишем на этой же строке
				($sizes[2] > $width) ? $textOut[++$i] = $word . ' ' : $textOut[$i] .= $word . ' ';
			}
			// "Естественный" переход на новую строку
			$textOut[++$i] = '';
		}
		return implode("\n", $textOut);
	}

	private function _hex2rgb($color)
	{
		if ($color[0] == '#') {
			$color = substr($color, 1);
		}
		if (strlen($color) == 6) {
			list($r, $g, $b) = array($color[0].$color[1],
									 $color[2].$color[3],
									 $color[4].$color[5]);
		} elseif (strlen($color) == 3) {
			list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
		} else {
			return false;
		}
		$r = hexdec($r); $g = hexdec($g); $b = hexdec($b);
		return array($r, $g, $b);
	}

    private function _openBlankImage($imageUrl, $imageType = IMAGETYPE_PNG)
    {
        if (isset($imageUrl) && file_exists($imageUrl)) {
            switch ($imageType) {
                case IMAGETYPE_PNG:
					$im = imagecreatefrompng($imageUrl);
                    break;
                case IMAGETYPE_JPEG:
					$im = imagecreatefromjpeg($imageUrl);
                    break;
                default:
					throw new PrintBlankException('Blank open error: unsupported image format');
                    break;
            }
			return $im;
        }
		return NULL;
    }

	private function _saveBlankImage($imageResource, $imageType = IMAGETYPE_PNG, $filename = NULL, $quality = 0, $filters = 0)
	{
		if (isset($imageResource) && $imageResource !== FALSE) {
			switch ($imageType) {
				case IMAGETYPE_PNG:
                    if (is_null($filename) || $filename === '') {
						return imagepng($imageResource);
					} else {
						return imagepng($imageResource, $filename, $quality, $filters);
					}
					break;
				case IMAGETYPE_JPEG:
					if (is_null($filename) || $filename === '') {
						return imagejpeg($imageResource);
					} else {
						return imagejpeg($imageResource, $filename, $quality, $filters);
					}
					break;
				default:
					throw new PrintBlankException('Blank save error: unsupported image format');
					break;
			}
		}
		return NULL;
	}

    private function _openCsvDescription($filePath)
    {
		if (file_exists($filePath)) {
			$fh = fopen($filePath, 'r');
			$header = fgetcsv($fh, 0, ';', '"');
			$data = array();
			while ($line = fgetcsv($fh, 0, ';', '"')) {
				if ($newLine = array_combine($header, $line)) {
					$data[] = $newLine;
				} else {
					return NULL;
				}
			}
			fclose($fh);
			return $data;
		}
		return NULL;
    }

}

?>
