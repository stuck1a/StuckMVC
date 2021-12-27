<?php
/*
 *  This file is part of StuckMVC <https://stuck1a.de/coding/stuckmvc>,
 *  Copyright (c) 2021.
 *  The StuckMVC framework is free software: you can redistribute it and/or modify it
 *  under the terms of the GNU General Public License version 3 as published by
 *  the Free Software Foundation.
 *  
 *  It is distributed in the hope that it will be useful, but without any warranty;
 *  without even the implied warranty of merchantability of fitness for a
 *  particular purpose. See the GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License along with
 *  StuckMVC. If not, see <https://www.gnu.org/licenses/>. 
 *
 *  FOR A SOMEWHAT FREER WORLD.
 */

namespace Stuck1A\StuckMVC\Core;

use FilesystemIterator;
use Generator;


class FileSystemAdapter {
  
  public const TYPEFILTER_CHILD = 0;
  public const TYPEFILTER_EQUAL = 1;
  
  
  /**
   * Reads any ascii file line by line<br>
   * <i>Modern generator version of the legacy <code>Base::readFile()</code> (which just returns the filled array)</i><br><br>
   * It must be yielded per line (buffer) which mostly result in an enormous reduction of memory peak and execution time
   * (the more, the shorter the longest line in file)<br><br>
   * Unfortunately, the handling is not quite as comfortable as with the legacy version.
   * 
   * @param string $file Path to the file. Relative to document root or as absolute realpath
   *              
   * @return Generator Iterable object with currently yielded line.
   */
  public static function yieldFromFile(string $file): Generator {
    if ( $fh = fopen($file, 'r') ) {
      while( !feof($fh) ) {
        yield trim(fgets($fh));
      }
      fclose($fh);
    } else {
      Logger::send('An Error occurred while opening file \'' . $file .'\' - could not open file (is it locked?)');
    }
  }
  
  
  public static function readFile(string $file): ?string {
    return file_get_contents($file, true);
  }
  
  
  /**
   * Writes any string to a given file<br>
   * 
   * @param string $file   File to write to.
   * @param string $data   String or any object which offers the magic method <i>__toString()</i>.
   * @param string $mode   The write mode in which the file will be opened. By default it will open in (a)ppend mode,
   *                       which creates a new file if <var>$file</var> doesn't exist and set the file pointer to the
   *                       end so <var>$data</var> will be <i>appended</i> to any existing content. Any write-only modes
   *                       from <i>fopen()</i> are allowed.
   * @param string $append [optional]<br>
   *                       Any string which will be appended to <var>$data</var>.<br>By default the standard line break
   *                       char defined by <i>PHP_EOL</i> will be used. Use an empty string or <i>null</i> to append
   *                       nothing.
   *                       
   * @return bool True on success, false on failure.
   */
  public static function write2File(string $file, string $data, string $mode = 'w', string $append = PHP_EOL): bool {
    if ( $fh = fopen($file, $mode) ) {
      if ( !fwrite($fh, $data . ($append ?? '')) ) {
        $msg = 'An Error occurred while writing file \"' . $file .
               '\" - access denied. Make sure you have write permissions and there is no write lock set.';
        Logger::send($msg, 'warning');
        return false;
      }
      fclose($fh);
      return true;
    }
    $msg = 'Could not open file \"' . $file .
           '\" - it seems to be locked by another application.';
    Logger::send($msg, 'warning');
    return false;
  }
  
  
  /**
   * Writes iterable data (generator, streams, ...) to a file<br>
   * This is the preferred function to write large amounts of data like large database tables to a file since it will
   * significantly reduce the memory usage (down to the largest data chunk). To write a single string to a file, use
   * FileSystemAdapter::write2file instead or stream2file (coming soon) if it is a way large string (like from
   * serialisation of big structures).
   * 
   * @param string    $file The file to write to.
   * @param Generator $data The iterable which yield strings or objects which offers the magic method <i>__toString()</i>.
   * @param string    $mode The write mode in which the file will be opened. By default it will open in (a)ppend mode,
   *                        which creates a new file if <var>$file</var> doesn't exist and set the file pointer to the
   *                        end so <var>$data</var> will be <i>appended</i> to any existing content. Any write-only
   *                        modes from <i>fopen()</i> are allowed.
   * @param string    $sep  [optional]<br>
   *                        Any string which will be appended to <var>$data</var>.<br>By default the standard line break
   *                        char defined by <i>PHP_EOL</i> will be used. Use an empty string or <i>null</i> to append
   *                        nothing.
   */
  public static function yield2File(string $file, Generator $data, string $mode = 'a', string $sep = PHP_EOL) {
    if ( $fh = fopen($file, $mode) ) {
      while ( $data->valid() ) {
        fwrite($fh, $data->current() . $sep);
        $data->next();
      }
      fclose($fh);
    } else {
      Logger::send('An Error occurred while opening file \'' . $file .'\'.');
    }
  }
  
  
  /**
   * Iterates through the lines of given file (which e.g. represents one database entry for model files)
   * and searchs them for the given regex pattern. Returns match groups from the first line which matches the pattern
   * or false if no match found. Throws an FileNotFoundException if $filepath doesn't point to an readable ascii file.
   * 
   * @param string $filepath path to ascii file to search in (absolute or relative from doc root)
   * @param string $pattern  regex pattern to search for
   *
   * @return ?array|false resolved content of the patterns match groups
   * 
   * @example $matchingLine = FileSystemOperator::fetchLineWithPattern('/tmp/models/users.txt', '/email=(.*@.*\.[A-Za-z0-9]*)/i');
   */
  public static function matchFileLinesByPattern(string $filepath, string $pattern): ?array {
    foreach ( FileSystemAdapter::yieldFromFile($filepath) as $line ) {
      if ( preg_match($pattern, $line, $matches) ) {
        return $matches;
      }
    }
    return false;
  }
  

  /**
   * @TODO: Facts (werden dann späer vom Setup erzeugt) einführen und da dann v.a. TypeMap.
   *        TypeMap enthält dann wieder fixe Keys auf die sich das Framework verlasst und als Value dann den FQN zur
   *        Klasse, die den Typ repräsentiert. Hier werden dann vorallem die Controller-Types benötigt, um die Funktion
   *        ordentlich auszuprogrammieren (Default Parameter für filterClass)
   * Scans a directory for PHP class definitions making use of PSR conventions<br><br>
   *
   * @param string        $path            Path to target directory.
   * @param string|object $filterClass     The target class the filter logic will use. Can be a object or class
   *                                       reference to the, too.
   * @param int           $filterMode      Defines the filter mode. By default, TYPEFILTER_EQUAL is used.
   *                                       Possible values are:
   *                                       TYPEFILTER_CHILD (0) - only collect classes which are subclasses of
   *                                       to$filterType. TYPEFILTER_EQUAL (1) - only collect classes which are
   *                                       identical to $filterType.
   *
   * @return ?string[]                     List of fully qualified namespaces to all matching classes or null for
   *                                       invalid $path.
   */
  public static function getClassesInDir(string $path, $filterClass = 'Stuck1A\\StuckMVC\\Core\\Controller', int $filterMode = self::TYPEFILTER_CHILD): ?array {
    if ( !is_dir($path) ) {
      return null;
    }
    $classList = [];
    $files = new FilesystemIterator($path);
    foreach ( $files as $file ) {
      // drop files without PHP file extension
      if ( $file->isFile() && strtolower($file->getExtension()) === 'php' ) {
        // fetch relevant data from the class file // TODO: pattern so schreiben, das es auch ohne "extends" greift
        $className = (FileSystemAdapter::matchFileLinesByPattern($file->getPathName(), '/^(\s*|.*;+\s*)class\s+(.*)\s+extends\s+(.*)\s/i'))[2];
        $namespace = (FileSystemAdapter::matchFileLinesByPattern($file->getPathName(), '/^(\s*|.*;+\s*)namespace\s+(.*)\s*;/i'))[2];
        $sFQN = '\\' . $namespace . '\\' . $className;
        // try to create a reflection from the data
        try {
         // include_once 'C:\xampp\htdocs\projects\StucksSeiten/application/controller/admin/DashboardController.php';
          $oReflection = new \ReflectionClass($sFQN);
        } catch ( \ReflectionException $ex ) {
          // log and skip on error
          $msg = "ReflectionError occurred while scanning '{$path}' for class files. Skipped target class '{$sFQN}'.";
          Logger::send($msg, 'warning');
          continue;
        }
        
        // apply type filter and collect what remains
        switch ($filterMode) {
          case self::TYPEFILTER_CHILD: {
            if ( $oReflection->isSubclassOf($filterClass) ) {
              $classList[] = $sFQN;
            }
            break;
          }
          case self::TYPEFILTER_EQUAL: {
            try {
              $oReflectionTarget = new \ReflectionClass($filterClass);
            } catch ( \ReflectionException $ex ) {
              // log and skip on error
              $msg = "ReflectionError occurred while scanning \'{$path}\' for class files. Target \'{$sFQN}\' skipped.";
              Logger::send($msg, 'warning');
              continue 2;
            }
            if ( $oReflection->isInstance($oReflectionTarget)) {
              $classList[] = $sFQN;
            }
            break;
          }
        }
      }
    }
    return $classList;
  }
  
  
  /**
   * Collects all names of directories within a location
   * 
   * @param string $path directory path to scan
   * 
   * @return string[]|false list of directories within $path (except virtual folders "." and "..") or false if $path 
   *                        is no directory.
   */
  public static function getSubDirNames(string $path): array {
    if ( is_dir($path) ) {
      $dirList = glob($path . '/*', GLOB_NOSORT | GLOB_ONLYDIR);
      array_walk($dirList, function(&$x) { $x = pathinfo($x)['filename']; });
      return $dirList;
    }
    return false;
  }
  
  
  /**
   * Traverses a directory recursively and returns a path list of all files and directories found<br><br>
   * 
   * <i>Use this function with care. Walking through large file system structures may be very time consuming.<br>
   * Therefore it's recommend to cache the results whenever possible to avoid unnecessary calls.</i>
   *
   * @param   string  $dir              The directory from which to start.
   * @param   ?string $fileWhitelist    [optional]<br>
   *                                    List only files which matches a specific regex pattern. Useful to filter by
   *                                    file extensions or similar.
   * @param   array   $dirBlacklist     [optional]<br>
   *                                    Allows to define a directory blacklist. Any directory with matching name will be
   *                                    skipped while scanning. This includes the directory itself and also its content!
   *
   * @return  array                     A list of paths to each matching subject found.<br>
   *                                    Note that directory levels are not taken into account, so all paths are stored at the
   *                                    lists top level regardless of the depth at which the associated file info was captured.
   */
  public static function getRecursivePathListFromDir(string $dir, string $fileWhitelist = null, array $dirBlacklist = []): array {
    $paths = [];
    $blacklist = [];
    $file = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::SELF_FIRST );
    $file->rewind();
    while ( $file->valid() ) {
      // skip files which are located within any directory marked as blacklisted
      foreach ( $blacklist as $excludedPath ) {
        if ( Base::str_contains($file->current()->getRealPath(), $excludedPath) ) {
          $file->next();
          continue 2;
        }
      }
      // collect paths of all blacklisted directories found to enable easier skipping of their content
      if ( $file->current()->isDir() && !empty($dirBlacklist) && in_array($file->current()->getFilename(), $dirBlacklist)  ) {
        $blacklist[] = $file->current()->getRealPath();
        // also don't add the path of the blacklisted directory itself
        $file->next();
        continue;
        }
      // skip files which won't match against whitelist expression, if set
      if ( isset($fileWhitelist) && $file->current()->isFile() && !preg_match($fileWhitelist, $file->current()->getFilename()) ) {
        $file->next();
        continue;
      }
      $paths[] = $file->current()->getRealPath();
      $file->next();
    }
    return $paths;
  }
  
}
