<?php
/**
 * File.php
 *
 * PHP version 5.4+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   3.1.0
 * @link      http://www.sweelix.net
 * @category  validators
 * @package   sweelix.yii1.validators
 */

namespace sweelix\yii1\validators;

use sweelix\yii1\web\UploadedFile;
use CFileValidator;
use Yii;

/**
 * File verifies if an attribute is receiving a valid uploaded file.
 *
 * It uses the model class and attribute name to retrieve the information
 * about the uploaded file. It then checks if a file is uploaded successfully,
 * if the file size is within the limit and if the file type is allowed.
 *
 * This validator will attempt to fetch uploaded data if attribute is not
 * previously set. Please note that this cannot be done if input is tabular:
 * <pre>
 *  foreach($models as $i=>$model)
 *     $model->attribute = UploadedFile::getInstance($model, "[$i]attribute");
 * </pre>
 * Please note that you must use {@link UploadedFile::getInstances} for multiple
 * file uploads.
 *
 * When using validators\File with an active record, the following code is often used:
 * <pre>
 *  if($model->save()) {
 *     // single upload
 *     $model->attribute->saveAs($path);
 *     // multiple upload
 *     foreach($model->attribute as $file)
 *        $file->saveAs($path);
 *  }
 * </pre>
 *
 * You can use {@link sweelix\yii1\validators\File} to validate the file attribute.
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   3.1.0
 * @link      http://www.sweelix.net
 * @category  validators
 * @package   sweelix.yii1.validators
 * @since     1.1
 */
class File extends CFileValidator
{
    /**
     * Set the attribute and then validates using {@link validateFile}.
     * If there is any error, the error message is added to the object.
     *
     * @param CModel $object the object being validated
     * @param string $attribute the attribute being validated
     *
     * @return mixed
     * @since  1.1.0
     */
    protected function validateAttribute($object, $attribute)
    {
        $filesUploaded = [];
        if ($object instanceof \IBehavior) {
            $object = $object->getOwner();
        }
        if ($this->maxFiles > 1) {
            $files = $object->$attribute;
            //Get uploaded files
            if (!is_array($files) || !isset($files[0]) || !$files[0] instanceof UploadedFile) {
                $filesUploaded = UploadedFile::getInstances($object, $attribute);
            }
            //Check if uploaded files are empty and files are empty
            if ((empty($filesUploaded) === true) && (empty($files) === true)) {
                return $this->emptyAttribute($object, $attribute);
            }
            //Check max files
            if (count($files) > $this->maxFiles) {
                $message = $this->tooMany !== null ?
                    $this->tooMany :
                    Yii::t('yii', '{attribute} cannot accept more than {limit} files.');
                $this->addError($object, $attribute, $message, array(
                    '{attribute}' => $attribute,
                    '{limit}' => $this->maxFiles
                ));
            } else {
                //Validate files uploaded
                foreach ($filesUploaded as $file) {
                    $this->validateFile($object, $attribute, $file);
                }
            }
        } else {
            $file = $object->$attribute;
            //Get uploaded file
            if (!$file instanceof UploadedFile) {
                $fileUpload = UploadedFile::getInstance($object, $attribute);
                //Check if is uploaded file and $file is not null
                if (($fileUpload === null) && ($file === null)) {
                    return $this->emptyAttribute($object, $attribute);
                } elseif($fileUpload !== null) {
                    //Validate uploaded file
                    $this->validateFile($object, $attribute, $fileUpload);
                }
            }
        }
    }

    /**
     * Internally validates a file object.
     *
     * @param CModel $object the object being validated
     * @param string $attribute the attribute being validated
     * @param CUploadedFile $file uploaded file passed to check against a set of rules
     *
     * @return mixed
     * @since  1.1.0
     */
    protected function validateFile($object, $attribute, $file)
    {
        if (null === $file) {
            return $this->emptyAttribute($object, $attribute);
        } else {
            if ($this->maxSize !== null && $file->getSize() > $this->maxSize) {
                $message = $this->tooLarge !== null ?
                    $this->tooLarge :
                    Yii::t('yii', 'The file "{file}" is too large. Its size cannot exceed {limit} bytes.');
                $this->addError($object, $attribute, $message, array(
                    '{file}' => $file->getName(),
                    '{limit}' => $this->getSizeLimit()
                ));
            }
        }

        if ($this->minSize !== null && $file->getSize() < $this->minSize) {
            $message = $this->tooSmall !== null ?
                $this->tooSmall :
                Yii::t('yii', 'The file "{file}" is too small. Its size cannot be smaller than {limit} bytes.');
            $this->addError($object, $attribute, $message, array(
                '{file}' => $file->getName(),
                '{limit}' => $this->minSize
            ));
        }

        if ($this->types !== null) {
            if (is_string($this->types)) {
                $types = preg_split('/[\s,]+/', strtolower($this->types), -1, PREG_SPLIT_NO_EMPTY);
            } else {
                $types = $this->types;
            }
            if (!in_array(strtolower($file->getExtensionName()), $types)) {
                $message = $this->wrongType !== null ?
                    $this->wrongType :
                    Yii::t('yii', 'The file "{file}" cannot be uploaded. Only files with these extensions are allowed: {extensions}.');
                $this->addError($object, $attribute, $message, array(
                    '{file}' => $file->getName(),
                    '{extensions}' => implode(', ', $types)
                ));
            }
        }
    }
}