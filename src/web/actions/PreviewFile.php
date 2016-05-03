<?php
/**
 * PreviewFile.php
 *
 * PHP version 5.4+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   3.1.0
 * @link      http://www.sweelix.net
 * @category  actions
 * @package   sweelix.yii1.web.actions
 */

namespace sweelix\yii1\web\actions;

use sweelix\yii1\web\UploadedFile;
use sweelix\yii1\web\Image;

/**
 * This PreviewFile handle the xhr / swfupload process for preview
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   3.1.0
 * @link      http://www.sweelix.net
 * @category  actions
 * @package   sweelix.yii1.web.actions
 * @since     2.0.0
 */
class PreviewFile extends \CAction
{
    public $width = 100;
    public $height = 100;
    public $fit = true;

    /**
     * Run the action and perform the preview process
     *
     * @return void
     * @since  2.0.0
     */
    public function run($fileName, $mode = null)
    {
        try {
            \Yii::trace(__METHOD__ . '()', 'sweelix.yii1.web.actions');
            if ($mode == 'json') {
                $this->generateJson($fileName);
            } elseif ($mode == 'raw') {
                $this->generateImage($fileName);
            }
        } catch (\Exception $e) {
            \Yii::log('Error in ' . __METHOD__ . '():' . $e->getMessage(), \CLogger::LEVEL_ERROR,
                'sweelix.yii1.web.actions');
            throw $e;
        }
    }

    /**
     * first pass, prepare json file
     *
     * @param string $fileName filename
     *
     * @return void
     * @since  2.0.0
     */
    public function generateJson($fileName)
    {
        try {
            \Yii::trace(__METHOD__ . '()', 'sweelix.yii1.web.actions');
            $tempFile = false;
            $sessionId = \Yii::app()->getRequest()->getParam('key', \Yii::app()->getSession()->getSessionId());
            $id = \Yii::app()->getRequest()->getParam('id', 'unk');

            if (strncmp($fileName, 'tmp://', 6) === 0) {
                $tempFile = true;
                $fileName = str_replace('tmp://', '', $fileName);
                $targetPath = \Yii::getPathOfAlias(UploadedFile::$targetPath) . DIRECTORY_SEPARATOR . $sessionId . DIRECTORY_SEPARATOR . $id;
            } else {
                $targetPath = \Yii::getPathOfAlias(\Yii::app()->getRequest()->getParam('targetPathAlias', 'webroot'));
            }
            if ($tempFile === false) {
                $replacement = array();
                if (preg_match_all('/{([^}]+)}/', \Yii::app()->getRequest()->getParam('targetPathAlias', 'webroot'),
                        $matches) > 0
                ) {
                    if (isset($matches[1]) === true) {
                        foreach ($matches[1] as $repKey) {
                            $replacement['{' . $repKey . '}'] = \Yii::app()->getRequest()->getParam($repKey, '');
                        }
                        $targetPath = str_replace(array_keys($replacement), array_values($replacement), $targetPath);
                    }
                }
            }
            $file = $targetPath . DIRECTORY_SEPARATOR . $fileName;
            $response = array('status' => false);
            if (is_file($file) === true) {
                $width = \Yii::app()->getRequest()->getParam('width', $this->width);
                $height = \Yii::app()->getRequest()->getParam('height', $this->height);
                $fit = \CPropertyValue::ensureBoolean(\Yii::app()->getRequest()->getParam('fit', $this->fit));
                $fit = ($fit === true) ? 'true' : 'false';
                $response['status'] = true;
                $imageInfo = getimagesize($file);
                if (($imageInfo !== false) && (in_array($imageInfo[2],
                            array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG)) === true)
                ) {
                    $response['image'] = true;
                } else {
                    $response['image'] = false;
                }
                if ($tempFile === true) {
                    $relativeFile = 'tmp://' . $fileName;
                    $response['url'] = \CHtml::normalizeUrl(array(
                        $this->id,
                        'mode' => 'raw',
                        'fileName' => $relativeFile,
                        'key' => $sessionId,
                        'id' => $id,
                        'width' => $width,
                        'height' => $height,
                        'fit' => $fit,
                    ));
                    $response['path'] = null;
                } else {
                    $basePath = \Yii::getPathOfAlias('webroot');
                    $baseUrl = \Yii::app()->baseUrl;
                    $relativeFile = $baseUrl.str_replace($basePath, '', $file);
                    $response['url'] = \CHtml::normalizeUrl(array(
                        $this->id,
                        'mode' => 'raw',
                        'fileName' => $relativeFile,
                        'width' => $width,
                        'height' => $height,
                        'fit' => $fit,
                    ));
                    $response['path'] = $relativeFile;
                }
                $response['name'] = $fileName;
            }

            if (\Yii::app()->request->isAjaxRequest == true) {
                $this->getController()->renderJson($response);
            } else {
                echo \CJSON::encode($response);
            }
        } catch (\Exception $e) {
            \Yii::log('Error in ' . __METHOD__ . '():' . $e->getMessage(), \CLogger::LEVEL_ERROR,
                'sweelix.yii1.web.actions');
            throw $e;
        }
    }

    /**
     * second pass, generate file
     *
     * @param string $fileName filename
     *
     * @return void
     * @since  2.0.0
     */
    public function generateImage($fileName)
    {
        try {
            \Yii::trace(__METHOD__ . '()', 'sweelix.yii1.web.actions');
            $tempFile = false;
            $sessionId = \Yii::app()->getRequest()->getParam('key', \Yii::app()->getSession()->getSessionId());
            $id = \Yii::app()->getRequest()->getParam('id', 'unk');
            if (strncmp($fileName, 'tmp://', 6) === 0) {
                $tempFile = true;
                $fileName = str_replace('tmp://', '', $fileName);
                $targetPath = \Yii::getPathOfAlias(UploadedFile::$targetPath) . DIRECTORY_SEPARATOR . $sessionId . DIRECTORY_SEPARATOR . $id;
            } else {
                $targetPath = \Yii::getPathOfAlias(\Yii::app()->getRequest()->getParam('targetPathAlias', 'webroot'));
                $replacement = array();
                $baseUrl = \Yii::app()->baseUrl;
                $fileName = str_replace($baseUrl, '', $fileName);
                if (preg_match_all('/{([^}]+)}/', \Yii::app()->getRequest()->getParam('targetPathAlias', 'webroot'),
                        $matches) > 0
                ) {
                    if (isset($matches[1]) === true) {
                        foreach ($matches[1] as $repKey) {
                            $replacement['{' . $repKey . '}'] = \Yii::app()->getRequest()->getParam($repKey, '');
                        }
                        $targetPath = str_replace(array_keys($replacement), array_values($replacement), $targetPath);
                    }
                }
            }
            $file = $targetPath . DIRECTORY_SEPARATOR . $fileName;
            if (is_file($file) === true) {
                $width = \Yii::app()->getRequest()->getParam('width', $this->width);
                $height = \Yii::app()->getRequest()->getParam('height', $this->height);
                $fit = \CPropertyValue::ensureBoolean(\Yii::app()->getRequest()->getParam('fit', $this->fit));

                $imageInfo = getimagesize($file);
                if (($imageInfo !== false) && (in_array($imageInfo[2],
                            array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG)) === true)
                ) {
                    if ($tempFile === false) {
                        $image = Image::create($file)->resize($width, $height)->setFit($fit);
                        $imageContentType = $image->getContentType();
                        $imageData = file_get_contents($image->getUrl(true));
                    } else {
                        $image = Image::create($file)->resize($width, $height)->setFit($fit);
                        $imageContentType = $image->getContentType();
                        $imageData = $image->liveRender();
                    }
                } else {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $imageName = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'icons' . DIRECTORY_SEPARATOR . $ext . '.png';

                    if (file_exists($imageName) === false) {
                        //Get icon default
                        $imageName = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'icons' . DIRECTORY_SEPARATOR .'default.png';
                    }
                    $image = Image::create($imageName)->resize($width, $height)->setFit($fit);
                    $imageContentType = $image->getContentType();
                    $imageData = file_get_contents($image->getUrl(true));
                }
            }
            header('Content-type: ' . $imageContentType);
            header('Content-Disposition: inline; filename="' . $fileName . '";');
            echo $imageData;
        } catch (\Exception $e) {
            \Yii::log('Error in ' . __METHOD__ . '():' . $e->getMessage(), \CLogger::LEVEL_ERROR,
                'sweelix.yii1.web.actions');
            throw $e;
        }
    }
}
