<?php

namespace FriendsOfTYPO3\Migrations\Utility;

/**
 * Class Files
 * @package FriendsOfTYPO3\Migrations\Utility
 * todo improve me!
 * Credits to Neos .... Neos\Utility\Files
 */
abstract class Files
{
    /**
     * Replacing backslashes and double slashes to slashes.
     * It's needed to compare paths (especially on windows).
     */
    public static function getUnixStylePath(string $path): string
    {
        if (strpos($path, ':') === false) {
            return str_replace(['//', '\\'], '/', $path);
        }
        return preg_replace('/^([a-z]{2,}):\//', '$1://', str_replace(['//', '\\'], '/', $path));
    }

    /**
     * Properly glues together filepaths / filenames by replacing
     * backslashes and double slashes of the specified paths.
     * Note: trailing slashes will be removed, leading slashes won't.
     * Usage: concatenatePaths(array('dir1/dir2', 'dir3', 'file'))
     */
    public static function concatenatePaths(array $paths): string
    {
        $resultingPath = '';
        foreach ($paths as $index => $path) {
            $path = self::getUnixStylePath($path);
            if ($index === 0) {
                $path = rtrim($path, '/');
            } else {
                $path = trim($path, '/');
            }
            if ($path !== '') {
                $resultingPath .= $path . '/';
            }
        }
        return rtrim($resultingPath, '/');
    }
}
