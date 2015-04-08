<?php
/**
 * File ImageConfig.php
 *
 * PHP version 5.3+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2015 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   3.2.0
 * @link      http://www.sweelix.net
 * @category  components
 * @package   sweelix.yii1.components
 */

namespace sweelix\yii1\components;

use sweelix\yii1\web\Image;
use CException;
use Yii;

/**
 * Class ImageConfig
 *
 * This module allow automatic configuration for class Image.
 * Once module is configured , Image inherit of basic properties
 * such as
 *
 *  - cachePath
 *  - cachingMode
 *  - urlSeparator
 *  - quality
 *
 * id of the module should be set to "image". If not, we will attempt to find
 * correct module.
 *
 * <code>
 *    'components' => [
 *        ...
 *        'image' => [
 *            'class'=>'sweelix\yii1\components\ImageConfig',
 *            'quality'=>80,
 *            'cachingMode'=>'performance',
 *            'urlSeparator'=>'/',
 *            'cachePath'=>'cache',
 *            'errorImage'=>'error.jpg',
 *        ],
 *        ...
 * </code>
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2015 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   3.2.0
 * @link      http://www.sweelix.net
 * @category  components
 * @package   sweelix.yii1.components
 * @since     1.11.0
 */
class ImageConfig extends \CApplicationComponent
{
    /**
     * @var boolean define status of the module
     */
    private $initialized = false;
    /**
     * @var integer define caching mode @see Image for further details
     */
    private $cachingMode = null;

    /**
     * Caching mode setter @see Image::cachingMode for further details
     *
     * @param integer $mode can be performance, normal or debug
     *
     * @return void
     * @since  1.0.0
     */
    public function setCachingMode($mode)
    {
        if ($this->initialized === true) {
            throw new CException(Yii::t('sweelix', 'ImageConfig, cachingMode can be defined only in configuration'));
        }
        $this->cachingMode = $mode;
    }

    /**
     * Caching mode getter @see Image for further details
     *
     * @return integer
     * @since  1.0.0
     */
    public function getCachingMode()
    {
        return $this->cachingMode;
    }

    /**
     * @var string this separator is used to build Urls
     */
    private $urlSeparator = '/';

    /**
     * Url separator setter @see Image::urlSeparator for further details
     *
     * @param string $urlSeparator separator used to build Urls
     *
     * @return void
     * @since  1.0.0
     */
    public function setUrlSeparator($urlSeparator)
    {
        if ($this->initialized === true) {
            throw new CException(Yii::t('sweelix', 'ImageConfig, urlSeparator can be defined only in configuration'));
        }
        $this->urlSeparator = $urlSeparator;
    }

    /**
     * Url separator getter @see Image::urlSeparator for further details
     *
     * @return string
     * @since  1.0.0
     */
    public function getUrlSeparator()
    {
        return $this->urlSeparator;
    }

    /**
     * @var string define default cache path
     */
    private $cachePath = 'cache';

    /**
     * Cache path setter @see Image::cachePath for further details
     *
     * @param string $cachePath real path (not namespace path)
     *
     * @return void
     * @since  1.0.0
     */
    public function setCachePath($cachePath)
    {
        if ($this->initialized === true) {
            throw new CException(Yii::t('sweelix', 'ImageConfig, cachePath can be defined only in configuration'));
        }
        $this->cachePath = $cachePath;
    }

    /**
     * Cache path getter @see Image::cachePath for further details
     *
     * @return string
     * @since  1.0.0
     */
    public function getCachePath()
    {
        return $this->cachePath;
    }

    /**
     * @var string this image is used when original image cannot be found
     */
    private $errorImage = 'error.jpg';

    /**
     * Error image setter @see Image::errorImage for further details
     *
     * @param string $errorImage error image name
     *
     * @return void
     * @since  1.2.0
     */
    public function setErrorImage($errorImage)
    {
        if ($this->initialized === true) {
            throw new CException(Yii::t('sweelix', 'ImageConfig, errorImage can be defined only in configuration'));
        }
        $this->errorImage = $errorImage;
    }

    /**
     *  Error image getter @see Image::errorImage for further details
     *
     * @return string
     * @since  1.2.0
     */
    public function getErrorImage()
    {
        return $this->errorImage;
    }

    /**
     * @var integer define the quality used for the rendering
     */
    private $quality = 90;

    /**
     * Quality setter @see Image::setQuality() for further details
     *
     * @param integer $quality image quality default to 90
     *
     * @return void
     * @since  1.0.0
     */
    public function setQuality($quality)
    {
        if ($this->initialized === true) {
            throw new CException(Yii::t('sweelix', 'ImageConfig, quality can be defined only in configuration'));
        }
        $this->quality = $quality;
    }

    /**
     * Cache path getter @see Image::cachePath for further details
     *
     * @return integer
     * @since  1.0.0
     */
    public function getQuality()
    {
        return $this->quality;
    }

    /**
     * Init module with parameters @see CApplicationComponent::init()
     *
     * @return void
     * @since  1.0.0
     */
    public function init()
    {
        $this->attachBehaviors($this->behaviors);
        if ((is_writable($this->cachePath) === false) || (is_dir($this->cachePath) === false)) {
            throw new CException(Yii::t('sweelix', 'ImageConfig, cachePath is invalid'));
        }
        $this->setCachingMode(Image::MODE_NORMAL);
        $this->initialized = true;
    }
}
