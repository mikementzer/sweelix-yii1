<?php
/**
 * UploadedFile.php
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

use sweelix\yii1\web\UploadedFile as BaseUploadedFile;
use CBehavior;
use CPropertyValue;
use Yii;

/**
 * This UploadedFile handle automagically the upload process in
 * models
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
class UploadedFile extends CBehavior
{

    /**
     * Attach behavior to specific events
     * (non-PHPdoc)
     * @see CBehavior::events()
     *
     * @return array
     * @since  2.0.0
     */
    public function events()
    {
        return array(
            'onBeforeSave' => 'beforeSave',
            'onAfterDelete' => 'deleteFiles',
            'onAfterFind' => 'setOriginalValues', // populate values from the record
            'onAfterSave' => 'afterSave', // repopulate when everything was saved
        );
    }

    private $shouldSave = false;

    /**
     * Perform save file
     *
     * @return void
     * @since  2.0.0
     */
    public function beforeSave()
    {
        if ($this->getOwnerModel()->isNewRecord === false) {
            $this->saveFiles();
        } else {
            $this->shouldSave = true;
        }
    }

    /**
     * Perform save file (to rename stuff)
     *
     * @return void
     * @since  2.0.0
     */
    public function afterSave()
    {
        if ($this->shouldSave === true) {
            $modelName = '';
            $pk = $this->getOwnerModel()->getPrimaryKey();
            $modelName = get_class($this->getOwnerModel());
            $modelToResave = $modelName::model()->resetScope()->findByPk($pk);
            $modelToResave->save();
            $this->getOwnerModel()->setAttributes($modelToResave->getAttributes(), false);
        }
        $this->setOriginalValues();
    }

    /**
     * @var CModel
     */
    private $model = null;

    /**
     * Define owner of the behavior
     *
     * @param CModel $model owner of the behavior
     *
     * @return void
     * @since  3.0.0
     */
    public function setOwnerModel($model)
    {
        $this->model = $model;
    }

    /**
     * Get owner model
     *
     * @return CModel
     * @since  3.0.0
     */
    public function getOwnerModel()
    {
        if ($this->model === null) {
            $this->model = $this->getOwner();
        }
        return $this->model;
    }

    /**
     * @var array parameters
     */
    private $pathParameters = array();

    /**
     * Get raw parameters
     *
     * @return array
     * @since  2.0.0
     */
    public function getPathParameters()
    {
        return $this->pathParameters;
    }

    /**
     * Prepare raw parameters
     *
     * @param array $pathParameters
     *
     * @return void
     * @since  2.0.0
     */
    public function setPathParameters($pathParameters)
    {
        if (is_array($pathParameters) === true) {
            $this->pathParameters = $pathParameters;
        }
    }

    /**
     * @var array expanded parameter
     */
    private $expandedPathParameters;

    /**
     * convert raw path parameter to usable one (attributes name to real value)
     *
     * @return array
     * @since  2.0.0
     */
    public function getExpandedPathParameters()
    {
        if ($this->expandedPathParameters === null) {
            $this->expandedPathParameters = array();
            foreach ($this->getPathParameters() as $expandKey => $attribute) {
                $this->expandedPathParameters[$expandKey] = $this->getOwnerModel()->$attribute;
            }
        }
        return $this->expandedPathParameters;
    }

    /**
     * @var array
     */
    private $attributesForFile = array();

    /**
     * Define attributes which are handling files
     *
     * attributes should be configured using and array
     * array(
     *        'images' => array(
     *            'asString' => true, // linearize using implode(', ') and preg_split()
     *            'isMulti' => false, // default value
     *            'targetPathAlias' => 'webroot', // default value
     *            'targetUrl' => '', // default value
     *        ),
     * );
     * @param array $attributesConfig
     *
     * @return void
     * @since  2.0.0
     */
    public function setAttributesForFile($attributesConfig)
    {
        if (is_string($attributesConfig) === true) {
            $attributesConfig = array($attributesConfig);
        }
        foreach ($attributesConfig as $key => $value) {
            if (is_string($value) === true) {
                $this->attributesForFile[$value] = array(
                    'asString' => true,
                    'isMulti' => false,
                    'targetPathAlias' => Yii::getPathOfAlias('webroot') . DIRECTORY_SEPARATOR,
                    'targetUrl' => ltrim(rtrim($value['targetUrl'], '/') . '/', '/'),
                );
            } elseif (is_array($value) === true) {
                $this->attributesForFile[$key] = array(
                    'asString' => (isset($value['asString']) === true) ?
                        CPropertyValue::ensureBoolean($value['asString']) :
                        true,
                    'isMulti' => (isset($value['isMulti']) === true) ?
                        CPropertyValue::ensureBoolean($value['isMulti']) :
                        false,
                    'targetPathAlias' => (Yii::getPathOfAlias((isset($value['targetPathAlias']) === true) ?
                            $value['targetPathAlias'] :
                            'webroot')
                        ) . DIRECTORY_SEPARATOR,
                    'targetUrl' => ltrim(((isset($value['targetUrl']) === true) ? $value['targetUrl'] : '') . '/', '/'),
                );
            }
        }
    }

    /**
     * Get attributes configured as file handlers
     *
     * @return array
     * @since  2.0.0
     */
    public function getAttributesForFile()
    {
        return $this->attributesForFile;
    }

    private $originalValues = array();

    /**
     * define original values to perform the difference
     *
     * @since  2.0.0
     * @return void
     */
    public function setOriginalValues()
    {
        foreach ($this->getAttributesForFile() as $attribute => $config) {
            if ($config['asString'] === true) {
                // we have files in string
                $this->originalValues[$attribute] = preg_split(
                    '/[\s,]+/',
                    $this->getOwnerModel()->$attribute,
                    -1,
                    PREG_SPLIT_NO_EMPTY
                );
            } else {
                if (($this->getOwnerModel()->$attribute === null) || empty($this->getOwnerModel()->$attribute)) {
                    $this->originalValues[$attribute] = array();
                } elseif (is_array($this->getOwnerModel()->$attribute) === true) {
                    $this->originalValues[$attribute] = $this->getOwnerModel()->$attribute;
                } else {
                    $this->originalValues[$attribute] = array($this->getOwnerModel()->$attribute);
                }
            }
        }
    }

    /**
     * Get files originally defined
     *
     * @since  2.0.0
     * @return array
     */
    public function getOriginalValues()
    {
        return $this->originalValues;
    }

    /**
     * Save files and populate model attributes
     *
     * @since  2.0.0
     * @return void
     */
    public function saveFiles()
    {
        foreach ($this->getAttributesForFile() as $attribute => $config) {
            $targetPath = $config['targetPathAlias'];
            $targetUrl = $config['targetUrl'];

            // patch with parameters
            if (count($this->getExpandedPathParameters()) > 0) {
                $targetPath = str_replace(
                    array_keys($this->getExpandedPathParameters()),
                    array_values($this->getExpandedPathParameters()),
                    $targetPath
                );
                $targetUrl = str_replace(
                    array_keys($this->getExpandedPathParameters()),
                    array_values($this->getExpandedPathParameters()),
                    $targetUrl
                );
            }

            if (empty($targetPath) === false && is_dir($targetPath) === false) {
                mkdir($targetPath, 0755, true);
            }
            $uploadedFiles = BaseUploadedFile::getInstances($this->getOwnerModel(), $attribute);

            if ($config['asString'] === true) {
                $currentFiles = preg_split('/[\s,]+/', $this->getOwnerModel()->$attribute, -1, PREG_SPLIT_NO_EMPTY);
            } else {
                if ($config['isMulti'] === false) {
                    if ($this->getOwnerModel()->$attribute === null) {
                        $currentFiles = array();
                    } elseif (is_array($this->getOwnerModel()->$attribute) === true) {
                        $currentFiles = $this->getOwnerModel()->$attribute;
                    } else {
                        $currentFiles = array($this->getOwnerModel()->$attribute);
                    }
                } else {
                    $currentFiles = ($this->getOwnerModel()->$attribute !== null) ?
                        $this->getOwnerModel()->$attribute :
                        array();
                }
            }


            $indexFiles = array();


            foreach ($currentFiles as $file) {
                if (empty($file) === false) {
                    if ((strncmp('tmp://', $file, 6) != 0)
                        && (in_array($file, $this->originalValues[$attribute]) === false)
                    ) {
                        $file = $targetUrl . $file;
                    }
                    $indexFiles[$file] = $file;
                }
            }
            $newFiles = array();
            foreach ($uploadedFiles as $file) {
                $fileName = strtolower($file->getName());
                if (strncmp('tmp://', $file, 6) === 0) {
                    $fileName = str_replace('tmp://', '', $fileName);
                    $fileToSave = $targetPath . $fileName;
                    $dbFile = $targetUrl . $fileName;

                    if (file_exists($fileToSave) === true && is_file($fileToSave) === true) {
                        $name = pathinfo($fileName);
                        $fileName = $name['filename'] . '-' . uniqid() . '.' . $name['extension'];
                        $fileToSave = $targetPath . $fileName;
                        $dbFile = $targetUrl . $fileName;
                    }
                    if ($file->saveAs($fileToSave)) {
                        $newFiles[] = $dbFile;
                        if (isset($indexFiles[$file->getName()]) === true) {
                            $indexFiles[$file->getName()] = $dbFile;
                        }
                    }

                }
            }


            $filesToDelete = array_diff(
                (($this->originalValues[$attribute] === null) ? array() : $this->originalValues[$attribute]),
                $indexFiles
            );
            foreach ($filesToDelete as $file) {
                //XXX: This line is used on creation of entity.
                // The files tmp:// is saved in attributes so we do not want to delete those files.
                // See afterSave
                if (strncmp($file, 'tmp://', 6) != 0) {
                    $file = str_replace($targetUrl, $targetPath, $file);
                    if (is_file($file) === true) {
                        unlink($file);
                    }
                }
            }
            if ($config['isMulti'] === false) {
                $finalFiles = array_values($indexFiles);
                $this->getOwnerModel()->$attribute = array_pop($finalFiles);
            } else {
                $this->getOwnerModel()->$attribute = ($config['asString'] === true) ?
                    implode(',', array_values($indexFiles)) :
                    array_values($indexFiles);
            }
        }
    }

    private $resourcesInfo;

    public function getResourcePath($file = null)
    {
        if ($this->resourcesInfo === null) {
            foreach ($this->getAttributesForFile() as $attribute => $config) {
                $targetPath = $config['targetPathAlias'];
                $targetUrl = $config['targetUrl'];
                // patch with parameters
                if (count($this->getExpandedPathParameters()) > 0) {
                    $targetPath = str_replace(
                        array_keys($this->getExpandedPathParameters()),
                        array_values($this->getExpandedPathParameters()),
                        $targetPath
                    );
                    $targetUrl = str_replace(
                        array_keys($this->getExpandedPathParameters()),
                        array_values($this->getExpandedPathParameters()),
                        $targetUrl
                    );
                }
                $this->resourcesInfo[$attribute] = array(
                    'pathAlias' => $targetPath,
                    'url' => $targetUrl,
                );
            }
        }
        if ($file === null) {
            return $this->resourcesInfo;
        } elseif (isset($this->resourcesInfo[$file]) === true) {
            return $this->resourcesInfo[$file];
        }
    }

    /**
     * Delete files when they are not needed anymore
     *
     * @since  2.0.0
     * @return void
     */
    public function deleteFiles()
    {
        foreach ($this->getOriginalValues() as $attribute => $filesToDelete) {
            $config = $this->attributesForFile[$attribute];
            $targetPath = $config['targetPathAlias'];
            $targetUrl = $config['targetUrl'];

            // patch with parameters
            if (count($this->getExpandedPathParameters()) > 0) {
                $targetPath = str_replace(
                    array_keys($this->getPathParameters()),
                    array_values($this->getPathParameters()),
                    $targetPath
                );
                $targetUrl = str_replace(
                    array_keys($this->getPathParameters()),
                    array_values($this->getPathParameters()),
                    $targetUrl
                );
            }
            foreach ($filesToDelete as $file) {
                $file = str_replace($targetUrl, $targetPath, $file);
                if ((strncmp('tmp://', $file, 6) !== 0) && (is_file($file) === true)) {
                    unlink($file);
                }
            }
        }
    }
}
