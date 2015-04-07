<?php
/**
 * Less.php
 *
 * PHP version 5.4+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   3.1.0
 * @link      http://www.sweelix.net
 * @category  behaviors
 * @package   sweelix.yii1.behaviors
 */

namespace sweelix\yii1\behaviors;

use CBehavior;
use CClientScript;
use CFileHelper;
use CException;
use Yii;
use lessc;

/**
 * Class Less
 *
 * This behavior implement less compilation and css/less management f
 *
 * <code>
 *    ...
 *        'clientScript' =>[
 *            'behaviors' => [
 *                'lessClientScript' => [
 *                    'class' => 'sweelix\yii1\behaviors\Less',
 *                    'cacheId' => 'cache', // define cache component to use
 *                    'cacheDuration' => 0, // default value infinite duration
 *                    'forceRefresh' => false, // default value : do not recompile files
 *                    'formatter' => 'lessjs', // default output format
 *                    'variables' => [], // variables to expand
 *                    'directory' => 'application.less', // directory where less files are stored
 *                    'assetsDirectories' => 'img', // directory (relative to less files) to publish
 *                ],
 *            ],
 *        ],
 *    ...
 * </code>
 *
 * With this behavior active, we can now perform :
 * <code>
 *    ...
 *    class MyController extends CController {
 *        ...
 *        public function actionTest() {
 *            ...
 *            Yii::app()->clientScript->registerLessFile('sweelix.less');
 *            // or
 *            Yii::app()->clientScript->registerLess('.block { width : (3px * 2); }');
 *            ...
 *        }
 *        ...
 *    }
 *    ...
 * </code>
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   3.1.0
 * @link      http://www.sweelix.net
 * @category  behaviors
 * @package   sweelix.yii1.behaviors
 * @since     1.11.0
 * @method CClientScript getOwner() Get the request
 */
class Less extends CBehavior
{
    const CACHE_PATH = 'application.runtime.sweelix.less';
    const CACHE_KEY_PREFIX = 'sweelix.behaviors.less.compilation.';
    const PROFILE_KEY_PREFIX = 'sweelix.behaviors.less.';

    /**
     * Attaches the behavior object only if owner is instance of CClientScript
     * or one of its derivative
     * @see CBehavior::attach()
     *
     * @param CClientScript $owner the component that this behavior is to be attached to.
     *
     * @return void
     * @since  1.11.0
     * @throws CException
     */
    public function attach($owner)
    {
        if ($owner instanceof CClientScript) {
            parent::attach($owner);
        } else {
            throw new CException(__CLASS__ . ' can only be attached ot a CClientScript instance');
        }
    }

    /**
     * @var string Allow coder to differentiate modules and avoid collisions
     */
    public $suffix = '';

    private $cacheId;

    /**
     * define the cms cache id
     *
     * @param string $cacheId id of cms cache
     *
     * @return void
     * @since  1.11.0
     */
    public function setCacheId($cacheId)
    {
        $this->cacheId = $cacheId;
    }

    /**
     * get current cache id
     *
     * @return string
     * @since  1.11.0
     */
    public function getCacheId()
    {
        return $this->cacheId;
    }

    /**
     * @var array less snippets registered
     */
    protected $less;

    /**
     * @var array less files registered
     */
    protected $lessFiles;

    private $cache;

    /**
     * Get cache component if everything
     * was set correctly
     *
     * @return \CCache
     * @since  1.11.0
     */
    public function getCache()
    {
        if (($this->cache === null) && ($this->cacheId !== null)) {
            $this->cache = Yii::app()->getComponent($this->cacheId);
        }
        return $this->cache;
    }

    private $assetsUrl;

    /**
     * Published assets url
     *
     * @return string
     * @since  1.11.0
     */
    public function getAssetsUrl()
    {
        if ($this->assetsUrl === null) {
            if (Yii::app()->getAssetManager()->linkAssets === true) {
                $this->assetsUrl = Yii::app()->getAssetManager()->publish($this->getCacheDirectory());
            } else {
                $this->assetsUrl = Yii::app()->getAssetManager()->publish(
                    $this->getCacheDirectory(),
                    false,
                    -1,
                    $this->getForceRefresh()
                );
            }
        }
        return $this->assetsUrl;
    }

    private $preparedLessAssets;

    /**
     * Duplicate less directory structure without the baseless files
     * should be upgrade
     *
     * @return void
     * @since  1.11.0
     */
    protected function prepareLessAssets()
    {
        if (($this->preparedLessAssets === null) && ($this->getAssetsDirectories() !== null)) {
            foreach ($this->getAssetsDirectories() as $directory) {
                $sourceDirectory = $this->getDirectory() . DIRECTORY_SEPARATOR . $directory;
                if (is_dir($sourceDirectory) === true) {
                    $cachedDirectory = $this->getCacheDirectory() . DIRECTORY_SEPARATOR . $directory;
                    CFileHelper::copyDirectory($sourceDirectory, $cachedDirectory);
                }
            }
            $this->preparedLessAssets = true;
        }
    }

    /**
     * Register less file
     *
     * @param string $url URL of the LESS file
     * @param string $media media that the generated CSS file should be applied to. If empty, it means all media types.
     *
     * @return CClientScript
     * @since  1.11.0
     */
    public function registerLessFile($url, $media = '')
    {
        Yii::beginProfile(self::PROFILE_KEY_PREFIX . '.registerLessFile');

        $cssFileName = pathinfo($url, PATHINFO_FILENAME) . '.css';
        $cssFilePath = $this->getCacheDirectory() . DIRECTORY_SEPARATOR . $cssFileName;
        $lessFilePath = $this->getDirectory() . DIRECTORY_SEPARATOR . $url;

        if ($this->isLessFileRegistered($url) === false) {
            if (($this->getForceRefresh() === true)
                || (is_file($cssFilePath) === false)
                || (filemtime($lessFilePath) >= filemtime($cssFilePath))
            ) {
                $this->compileFile($url, $cssFilePath);
                $this->prepareLessAssets();
            }
            $this->lessFiles[$url] = $media;
        }
        // $urlCss = Yii::app()->getAssetManager()->publish($cssFilePath, false, 0, $this->getForceRefresh());
        $urlCss = $this->getAssetsUrl() . '/' . $cssFileName;

        $params = func_get_args();
        $this->recordCachingAction('clientScript', 'registerLessFile', $params);

        Yii::endProfile(self::PROFILE_KEY_PREFIX . '.registerLessFile');
        return $this->getOwner()->registerCssFile($urlCss, $media);
    }

    /**
     * Register less css code
     *
     * @param string $id ID that uniquely identifies this piece of generated CSS code
     * @param string $less the LESS code
     * @param string $media media that the CSS code should be applied to. If empty, it means all media types.
     *
     * @return CClientScript
     * @since  1.11.0
     */
    public function registerLess($id, $less, $media = '')
    {
        Yii::beginProfile(self::PROFILE_KEY_PREFIX . '.registerLess');

        $css = false;
        if (($this->getForceRefresh() === false) && ($this->getCache() !== null)) {
            $cacheKey = self::CACHE_KEY_PREFIX . md5($less);
            $css = $this->getCache()->get($cacheKey);
        }
        if ($css === false) {
            $css = $this->getCompiler()->compile($less);
            if (($this->getForceRefresh() === false) && ($this->getCache() !== null)) {
                $this->getCache()->set($cacheKey, $css, $this->getCacheDuration());
            }
        }
        $this->less[$id] = array($less, $media);

        $params = func_get_args();
        $this->recordCachingAction('clientScript', 'registerLess', $params);

        Yii::endProfile(self::PROFILE_KEY_PREFIX . '.registerLess');
        return $this->getOwner()->registerCss($id . '-less', $css, $media);
    }

    /**
     * Check if snippet is registered
     *
     * @param string $id snippet id
     *
     * @return boolean
     * @since  1.11.0
     */
    public function isLessRegistered($id)
    {
        return isset($this->less[$id]);
    }

    /**
     * Check if file is registered
     *
     * @param string $url file url
     *
     * @return boolean
     * @since  1.11.0
     */
    public function isLessFileRegistered($url)
    {
        return isset($this->lessFiles[$url]);
    }

    private $formatter;

    /**
     * Get current formatter
     *
     * @return string
     * @since  1.11.0
     */
    public function getFormatter()
    {
        return $this->formatter;
    }

    /**
     * Define the formatter to use. Can be
     * compressed, classic or lessjs (default)
     *
     * @param string $formatter
     *
     * @return void
     * @since  1.11.0
     */
    public function setFormatter($formatter)
    {
        if (in_array($formatter, array('lessjs', 'compressed', 'classic')) === true) {
            $this->formatter = $formatter;
            if ($this->compiler !== null) {
                $this->compiler->setFormatter($formatter);
            }
        }
    }

    private $variables;

    /**
     * Get dynamic less variable to use
     *
     * @return array
     * @since  1.11.0
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * Define variables to expand in parsed less files
     *
     * @param array $variables variables to expand
     *
     * @return void
     * @since  1.11.0
     */
    public function setVariables($variables)
    {
        $this->variables = $variables;
        if ($this->compiler !== null) {
            $this->compiler->setVariables($variables);
        }
    }

    private $lessDirectory;

    /**
     * Define the directory where less files are published.
     * The directory must be defined usin a pathalias
     *
     * @param string $directory path alias to the less directory
     *
     * @return void
     * @since  1.11.0
     */
    public function setDirectory($directory)
    {
        $this->lessDirectory = Yii::getPathOfAlias($directory);
        if ($this->compiler !== null) {
            $this->compiler->setImportDir($this->lessDirectory);
        }
    }

    /**
     * Retrieve real less path
     *
     * @return string
     * @since  1.11.0
     */
    public function getDirectory()
    {
        return $this->lessDirectory;
    }

    private $assetsDirectories;

    /**
     * List of directories (relatives to less path) which
     * must be published as companion assets
     *
     * @param array $assetsDirectories list of less companion directories
     *
     * @return void
     * @since  1.11.0
     */
    public function setAssetsDirectories($assetsDirectories)
    {
        $this->assetsDirectories = $assetsDirectories;
    }

    /**
     * List of directories (relatives to less path) which
     * must be published as companion assets
     *
     * @return array
     * @since  1.11.0
     */
    public function getAssetsDirectories()
    {
        return $this->assetsDirectories;
    }

    private $forceRefresh = false;

    /**
     * Define if we want to force compilation / copy on all request
     *
     * @param boolean $forceRefresh
     *
     * @return void
     * @since  1.11.0
     */
    public function setForceRefresh($forceRefresh)
    {
        $this->forceRefresh = $forceRefresh;
    }

    /**
     * Check if we have to force refresh on each request
     *
     * @return boolean
     * @since  1.11.0
     */
    public function getForceRefresh()
    {
        return ($this->forceRefresh === true);
    }

    private $cacheDirectory;

    /**
     * Get cache directory. Default to protected.runtime.less
     * This directory is used to pre-publish css files
     *
     * @return string
     * @since  1.11.0
     */
    public function getCacheDirectory()
    {
        if ($this->cacheDirectory === null) {
            $this->cacheDirectory = Yii::getPathOfAlias(self::CACHE_PATH . $this->suffix);
            if (is_dir($this->cacheDirectory) === false) {
                mkdir($this->cacheDirectory, 0777, true);
            }
        }
        return $this->cacheDirectory;
    }

    /**
     * Wraps the original compile function @see lessc::compile for detailed
     * information
     *
     * @param string $less less code to compile
     *
     * @return string
     * @since  1.11.0
     */
    public function compile($less)
    {
        return $this->getCompiler()->compile($less);
    }

    /**
     * Wraps the original compileFile function @see lessc::compileFile for detailed
     * information
     *
     * @param string $lessFile original less file to compile
     * @param string $cssFile compiled css file
     *
     * @return mixed
     * @since  1.11.0
     */
    public function compileFile($lessFile, $cssFile = null)
    {
        $result = false;
        $lessFile = $this->getDirectory() . DIRECTORY_SEPARATOR . $lessFile;
        if (is_file($lessFile) === true) {
            $result = $this->getCompiler()->compileFile($lessFile, $cssFile);
        }
        return $result;
    }

    private $compiler;

    /**
     * Lazy load the less compiler
     *
     * @return lessc
     * @since  1.11.0
     */
    protected function getCompiler()
    {
        if ($this->compiler === null) {
            $this->compiler = new lessc();
            if ($this->getFormatter() !== null) {
                $this->compiler->setFormatter($this->getFormatter());
            }
            if ($this->getVariables() !== null) {
                $this->compiler->setVariables($this->getVariables());
            }
            if ($this->getDirectory() !== null) {
                $this->compiler->setImportDir($this->getDirectory());
            }
        }
        return $this->compiler;
    }

    private $cacheDuration = 0;

    /**
     * Define cache duration for less code blocks
     *
     * @param integer $cacheDuration @see CCache::get for more information
     *
     * @return void
     * @since  1.11.0
     */
    public function setCacheDuration($cacheDuration)
    {
        $this->cacheDuration;
    }

    /**
     * Get cache duration to use
     *
     * @return integer
     * @since  1.11.0
     */
    public function getCacheDuration()
    {
        return $this->cacheDuration;
    }


    /**
     * Records a method call when an output cache is in effect.
     * This is a shortcut to Yii::app()->controller->recordCachingAction.
     * In case when controller is absent, nothing is recorded.
     * @param string $context a property name of the controller. It refers to an object
     * whose method is being called. If empty it means the controller itself.
     * @param string $method the method name
     * @param array $params parameters passed to the method
     * @see COutputCache
     */
    protected function recordCachingAction($context, $method, $params)
    {
        if (($controller = Yii::app()->getController()) !== null) {
            $controller->recordCachingAction($context, $method, $params);
        }
    }
}
