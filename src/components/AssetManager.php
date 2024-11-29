<?php
namespace ssqz\components;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\helpers\FileHelper;

/**
 * Class AssetManager
 * @package common\components
 * @property $cachePublishState
 * @property $cacheObject
 * @property $cachePrefix
 * @property $cacheDuration
 */
class AssetManager extends \yii\web\AssetManager
{
    public $cachePublishState = true;
    public $cacheObject = 'cache';
    public $cachePrefix = 'assetPublish';
    public $cacheDuration = 600;
    public $dirMode = 0777;

    public $appendTimestamp = false;
    /**
     * @var array published assets
     */
    private $_published = [];

    /**
     * Initializes the component.
     * @throws InvalidConfigException if [[basePath]] is invalid
     */
    public function init()
    {
        $this->basePath = Yii::getAlias($this->basePath);
//        $this->basePath = realpath($this->basePath);
        $this->baseUrl = rtrim(Yii::getAlias($this->baseUrl), '/');

        $this->hashCallback = function ($path) {
            return sprintf('%08x', crc32($path));
//            return Yii::$app->id."/".date('Y').'/'.date('md').'/'.sprintf('%08x', crc32($path));
        };
    }

    /**
     * Returns the published path of a file path.
     * This method does not perform any publishing. It merely tells you
     * if the file or directory is published, where it will go.
     * @param string $path directory or file path being published
     * @return string|false string the published file path. False if the file or directory does not exist
     */
    public function getPublishedPath($path)
    {
        $path = Yii::getAlias($path);

        if (isset($this->_published[$path])) {
            return $this->_published[$path][0];
        }
        if (is_string($path) && ($path = realpath($path)) !== false) {
            return $this->basePath . '/' . $this->hash($path) . (is_file($path) ? '/' . basename($path) : '');
        } else {
            return false;
        }
    }

    /**
     * Returns the URL of a published file path.
     * This method does not perform any publishing. It merely tells you
     * if the file path is published, what the URL will be to access it.
     * @param string $path directory or file path being published
     * @return string|false string the published URL for the file or directory. False if the file or directory does not exist.
     */
    public function getPublishedUrl($path)
    {
        $path = Yii::getAlias($path);

        if (isset($this->_published[$path])) {
            return $this->_published[$path][1];
        }
        if (is_string($path) && ($path = realpath($path)) !== false) {
            return $this->baseUrl . '/' . $this->hash($path) . (is_file($path) ? '/' . basename($path) : '');
        } else {
            return false;
        }
    }

    public function getAssetUrl($bundle, $asset, $appendTimestamp = null)
    {
        $appendTimestamp = $this->appendTimestamp;
        if ($bundle->sourcePath) {
            $this->appendTimestamp = false;
        }
        $url = parent::getAssetUrl($bundle, $asset, $appendTimestamp);
        $this->appendTimestamp = $appendTimestamp;
        if ($this->appendTimestamp) {
            if ($this->cachePublishState) {
                $cache = Yii::$app->get($this->cacheObject);
                /* @var $cache \yii\caching\Cache */
                if ($bundle->sourcePath) {
                    if (preg_match('/^https?:\/\//', $asset) || preg_match('/^\/\//', $asset)) {
                        $v = '';
                    } else {
                        $hash = self::assetsHash(FileHelper::normalizePath($bundle->sourcePath));
                        $key = $this->cachePrefix . $hash;
                        $v = $cache->get($key);
                        if ($v === false) {
                            $v = self::assetsModifyTime($bundle->basePath . '/');
                            $cache->set($key, $v, $this->cacheDuration);
                        }
                    }
                } else {
                    // 本台服务器的静态资源的时间
                    $filepath = Yii::getAlias('@webroot') . $url;
                    if (file_exists($filepath)) {
                        $v = filemtime($filepath);
                    } else {
                        $v = '';
                    }
                }
            } else {
                $v = self::assetsModifyTime($bundle->basePath . '/');
            }
            // 多个服务器pull时间不同，会导致v不同，采用自动部署工具会自动修改文件的时间
            // if ($v && !(substr($asset, 0, 2) == '//' || substr($asset, 0, 7) == 'http://' || substr($asset, 0, 8) == 'https://')) {
            if ($v) {
                return $url . '?v=' . $v;
            }
        }
        return $url;
    }

    public static function assetsHash($path)
    {
        return sprintf('%08x', crc32($path));
    }

    /**
     * Publishes a file.
     * @param string $src the asset file to be published
     * @return array the path and the URL that the asset is published as.
     * @throws InvalidParamException if the asset to be published does not exist.
     */
    protected function publishFile($src)
    {
        // TODO not tested
        $dir = $this->hash($src);
        $fileName = basename($src);
//        $dstDir = $this->basePath . '/' . $dir;
        $dstDir = $dir;
        $dstFile = $dstDir . '/' . $fileName;

        $storage = Yii::$app->get('storage');
        $disk = $storage->getDisk('assets');
        /* @var $disk \weyii\filesystem\Filesystem */
        $disk->createDir($dstDir);

        if (!$this->isPublished($dir)) {
            $disk->putFile($dstFile, $src);
            if ($this->cachePublishState) {
                $cache = Yii::$app->get($this->cacheObject);
                $key = $this->cachePrefix . $dir;
                /* @var $cache \yii\caching\Cache */
                $time = self::assetsModifyTime($src);
                $cache->set($key, $time, $this->cacheDuration);
            }

        }

        return [$dstFile, $this->baseUrl . "/$dir/$fileName"];
    }

    protected function isPublished($dstDir)
    {
        $isExist = false;
        if ($this->cachePublishState) {
            $cache = Yii::$app->get($this->cacheObject);
            $key = $this->cachePrefix . $dstDir;
            /* @var $cache \yii\caching\Cache */
            $isExist = $cache->exists($key);
        }

        if (!$isExist) {
            $storage = Yii::$app->get('storage');
            $disk = $storage->getDisk('assets');
            /* @var $disk \weyii\filesystem\Filesystem */
            // 必须使用更底层的getTimestamp()函数，因为他不会把末尾的斜杠去掉
            $isExist = $disk->getAdapter()->has($dstDir . '/');
            if ($isExist) {
                if ($this->cachePublishState) {
                    $cache = Yii::$app->get($this->cacheObject);
                    /* @var $cache \yii\caching\Cache */
                    $key = $this->cachePrefix . $dstDir;
                    $time = self::assetsModifyTime($dstDir . '/');
                    $cache->set($key, $time, $this->cacheDuration);
                }
            }
        }

        return $isExist;
    }

    /**
     * @param $bundle \yii\web\AssetBundle
     * @throws InvalidConfigException
     */
    public static function assetsModifyTime($basePath)
    {
        try {
            $storage = Yii::$app->get('storage');
            $disk = $storage->getDisk('assets');
            /* @var $disk \League\Flysystem\Filesystem */
            // 必须使用更底层的getTimestamp()函数，因为他不会把末尾的斜杠去掉
            if (!$object = $disk->getAdapter()->getTimestamp($basePath)) {
                return false;
            }
            $time = $object['timestamp'];
        } catch (\Exception $e) {
            $time = null;
        }
        return $time;
    }

    /**
     * Publishes a directory.
     * @param string $src the asset directory to be published
     * @param array $options the options to be applied when publishing a directory.
     * The following options are supported:
     *
     * - only: array, list of patterns that the file paths should match if they want to be copied.
     * - except: array, list of patterns that the files or directories should match if they want to be excluded from being copied.
     * - caseSensitive: boolean, whether patterns specified at "only" or "except" should be case sensitive. Defaults to true.
     * - beforeCopy: callback, a PHP callback that is called before copying each sub-directory or file.
     *   This overrides [[beforeCopy]] if set.
     * - afterCopy: callback, a PHP callback that is called after a sub-directory or file is successfully copied.
     *   This overrides [[afterCopy]] if set.
     * - forceCopy: boolean, whether the directory being published should be copied even if
     *   it is found in the target directory. This option is used only when publishing a directory.
     *   This overrides [[forceCopy]] if set.
     *
     * @return array the path directory and the URL that the asset is published as.
     * @throws InvalidParamException if the asset to be published does not exist.
     */
    protected function publishDirectory($src, $options)
    {
        $dir = $this->hash($src);
        $dstDir = $dir;
        if (!empty($options['forceCopy']) || ($this->forceCopy && !isset($options['forceCopy'])) || !$this->isPublished($dir)) {
            Yii::info('Publishing Assets ' . $dir);
            $opts = array_merge(
                $options, [
                    'dirMode' => $this->dirMode,
                    'fileMode' => $this->fileMode,
                ]
            );
            $tmpDir = Yii::getAlias('@runtime/assets-' . $dir);
            Yii::beginProfile('asset-publish:' . $dir, __METHOD__);
            FileHelper::copyDirectory($src, $tmpDir, $opts);
            $this->uploadDirectory($dstDir, $tmpDir);
            Yii::endProfile('asset-publish:' . $dir);
            if ($this->cachePublishState) {
                $cache = Yii::$app->get($this->cacheObject);
                $key = $this->cachePrefix . $dir;
                /* @var $cache \yii\caching\Cache */
                $time = self::assetsModifyTime($dstDir . '/');
                $cache->set($key, $time, $this->cacheDuration);
            }
        }
        return [$dstDir, $this->baseUrl . '/' . $dir];
    }

    public function uploadDirectory($prefix, $localDirectory)
    {
        $storage = Yii::$app->get('storage');
        $disk = $storage->getDisk('assets');
        /* @var $disk \weyii\filesystem\Filesystem */

        $localDirectory = FileHelper::normalizePath($localDirectory, '/');
        $retArray = array("succeededList" => array(), "failedList" => array());

        $directory = $localDirectory;
        //判断是否目录
        if (!is_dir($directory)) {
            throw new \Exception('parameter error: ' . $directory . ' is not a directory, please check it');
        }
        //read directory
        $file_list_array = self::listContents($directory);

        // create dir
        // 为了保证目录的时间是发布时间，需要先删除
        try {
            // 阿里云的adapter有bug，所以加了try
            if($disk->has($prefix)) {
                $disk->deleteDir($prefix);
            }
        } catch (\Exception $e) {
        }

        $disk->createDir($prefix);

        foreach ($file_list_array as $filename) {
            if (is_dir($filename)) {
                $realDir = substr($filename, strlen($localDirectory));
                $realDir = $prefix . $realDir;
                self::uploadDirectory($realDir, $filename);
            } else {
                $realObject = $prefix . substr($filename, strlen($localDirectory));

                try {
                    if (!$disk->putFile($realObject, $filename)) {
                        Yii::error('file upload error');
                    }
                    $retArray["succeededList"][] = $realObject;
                } catch (\Exception $e) {
                    $retArray["failedList"][$realObject] = $e->getMessage();
                }
            }
        }
    }


    public static function listContents($directory = '')
    {
        $result = [];
        $location = $directory;
        if (!is_dir($location)) {
            return [];
        }

        $iterator = new \DirectoryIterator($location);
        foreach ($iterator as $file) {
            $path = $file;
            if (preg_match('#(^|/|\\\\)\.{1,2}$#', $path)) {
                continue;
            }
            $filename = $file->getPathname();
            $filename = FileHelper::normalizePath($filename, '/');
            $result[] = $filename;
        }
        return array_filter($result);
    }

}
