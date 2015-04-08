<?php
/**
 * PackageCommand.php
 *
 * PHP version 5.3+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2015 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   3.2.0
 * @link      http://www.sweelix.net
 * @category  commands
 * @package   sweelix.yii1.commands
 */

namespace sweelix\yii1\commands;

/**
 * This command perform all actions needed to prepare a
 * correct package
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2015 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   3.2.0
 * @link      http://www.sweelix.net
 * @category  commands
 * @package   sweelix.yii1.commands
 *
 * @property array $files files to process
 */
class PackageCommand extends \CConsoleCommand
{

    /**
     * @var array forbidden directories
     */
    public $forbiddenDirectories = array(
        'cache',
        'assets',
        'runtime',
        'vendor',
    );
    public $versionningDirectories = array(
        '.git',
        '.svn',
        '.cvs',
        '.settings', // zend studio
    );

    /**
     * @var string initial path
     */
    public $path;

    /**
     * @var array extensions to re-document
     */
    public $extensions = array('php');
    /**
     * @var array handle files to parse (used for lazy load)
     */
    private $_files;

    /**
     * retrieve all files to parse
     *
     * @return array
     * @since  1.11.0
     */
    public function getFiles()
    {
        if ($this->_files === null) {
            $this->_files = $this->buildList($this->path);
        }
        return $this->_files;
    }

    /**
     * Bump version and re-document files
     *
     * @param string $version version number
     * @param string $since since version number
     * @param string $app appname (for meta generator)
     * @param string $copyright copyright value
     * @param string $license license type and url
     * @param string $link link to project info
     * @param string $php php version
     * @param string $path force path if needed
     * @param string $forbiddenPath path to avoid
     * @param boolean $dryRun fake run
     * @param boolean $skipConfirm skip confirmation message
     *
     * @return integer
     * @since  1.11.0
     */
    public function actionBumpVersion(
        $version = null,
        $since = null,
        $app = null,
        $copyright = null,
        $license = null,
        $link = null,
        $php = null,
        $path = null,
        $forbiddenPath = null,
        $dryRun = false,
        $skipConfirm = false
    ) {
        try {
            \Yii::trace(__METHOD__ . '()', 'sweelix.yii1.commands');
            if ($version == null) {
                return $this->actionIndex();
            }
            if ($path !== null) {
                $this->path = rtrim($path, '/');
            } else {
                $this->path = dirname(\Yii::getPathOfAlias('application'));
            }
            if ($forbiddenPath !== null) {
                $this->forbiddenDirectories = array_unique(
                    array_merge(
                        array('.', '..'),
                        $this->forbiddenDirectories,
                        $this->versionningDirectories,
                        preg_split('/\s*,\s*/', $forbiddenPath)
                    )
                );
            } else {
                $this->forbiddenDirectories = array_unique(
                    array_merge(
                        array('.', '..'),
                        $this->forbiddenDirectories,
                        $this->versionningDirectories
                    )
                );
            }
            if (($dryRun === false) && ($skipConfirm === false)) {
                echo count($this->files) . ' file(s) will be processed' . "\n";
                foreach ($this->files as $file) {
                    echo "\t" . $file . "\n";
                }
                $confirm = $this->confirm("Process selected file(s) ?");
            } else {
                $confirm = true;
            }
            if ($confirm === true) {
                $this->documentFile($version, $since, $app, $copyright, $license, $link, $php, $dryRun);
            }
            return 0;
        } catch (\Exception $e) {
            \Yii::log('Error in ' . __METHOD__ . '():' . $e->getMessage(), \CLogger::LEVEL_ERROR,
                'sweelix.yii1.commands');
            return 1;
        }
    }

    /**
     * Realign parameters
     *
     * @param string $version version number
     * @param string $since since version number
     * @param string $app appname (for meta generator)
     * @param string $copyright copyright value
     * @param string $license license type and url
     * @param string $link link to project info
     * @param string $php php version
     * @param boolean $dryRun fake run
     *
     * @return void
     * @since  1.11.0
     */
    public function documentFile($version, $since, $app, $copyright, $license, $link, $php, $dryRun)
    {
        try {
            \Yii::trace(__METHOD__ . '()', 'sweelix.yii1.commands');
            foreach ($this->files as $file) {
                echo 'File : ' . $file . " /";
                $handle = fopen($file, 'r');
                $buffer = array();
                while (($line = fgets($handle, 4096)) !== false) {
                    if ($version !== null) {
                        $line = preg_replace('/(\*\s+@version\s+)(.*)/', '${1}' . $version, $line);
                    }
                    if ($since !== null) {
                        $line = preg_replace('/(\*\s+@since\s+)(.*)/', '${1}' . $since, $line);
                    }
                    if ($app !== null) {
                        $line = preg_replace('/<meta name="generator" content="[^"]+" \/>/',
                            '<meta' . ' ' . 'name="generator" content="' . $app . ' ' . $version . '" />', $line);
                    }
                    if ($copyright !== null) {
                        $line = preg_replace('/(\*\s+@copyright\s+)(.*)/', '${1}' . $copyright, $line);
                    }
                    if ($license !== null) {
                        $line = preg_replace('/(\*\s+@license\s+)(.*)/', '${1}' . $license, $line);
                    }
                    if ($link !== null) {
                        $line = preg_replace('/(\*\s+@link\s+)(.*)/', '${1}' . $link, $line);
                    }
                    if ($php !== null) {
                        $line = preg_replace('/(\*\s+PHP version\s+)(.*)/', '${1}' . $php, $line);
                    }
                    $buffer[] = $line;
                }
                fclose($handle);
                if ($dryRun === false) {
                    $handle = fopen($file, 'w');
                    fwrite($handle, implode("", $buffer));
                    fclose($handle);
                }
                echo " done\n";
            }
        } catch (\Exception $e) {
            \Yii::log('Error in ' . __METHOD__ . '():' . $e->getMessage(), \CLogger::LEVEL_ERROR,
                'sweelix.yii1.commands');
            throw $e;
        }
    }

    /**
     * Parse files
     *
     * @param string $path force path if needed
     * @param string $forbiddenPath path to avoid
     * @param boolean $dryRun fake run
     * @param boolean $skipConfirm skip confirmation message
     *
     * @return void
     * @since  1.11.0
     */
    public function actionTrim($path = null, $forbiddenPath = null, $dryRun = false, $skipConfirm = false)
    {
        try {
            \Yii::trace(__METHOD__ . '()', 'sweelix.yii1.commands');
            if ($path !== null) {
                $this->path = rtrim($path, '/');
            } else {
                $this->path = dirname(\Yii::getPathOfAlias('application'));
            }
            if ($forbiddenPath !== null) {
                $this->forbiddenDirectories = array_unique(
                    array_merge(
                        array('.', '..'),
                        $this->forbiddenDirectories,
                        $this->versionningDirectories,
                        preg_split('/\s*,\s*/', $forbiddenPath)
                    )
                );
            } else {
                $this->forbiddenDirectories = array_unique(
                    array_merge(
                        array('.', '..'),
                        $this->forbiddenDirectories,
                        $this->versionningDirectories
                    )
                );
            }
            if (($dryRun === false) && ($skipConfirm === false)) {
                echo count($this->files) . ' file(s) will be processed' . "\n";
                foreach ($this->files as $file) {
                    echo "\t" . $file . "\n";
                }
                $confirm = $this->confirm("Process selected file(s) ?");
            } else {
                $confirm = true;
            }
            if ($confirm === true) {
                $this->cleanUpFiles($dryRun);
            }
            return 0;
        } catch (\Exception $e) {
            \Yii::log('Error in ' . __METHOD__ . '():' . $e->getMessage(), \CLogger::LEVEL_ERROR,
                'sweelix.yii1.commands');
            return 1;
        }
    }

    /**
     * Cleanup files : trim right + remove BOM
     *
     * @param boolean $dryRun fake run
     *
     * @return void
     * @since  1.11.0
     */
    public function cleanUpFiles($dryRun)
    {
        try {
            \Yii::trace(__METHOD__ . '()', 'sweelix.yii1.commands');
            foreach ($this->files as $file) {
                echo 'File : ' . $file . " /";
                $handle = fopen($file, 'r');
                $buffer = array();
                while (($line = fgets($handle, 4096)) !== false) {
                    $buffer[] = rtrim($line);
                }
                fclose($handle);
                // remove BOM
                if (isset($buffer[0]) === true) {
                    if (substr($buffer[0], 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf)) {
                        $buffer[0] = substr($buffer[0], 3);
                    }
                }
                if ($dryRun === false) {
                    $handle = fopen($file, 'w');
                    fwrite($handle, implode("\n", $buffer));
                    fclose($handle);
                }
                echo " done\n";
            }
        } catch (\Exception $e) {
            \Yii::log('Error in ' . __METHOD__ . '():' . $e->getMessage(), \CLogger::LEVEL_ERROR,
                'sweelix.yii1.commands');
            throw $e;
        }
    }

    /**
     * Create zip package
     *
     * @param string $version version number
     * @param string $app appname (for meta generator)
     * @param string $path force path if needed
     * @param string $emptyPath path to add to zip without adding files
     * @param string $forbiddenFiles files to exclude
     * @param boolean $dryRun fake run
     * @param boolean $skipConfirm skip confirmation message
     *
     * @return integer
     * @since  1.11.0
     */
    public function actionZip(
        $version = null,
        $app = null,
        $path = null,
        $emptyPath = null,
        $forbiddenFiles = null,
        $dryRun = false,
        $skipConfirm = false
    ) {
        try {
            \Yii::trace(__METHOD__ . '()', 'sweelix.yii1.commands');
            if (($version === null) || ($app === null)) {
                return $this->actionIndex();
            }
            if ($path !== null) {
                $this->path = rtrim($path, '/');
            } else {
                $this->path = dirname(\Yii::getPathOfAlias('application'));
            }
            $this->forbiddenDirectories = array_unique(
                array_merge(
                    array('.', '..'),
                    $this->versionningDirectories
                )
            );
            $this->extensions = null;
            if ($emptyPath === null) {
                $emptyPath = array();
            } else {
                $emptyPath = preg_split('/\s*,\s*/', $emptyPath);
            }
            $_forbiddenFiles = array('.gitignore', '.buildpath', '.project');
            if ($forbiddenFiles !== null) {
                $forbiddenFiles = array_unique(array_merge($_forbiddenFiles, preg_split('/\s*,\s*/', $forbiddenFiles)));
            } else {
                $forbiddenFiles = $_forbiddenFiles;
            }
            $packageFiles = array();
            $packageDirectories = array();
            foreach ($this->files as $file) {
                $allowed = true;
                foreach ($emptyPath as $subPath) {
                    if (($res = strpos($file, $subPath)) !== false) {
                        $allowed = false;
                        $packageDirectories[] = substr(pathinfo($file, PATHINFO_DIRNAME), 0, $res + strlen($subPath));
                        break;
                    }
                }
                if ($allowed === true) {
                    $packageFiles[] = $file;
                }
            }
            $packageDirectories = array_unique($packageDirectories);
            $_packageFiles = array_unique($packageFiles);
            $packageFiles = array();
            foreach ($_packageFiles as $file) {
                if (in_array(pathinfo($file, PATHINFO_BASENAME), $forbiddenFiles) === false) {
                    $packageFiles[] = $file;
                }
            }
            $finalDirectories = array();
            foreach ($packageDirectories as $directory) {
                $finalDirectories[$directory] = ltrim(str_replace($this->path, '', $directory), '/');
            }
            $finalFiles = array();
            foreach ($packageFiles as $file) {
                $finalFiles[$file] = ltrim(str_replace($this->path, '', $file), '/');
            }

            $archiveName = $app . '-' . $version . '.zip';
            $archiveName = preg_replace('/[^a-z0-9-_\.]/i', '_', $archiveName);
            $archiveName = preg_replace('/[_]+/', '_', $archiveName);
            if (($dryRun === false) && ($skipConfirm === false)) {
                $confirm = $this->confirm("Prepare package ?");
            } else {
                $confirm = true;
            }
            if ($confirm === true) {
                $zip = new \ZipArchive();
                if ($zip->open($this->path . DIRECTORY_SEPARATOR . $archiveName, \ZipArchive::OVERWRITE) === true) {
                    $zip->setArchiveComment('Package ' . $archiveName . ' created on ' . \Yii::app()->getDateFormatter()->formatDateTime(time()));
                    foreach ($finalFiles as $original => $target) {
                        $zip->addFile($original, $target);
                    }
                    foreach ($finalDirectories as $target) {
                        $zip->addEmptyDir($target);
                    }
                    $zip->close();
                }
            }
            return 0;
        } catch (\Exception $e) {
            \Yii::log('Error in ' . __METHOD__ . '():' . $e->getMessage(), \CLogger::LEVEL_ERROR,
                'sweelix.yii1.commands');
            return 1;
        }
    }

    /**
     * display help
     *
     * @return void
     * @since  1.11.0
     */
    public function actionIndex()
    {
        try {
            \Yii::trace(__METHOD__ . '()', 'sweelix.yii1.commands');
            echo "./yiic package bumpVersion \n";
            echo "\t--version : version number ( --version=1.2.1 )\n";
            echo "\t[--since] : set since version number ( --since=1.2.0 )\n";
            echo "\t[--app] : set app name ( --app=Ibitux )\n";
            echo "\t[--copyright] : copyright ( --copyright='2010-2014 Sweelix' )\n";
            echo "\t[--license] : license ( --license='http://www.sweelix.net/license license' )\n";
            echo "\t[--link] : site link ( --link='http://www.sweelix.net/' )\n";
            echo "\t[--php] : php version ( --php='5.4+' )\n";
            echo "\t[--forbiddenPath] : path to not touch ( --forbiddenPath='runtime,cache' )\n";
            echo "\t[--path] : chemin de traitement ( --path='../' )\n";
            echo "\t[--dryRun] : do not perform replace\n";
            echo "\t[--skipConfirm] : skip confirm message\n";
            echo "\n";
            echo "./yiic package trim \n";
            echo "\t[--forbiddenPath] : path to not touch ( --forbiddenPath='runtime,cache' )\n";
            echo "\t[--dryRun] : do not perform replace\n";
            echo "\t[--skipConfirm] : skip confirm message\n";
            echo "\n";
            echo "./yiic package zip \n";
            echo "\t--version : version number ( --version=1.2.1 )\n";
            echo "\t--app : set app name ( --app=Sweelix )\n";
            echo "\t[--forbiddenFiles] : files to exclude ( --forbiddenFiles='remove.txt,test.txt' )\n";
            echo "\t[--path] : chemin de traitement ( --path='../' )\n";
            echo "\t[--emptyPath] : directory to add without adding files ( --emptyPath='assets,cache' )\n";
            echo "\t[--dryRun] : do not perform replace\n";
            echo "\t[--skipConfirm] : skip confirm message\n";
            return 0;
        } catch (\Exception $e) {
            \Yii::log('Error in ' . __METHOD__ . '():' . $e->getMessage(), \CLogger::LEVEL_ERROR,
                'sweelix.yii1.commands');
            return 1;
        }
    }

    /**
     * Prepare the directory list. Should be upgraded to handle
     * full directory path
     *
     * @param string $initialDirectory initial directory
     *
     * @return array
     * @since  1.11.0
     */
    protected function buildList($initialDirectory)
    {
        try {
            \Yii::trace(__METHOD__ . '()', 'sweelix.yii1.commands');
            $dirList = scandir($initialDirectory);
            $fileList = array();
            foreach ($dirList as $element) {
                if (in_array($element, $this->forbiddenDirectories) === false) {
                    if (is_dir($initialDirectory . DIRECTORY_SEPARATOR . $element) === true) {
                        $fileList = array_merge($fileList,
                            $this->buildList($initialDirectory . DIRECTORY_SEPARATOR . $element));
                    } elseif (is_file($initialDirectory . DIRECTORY_SEPARATOR . $element) === true) {
                        if (($this->extensions === null) || (in_array(pathinfo($element, PATHINFO_EXTENSION),
                                    $this->extensions) === true)
                        ) {
                            $fileList[] = $initialDirectory . DIRECTORY_SEPARATOR . $element;
                        }
                    }
                }
            }
            return $fileList;
        } catch (\Exception $e) {
            \Yii::log('Error in ' . __METHOD__ . '():' . $e->getMessage(), \CLogger::LEVEL_ERROR,
                'sweelix.yii1.commands');
            throw $e;
        }
    }
}
