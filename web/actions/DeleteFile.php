<?php
/**
 * DeleteFile.php
 *
 * PHP version 5.4+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   2.0.0
 * @link      http://www.sweelix.net
 * @category  actions
 * @package   sweelix.yii1.web.actions
 */

namespace sweelix\yii1\web\actions;
use sweelix\yii1\web\UploadedFile;

/**
 * This DeleteFile handle the xhr / swfupload process
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   2.0.0
 * @link      http://www.sweelix.net
 * @category  actions
 * @package   sweelix.yii1.web.actions
 * @since     1.1
 */
class DeleteFile extends \CAction {
	/**
	 * Run the action and perform the delete process
	 *
	 * @return void
	 * @since  1.1.0
	 */
	public function run() {
		try {
			\Yii::trace(__METHOD__.'()', 'sweelix.yii1.web.actions');
			$sessionId = \Yii::app()->getSession()->getSessionId();
			$fileName = \Yii::app()->getRequest()->getParam('name', '');
			if (strncmp($fileName, 'tmp://', 6) === 0) {
				$fileName = str_replace('tmp://', '', $fileName);
			}

			$id = \Yii::app()->getRequest()->getParam('id', 'unk');
			$targetPath = \Yii::getPathOfAlias(UploadedFile::$targetPath).DIRECTORY_SEPARATOR.$sessionId.DIRECTORY_SEPARATOR.$id;
			$response = array('fileName' => $fileName, 'status' => false, 'fileSize' => null);
			if((file_exists($targetPath.DIRECTORY_SEPARATOR.$fileName) == true) && (is_file($targetPath.DIRECTORY_SEPARATOR.$fileName) == true)) {
				unlink($targetPath.DIRECTORY_SEPARATOR.$fileName);
				$response['status'] = true;
			}
			if(\Yii::app()->request->isAjaxRequest == true) {
				$this->getController()->renderJson($response);
			} else {
				echo \CJSON::encode($response);
			}
		}
		catch(\Exception $e) {
			\Yii::log('Error in '.__METHOD__.'():'.$e->getMessage(), \CLogger::LEVEL_ERROR, 'sweelix.yii1.web.actions');
			throw $e;
		}
	}
}