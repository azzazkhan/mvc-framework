<?php

namespace Illuminate\Filesystem;

use ErrorException;
use Exception;
use FilesystemIterator;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class Filesystem
{
    /**
     * Determine if a file or directory exists.
     *
     * @param  string  $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Determine if a file or directory is missing.
     *
     * @param  string  $path
     * @return bool
     */
    public function missing(string $path): bool
    {
        return !$this->exists($path);
    }

    /**
     * Prints and retrieves executed content of a file from buffer.
     * 
     * @param  string  $path
     * @return string
     */
    public function getBuffered(string $path, array $data = []): string
    {
        if ($this->isFile($path)) {
            $__path = $path;
            $__data = $data;

            return (static function () use ($__path, $__data) {
                try {
                    ob_start();

                    extract($__data, EXTR_SKIP);

                    include_once $__path;

                    $content = ob_get_clean();

                    if (ob_get_length())
                        ob_end_clean();

                    return $content;
                } catch (Exception $e) {
                    return '';
                }
            })();
        }

        throw new FileNotFoundException("File does not exist at path {$path}.");
    }

    /**
     * Get the contents of a file.
     *
     * @param  string  $path
     * @param  bool  $lock
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function get(string $path, bool $lock = false): string
    {
        if ($this->isFile($path)) {
            return $lock ? $this->sharedGet($path) : file_get_contents($path);
        }

        throw new FileNotFoundException("File does not exist at path {$path}.");
    }

    /**
     * Get contents of a file with shared access.
     *
     * @param  string  $path
     * @return string
     */
    public function sharedGet($path)
    {
        $contents = '';

        $handle = fopen($path, 'rb');

        if ($handle) {
            try {
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $path);

                    $contents = fread($handle, $this->size($path) ?: 1);

                    flock($handle, LOCK_UN);
                }
            } finally {
                fclose($handle);
            }
        }

        return $contents;
    }

    /**
     * Get the returned value of a file.
     *
     * @param  string  $path
     * @param  array  $data
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getRequire(string $path, array $data = []): mixed
    {
        if ($this->isFile($path)) {
            $__path = $path;
            $__data = $data;

            return (static function () use ($__path, $__data) {
                extract($__data, EXTR_SKIP);

                return require $__path;
            })();
        }

        throw new FileNotFoundException("File does not exist at path {$path}.");
    }

    /**
     * Require the given file once.
     *
     * @param  string  $path
     * @param  array  $data
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function requireOnce(string $path, array $data = []): mixed
    {
        if ($this->isFile($path)) {
            $__path = $path;
            $__data = $data;

            return (static function () use ($__path, $__data) {
                extract($__data, EXTR_SKIP);

                return require_once $__path;
            })();
        }

        throw new FileNotFoundException("File does not exist at path {$path}.");
    }

    /**
     * Get the hash of the file at the given path.
     *
     * @param  string  $path
     * @param  string  $algorithm
     * @return string
     */
    public function hash(string $path, string $algorithm = 'md5'): string
    {
        return hash_file($algorithm, $path);
    }

    /**
     * Write the contents of a file.
     *
     * @param  string  $path
     * @param  string  $contents
     * @param  bool  $lock
     * @return int|bool
     */
    public function put(
        string $path,
        string $contents,
        bool $lock = false
    ): int|bool {
        return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
    }

    /**
     * Write the contents of a file, replacing it atomically if it already exists.
     *
     * @param  string  $path
     * @param  string  $content
     * @return void
     */
    public function replace(string $path, string $content): void
    {
        // If the path already exists and is a symlink, get the real path...
        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;

        $tempPath = tempnam(dirname($path), basename($path));

        // Fix permissions of tempPath because `tempnam()` creates it with permissions set to 0600...
        chmod($tempPath, 0777 - umask());

        file_put_contents($tempPath, $content);

        rename($tempPath, $path);
    }

    /**
     * Replace a given string within a given file.
     *
     * @param  array|string  $search
     * @param  array|string  $replace
     * @param  string  $path
     * @return void
     */
    public function replaceInFile(
        array|string $search,
        array|string $replace,
        string $path
    ): void {
        file_put_contents($path, str_replace($search, $replace, file_get_contents($path)));
    }

    /**
     * Prepend to a file.
     *
     * @param  string  $path
     * @param  string  $data
     * @return int
     */
    public function prepend(string $path, string $data): int
    {
        if ($this->exists($path)) {
            return $this->put($path, $data . $this->get($path));
        }

        return $this->put($path, $data);
    }

    /**
     * Append to a file.
     *
     * @param  string  $path
     * @param  string  $data
     * @return int
     */
    public function append(string $path, string $data): int
    {
        return file_put_contents($path, $data, FILE_APPEND);
    }

    /**
     * Get or set UNIX mode of a file or directory.
     *
     * @param  string  $path
     * @param  int|null  $mode
     * @return mixed
     */
    public function chmod(string $path, int|null $mode = null): mixed
    {
        if ($mode) {
            return chmod($path, $mode);
        }

        return substr(sprintf('%o', fileperms($path)), -4);
    }

    /**
     * Delete the file at a given path.
     *
     * @param  string|array  $paths
     * @return bool
     */
    public function delete(string|array $paths): bool
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        foreach ($paths as $path) {
            try {
                if (@unlink($path)) {
                    clearstatcache(false, $path);
                } else {
                    $success = false;
                }
            } catch (ErrorException $e) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Move a file to a new location.
     *
     * @param  string  $path
     * @param  string  $target
     * @return bool
     */
    public function move(string $path, string $target): bool
    {
        return rename($path, $target);
    }

    /**
     * Copy a file to a new location.
     *
     * @param  string  $path
     * @param  string  $target
     * @return bool
     */
    public function copy(string $path, string $target): bool
    {
        return copy($path, $target);
    }

    /**
     * Create a symlink to the target file or directory. On Windows, a hard
     * link is created if the target is a file.
     *
     * @param  string  $target
     * @param  string  $link
     * @return void
     */
    public function link(string $target, string $link): void
    {
        if (!windows_os()) {
            symlink($target, $link);
            return;
        }

        $mode = $this->isDirectory($target) ? 'J' : 'H';

        exec("mklink /{$mode} " . escapeshellarg($link) . ' ' . escapeshellarg($target));
    }

    /**
     * Extract the file name from a file path.
     *
     * @param  string  $path
     * @return string
     */
    public function name(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Extract the trailing name component from a file path.
     *
     * @param  string  $path
     * @return string
     */
    public function basename(string $path): string
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Extract the parent directory from a file path.
     *
     * @param  string  $path
     * @return string
     */
    public function dirname(string $path): string
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    /**
     * Extract the file extension from a file path.
     *
     * @param  string  $path
     * @return string
     */
    public function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Get the file type of a given file.
     *
     * @param  string  $path
     * @return string
     */
    public function type(string $path): string
    {
        return filetype($path);
    }

    /**
     * Get the mime-type of a given file.
     *
     * @param  string  $path
     * @return string|false
     */
    public function mimeType(string $path): string|false
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }

    /**
     * Get the file size of a given file.
     *
     * @param  string  $path
     * @return int
     */
    public function size(string $path): int
    {
        return filesize($path);
    }

    /**
     * Get the file's last modification time.
     *
     * @param  string  $path
     * @return int
     */
    public function lastModified(string $path): int
    {
        return filemtime($path);
    }

    /**
     * Determine if the given path is a directory.
     *
     * @param  string  $directory
     * @return bool
     */
    public function isDirectory(string $directory): bool
    {
        return is_dir($directory);
    }

    /**
     * Determine if the given path is readable.
     *
     * @param  string  $path
     * @return bool
     */
    public function isReadable(string $path): bool
    {
        return is_readable($path);
    }

    /**
     * Determine if the given path is writable.
     *
     * @param  string  $path
     * @return bool
     */
    public function isWritable(string $path): bool
    {
        return is_writable($path);
    }

    /**
     * Determine if two files are the same by comparing their hashes.
     *
     * @param  string  $firstFile
     * @param  string  $secondFile
     * @return bool
     */
    public function hasSameHash(string $firstFile, string $secondFile): bool
    {
        $hash = @md5_file($firstFile);

        return $hash && $hash === @md5_file($secondFile);
    }

    /**
     * Determine if the given path is a file.
     *
     * @param  string  $file
     * @return bool
     */
    public function isFile(string $file): bool
    {
        return is_file($file);
    }

    /**
     * Find path names matching a given pattern.
     *
     * @param  string  $pattern
     * @param  int  $flags
     * @return array
     */
    public function glob(string $pattern, int $flags = 0): array
    {
        return glob($pattern, $flags);
    }

    /**
     * Ensure a directory exists.
     *
     * @param  string  $path
     * @param  int  $mode
     * @param  bool  $recursive
     * @return void
     */
    public function ensureDirectoryExists(
        string $path,
        int $mode = 0755,
        bool $recursive = true
    ): void {
        if (!$this->isDirectory($path)) {
            $this->makeDirectory($path, $mode, $recursive);
        }
    }

    /**
     * Create a directory.
     *
     * @param  string  $path
     * @param  int  $mode
     * @param  bool  $recursive
     * @param  bool  $force
     * @return bool
     */
    public function makeDirectory(
        string $path,
        int $mode = 0755,
        bool $recursive = false,
        bool $force = false
    ): bool {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }

        return mkdir($path, $mode, $recursive);
    }

    /**
     * Move a directory.
     *
     * @param  string  $from
     * @param  string  $to
     * @param  bool  $overwrite
     * @return bool
     */
    public function moveDirectory(string $from, string $to, bool $overwrite = false): bool
    {
        if ($overwrite && $this->isDirectory($to) && !$this->deleteDirectory($to)) {
            return false;
        }

        return @rename($from, $to) === true;
    }

    /**
     * Copy a directory from one location to another.
     *
     * @param  string  $directory
     * @param  string  $destination
     * @param  int|null  $options
     * @return bool
     */
    public function copyDirectory(
        string $directory,
        string $destination,
        int|null $options = null
    ): bool {
        if (!$this->isDirectory($directory)) {
            return false;
        }

        $options = $options ?: FilesystemIterator::SKIP_DOTS;

        // If the destination directory does not actually exist, we will go ahead and
        // create it recursively, which just gets the destination prepared to copy
        // the files over. Once we make the directory we'll proceed the copying.
        $this->ensureDirectoryExists($destination, 0777);

        $items = new FilesystemIterator($directory, $options);

        foreach ($items as $item) {
            // As we spin through items, we will check to see if the current file is actually
            // a directory or a file. When it is actually a directory we will need to call
            // back into this function recursively to keep copying these nested folders.
            $target = $destination . '/' . $item->getBasename();

            if ($item->isDir()) {
                $path = $item->getPathname();

                if (!$this->copyDirectory($path, $target, $options)) {
                    return false;
                }
            }

            // If the current items is just a regular file, we will just copy this to the new
            // location and keep looping. If for some reason the copy fails we'll bail out
            // and return false, so the developer is aware that the copy process failed.
            elseif (!$this->copy($item->getPathname(), $target)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Recursively delete a directory.
     *
     * The directory itself may be optionally preserved.
     *
     * @param  string  $directory
     * @param  bool  $preserve
     * @return bool
     */
    public function deleteDirectory(string $directory, bool $preserve = false): bool
    {
        if (!$this->isDirectory($directory)) {
            return false;
        }

        $items = new FilesystemIterator($directory);

        foreach ($items as $item) {
            // If the item is a directory, we can just recurse into the function and
            // delete that sub-directory otherwise we'll just delete the file and
            // keep iterating through each file until the directory is cleaned.
            if ($item->isDir() && !$item->isLink()) {
                $this->deleteDirectory($item->getPathname());
            }

            // If the item is just a file, we can go ahead and delete it since we're
            // just looping through and waxing all of the files in this directory
            // and calling directories recursively, so we delete the real path.
            else {
                $this->delete($item->getPathname());
            }
        }

        if (!$preserve) {
            @rmdir($directory);
        }

        return true;
    }

    /**
     * Empty the specified directory of all files and folders.
     *
     * @param  string  $directory
     * @return bool
     */
    public function cleanDirectory(string $directory): bool
    {
        return $this->deleteDirectory($directory, true);
    }
}
