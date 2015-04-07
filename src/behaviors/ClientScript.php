<?php
/**
 * File ClientScript.php
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
 * @since     1.1
 */

namespace sweelix\yii1\behaviors;

use CBehavior;
use CClientScript;
use CException;
use Yii;

/**
 * Class ClientScript
 *
 * This behavior implement script management for
 * element used in @see Html
 *
 * <code>
 *    ...
 *        'clientScript' => [
 *            'behaviors' => [
 *                'sweelixClientScript' => [
 *                    'class' => 'sweelix\yii1\behaviors\ClientScript',
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
 *            Yii::app()->clientScript->registerSweelixScript('sweelix');
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
 * @since     1.1
 * @method CClientScript getOwner() Get the request
 */
class ClientScript extends CBehavior
{
    public $sweelixScript = array();
    public $sweelixPackages = null;
    private $assetUrl;
    private $config;
    private $shadowboxConfig;
    private $init = false;
    private $sbInit = false;

    /**
     * Attaches the behavior object only if owner is instance of CClientScript
     * or one of its derivative
     * @see CBehavior::attach()
     *
     * @param CClientScript $owner the component that this behavior is to be attached to.
     *
     * @return void
     * @since  1.1.0
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
     * Publish assets to allow script and css appending
     *
     * @return string
     * @since  1.1.0
     */
    public function getSweelixAssetUrl()
    {
        if ($this->assetUrl === null) {
            $this->assetUrl = Yii::app()->getAssetManager()->publish(
                dirname(__DIR__) . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'source'
            );
        }
        return $this->assetUrl;
    }

    /**
     * Register sweelix script
     *
     * @param string $name name of the package we want to register
     * @param boolean $importCss do not load packaged css
     *
     * @return CClientScript
     * @since  1.1.0
     */
    public function registerSweelixScript($name, $importCss = true)
    {
        if (isset($this->sweelixScript[$name])) {
            return $this->getOwner();
        }
        if ($this->sweelixPackages === null) {
            $this->sweelixPackages = require(
                dirname(__DIR__) . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'packages.php'
            );
        }
        if (isset($this->sweelixPackages[$name])) {
            $package = $this->sweelixPackages[$name];
        }
        if (isset($package)) {
            if (!empty($package['depends'])) {
                foreach ($package['depends'] as $p) {
                    if (array_key_exists($p, $this->sweelixPackages) == true) {
                        $this->registerSweelixScript($p);
                    } else {
                        $this->getOwner()->registerCoreScript($p);
                    }
                }
            }
            if (isset($package['js']) == true) {
                foreach ($package['js'] as $js) {
                    $this->getOwner()->registerScriptFile($this->getSweelixAssetUrl() . '/' . $js);
                }
            }
            if (($importCss === true) && (isset($package['css']) == true)) {
                foreach ($package['css'] as $css) {
                    $this->getOwner()->registerCssFile($this->getSweelixAssetUrl() . '/' . $css);
                }
            }
            if ($name === 'shadowbox') {
                $this->initShadowbox();
            }
            $this->sweelixScript[$name] = $package;
            if ($this->init === false) {
                if (isset($this->config['debug']) === true) {
                    if (isset($this->config['debug']['mode']) === true) {
                        if (is_string($this->config['debug']['mode']) === true) {
                            $this->config['debug']['mode'] = array($this->config['debug']['mode']);
                        }
                        $appenders = array();
                        foreach ($this->config['debug']['mode'] as $debugMode => $parameters) {
                            if ((is_integer($debugMode) === true) && (is_string($parameters) === true)) {
                                $debugMode = $parameters;
                                $parameters = null;
                            }
                            $debugMode = strtolower($debugMode);
                            if ($parameters !== null) {
                                $parameters = \CJavaScript::encode($parameters);
                            } else {
                                $parameters = '';
                            }

                            switch ($debugMode) {
                                case 'popup':
                                    $appenders[] = 'js:new log4javascript.PopUpAppender(' . $parameters . ')';
                                    break;
                                case 'browser':
                                    $appenders[] = 'js:new log4javascript.BrowserConsoleAppender(' . $parameters . ')';
                                    break;
                                case 'inpage':
                                    $appenders[] = 'js:new log4javascript.InPageAppender(' . $parameters . ')';
                                    break;
                                case 'alert':
                                    $appenders[] = 'js:new log4javascript.AlertAppender(' . $parameters . ')';
                                    break;
                            }
                        }
                        unset($this->config['debug']['mode']);
                        if (count($appenders) > 0) {
                            $this->config['debug']['appenders'] = 'js:' . \CJavaScript::encode($appenders);
                        }

                    }
                }
                $this->getOwner()->registerScript(
                    'sweelixInit',
                    'sweelix.configure(' . \CJavaScript::encode($this->config) . ');',
                    CClientScript::POS_HEAD
                );
                $this->init = true;
            }
        }
        return $this->getOwner();
    }

    /**
     * Register shadowbox script and init it in the
     * page
     *
     * @return void
     * @since  1.1.0
     */
    private function initShadowbox()
    {
        if ($this->sbInit === false) {
            $this->getOwner()->registerScript(
                'shadowboxInit',
                'Shadowbox.init(' . \CJavaScript::encode($this->shadowboxConfig) . ');',
                CClientScript::POS_READY
            );
            $this->sbInit = true;
        }
    }

    /**
     * Define configuration parameters for
     * javascript packages
     *
     * @param array $data initial config
     *
     * @return void
     * @since  1.1.0
     */
    public function setConfig($data = array())
    {
        if (isset($data['shadowbox']) == true) {
            $this->shadowboxConfig = $data['shadowbox'];
            unset($data['shadowbox']);
        }
        if (!isset($this->shadowboxConfig['skipSetup'])) {
            $this->shadowboxConfig['skipSetup'] = true;
        }
        $this->config = $data;
    }
}
