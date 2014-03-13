<?php
/**
 * Routing class file.
 *
 * PHP 5.4+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   3.0.1
 * @link      http://www.sweelix.net
 * @category  behaviors
 * @package   sweelix.yii1.behaviors
 */

namespace sweelix\yii1\behaviors;

/**
 * This class allow submodules to attach their own routes
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   3.0.1
 * @link      http://www.sweelix.net
 * @category  behaviors
 * @package   sweelix.yii1.behaviors
 */
class Routing extends \CBehavior {
	/**
	 * @var array list of modules
	 */
	public $modules = [];

	/**
	 * Event where we want to attach ourselves
	 *
	 * (non-PHPdoc)
	 * @see CBehavior::events()
	 *
	 * @return array
	 * @since  1.9.0
	 */
	public function events() {
		return [
			'onBeginRequest' => 'beginRequest',
		];
	}

	/**
	 * Run the specific methods (ask the modules to build their routes
	 *
	 * @return void
	 * @since  1.9.0
	 */
	public function beginRequest() {
		if(is_array($this->modules) === false) {
			$this->modules = [$this->modules];
		}
		foreach($this->modules as $module) {
			if(is_callable([\Yii::app()->getModule($module), 'buildRoute']) === true) {
				\Yii::app()->getModule($module)->buildRoute();
			}
		}
	}
}