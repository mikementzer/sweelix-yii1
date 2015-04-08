<?php
/**
 * m121021_122255_createParameters.php
 *
 * PHP version 5.3+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2015 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   3.2.0
 * @link      http://www.sweelix.net
 * @category  migrations
 * @package   sweelix.yii1.migrations
 */

namespace sweelix\yii1\migrations;

/**
 * This class create the Parameter table
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2015 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   3.2.0
 * @link      http://www.sweelix.net
 * @category  migrations
 * @package   sweelix.yii1.migrations
 */
class m121021_122255_createParameters extends \CDbMigration {
	/**
	 * Apply current migration
	 *
	 * @return void
	 */
	public function safeUp() {
		$this->createTable(
			'{{parameters}}',
			array(
				'parameterId' => 'string NOT NULL COMMENT \'parameter key\'',
				'parameterType' => 'string NOT NULL DEFAULT \'string\'',
				'parameterValue' => 'text COMMENT \'parameter value\'',
				'parameterDateCreate' => 'datetime NOT NULL',
				'parameterDateUpdate' => 'datetime',
				'PRIMARY KEY(parameterId)',
			),
			'ENGINE=InnoDB DEFAULT CHARSET=utf8'
		);
	}
	/**
	 * Revert current migration
	 *
	 * @return void
	 */
	public function safeDown() {
		$this->dropTable('{{parameters}}');
	}
}