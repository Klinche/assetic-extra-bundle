<?php

namespace Alex\AsseticExtraBundle\Assetic\Util;

use Assetic\Cache\CacheInterface;

/**
 * Class to interact with a folder containing images.
 *
 * @author Alexandre Salomé <alexandre.salome@gmail.com>
 */
class AssetDirectory
{
    /**
     * @var string
     */
    protected $directory;

    /**
     * @var string
     */
    protected $target;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Constructs a new asset directory
     *
     * @param string         $directory path to the directory of assets
     * @param CacheInterface $cache     a cache to use, to avoid copying the same file
     */
    public function __construct($directory, $target = null, CacheInterface $cache = null)
    {
        $this->directory = $directory;
        $this->target    = $target;
        $this->cache     = $cache;
    }

    /**
     * Copy a file to the directory.
     *
     * @param string  $file  fullpath of file to add
     * @param boolean $force ignore cache and add file to directory
     *
     * @throws InvalidArgumentException file does not exist
     * @throws RuntimeException filesystem errors
     *
     * @return string target image path
     */
    public function add($file, $force = false)
    {
        if (!file_exists($file)) {
            throw new \InvalidArgumentException(sprintf('File "%s" does not exist.', $file));
        }

        if (false === $force && null !== $path = $this->getCache($file)) {
            return null === $this->target ? $path : $this->target.'/'.$path;
        }

        $name = $this->findAvailableName($file);

        if (null !== $this->cache) {
            $this->cache->set(md5($file), serialize(array(filemtime($file), $name)));
        }

        if (!is_dir($dir = dirname($target = $this->directory.'/'.$name))) {
            mkdir($dir, 0777, true);
        }

        if (false === @copy($file, $target)) {
            throw new \RuntimeException(sprintf('Error while copying "%s" to "%s".', $file, $target));
        }

        return null === $this->target ? $name : $this->target.'/'.$name;
    }

    /**
     * Returns directory.
     *
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Returns target.
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Changes target value.
     *
     * @param string $target a target path
     *
     * @return AssetDirectory fluid interface
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Searches a fresh cached file for the given file.
     *
     * @throws RuntimeException filesystem errors
     */
    private function getCache($file)
    {
        if (null === $this->cache) {
            return null;
        }

        $key = md5($file);

        // File already present
        if ($this->cache->has($key)) {
            list($mtime, $path) = unserialize($this->cache->get($key));

            if ($mtime === filemtime($file)) {
                return $path;
            }

            $delete = $this->directory.'/'.$path;
            if (file_exists($delete) && false === @unlink($delete)) {
                throw new \RuntimeException('Unable to remove file '.$delete);
            }
        }

        return null;
    }

    /**
     * Finds a name not used, incrementing if file already
     * exists in storage : foo.png, foo_1.png, foo_2.png...
     *
     * @return string a relative path
     */
    private function findAvailableName($file)
    {
        $name = basename($file);

        return $name;

        if (!file_exists($this->directory.'/'.$name)) {
            return $name;
        }
        //For now we want to overwrite....
        // $dotPos = strrpos($name, '.');
        // $prefix = substr($name, 0, $dotPos);
        // $suffix = substr($name, $dotPos);

        // $count = 1;
        // do {
        //     $name = $prefix.'_'.$count.$suffix;
        //     $count++;
        // } while (file_exists($this->directory.'/'.$name));

        // return $name;
    }
}
