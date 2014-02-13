<?php
/**
 * File Image.php
 *
 * PHP version 5.4+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   2.0.0
 * @link      http://www.sweelix.net
 * @category  web
 * @package   sweelix.yii1.web
 */

namespace sweelix\yii1\web;
use sweelix\image\Image as BaseImage;

/**
 * Class SwCacheImage wraps @see SwImage and
 * Yii into one class to inherit Yii config
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   2.0.0
 * @link      http://www.sweelix.net
 * @category  web
 * @package   sweelix.yii1.web
 * @since     1.11.0
 */
class Image extends BaseImage {
	/**
	 * Constructor, create an image object. This object will
	 * allow basic image manipulation
	 *
	 * @param string  $fileImage  image name with path
	 * @param integer $quality    quality, default is @see SwImage::_quality
	 * @param integer $ratio      ratio, default is @see SwImage::_ratio
	 * @param string  $base64Data image data base64 encoded
	 *
	 * @return Image
	 */
	public function __construct($fileImage, $quality=null, $ratio=null, $base64Data=null) {
		$module = \Yii::app()->getComponent('image');
		if($module===null) {
			\Yii::log(\Yii::t('sweelix', '{object} has not been defined', array('{object}'=>'ImageConfig')), \CLogger::LEVEL_ERROR, 'sweelix.yii1.web');
			throw new \CException(\Yii::t('sweelix', 'ImageConfig, component has not been defined'));
		}
		$this->cachePath = $module->getCachePath();
		$this->cachingMode = $module->getCachingMode();
		$this->setQuality($module->getQuality());
		self::$urlSeparator = $module->getUrlSeparator();
		self::$errorImage = $module->getErrorImage();
		parent::__construct($fileImage, $quality, $ratio, $base64Data);
	}

	/**
	 * Create an instance of Image with correct parameters
	 * calls original constructor @see Image::__construct()
	 *
	 * @param string  $fileImage  image name with path
	 * @param integer $quality    quality, default is @see BaseImage::_quality
	 * @param integer $ratio      ratio, default is @see BaseImage::_ratio
	 * @param string  $base64Data image data base64 encoded
	 *
	 * @return SwCacheImage
	 */
	public static function create($fileImage, $quality=null, $ratio=null, $base64Data=null) {
		\Yii::trace(\Yii::t('sweelix', '{class} get instance of image', array('{class}'=>__CLASS__)), 'sweelix.yii1.web');
		return new static($fileImage, $quality, $ratio, $base64Data);
	}
}