<?php
/**
 * Generic PHP stream wrapper interface.
 *
 * @see http://www.php.net/manual/en/class.streamwrapper.php
 */
interface DPSAE_StreamWrapperInterface {
  public function stream_open($uri, $mode, $options, &$opened_url);
  public function stream_close();
  public function stream_lock($operation);
  public function stream_read($count);
  public function stream_write($data);
  public function stream_eof();
  public function stream_seek($offset, $whence);
  public function stream_flush();
  public function stream_tell();
  public function stream_stat();
  public function unlink($uri);
  public function rename($from_uri, $to_uri);
  public function mkdir($uri, $mode, $options);
  public function rmdir($uri, $options);
  public function url_stat($uri, $flags);
  public function dir_opendir($uri, $options);
  public function dir_readdir();
  public function dir_rewinddir();
  public function dir_closedir();
}

/**
 * Drupal stream wrapper extension.
 *
 * Extend the StreamWrapperInterface with methods expected by Drupal stream
 * wrapper classes.
 */
interface DPSAE_DrupalStreamWrapperInterface extends DPSAE_StreamWrapperInterface {
  /**
   * Set the absolute stream resource URI.
   *
   * This allows you to set the URI. Generally is only called by the factory
   * method.
   *
   * @param $uri
   *   A string containing the URI that should be used for this instance.
   */
  function setUri($uri);

  /**
   * Returns the stream resource URI.
   *
   * @return
   *   Returns the current URI of the instance.
   */
  public function getUri();

  /**
   * Returns a web accessible URL for the resource.
   *
   * This function should return a URL that can be embedded in a web page
   * and accessed from a browser. For example, the external URL of
   * "youtube://xIpLd0WQKCY" might be
   * "http://www.youtube.com/watch?v=xIpLd0WQKCY".
   *
   * @return
   *   Returns a string containing a web accessible URL for the resource.
   */
  public function getExternalUrl();

  /**
   * Returns the MIME type of the resource.
   *
   * @param $uri
   *   The URI, path, or filename.
   * @param $mapping
   *   An optional map of extensions to their mimetypes, in the form:
   *    - 'mimetypes': a list of mimetypes, keyed by an identifier,
   *    - 'extensions': the mapping itself, an associative array in which
   *      the key is the extension and the value is the mimetype identifier.
   *
   * @return
   *   Returns a string containing the MIME type of the resource.
   */
  public static function getMimeType($uri, $mapping = NULL);

  /**
   * Changes permissions of the resource.
   *
   * PHP lacks this functionality and it is not part of the official stream
   * wrapper interface. This is a custom implementation for Drupal.
   *
   * @param $mode
   *   Integer value for the permissions. Consult PHP chmod() documentation
   *   for more information.
   *
   * @return
   *   Returns TRUE on success or FALSE on failure.
   */
  public function chmod($mode);

  /**
   * Returns canonical, absolute path of the resource.
   *
   * Implementation placeholder. PHP's realpath() does not support stream
   * wrappers. We provide this as a default so that individual wrappers may
   * implement their own solutions.
   *
   * @return
   *   Returns a string with absolute pathname on success (implemented
   *   by core wrappers), or FALSE on failure or if the registered
   *   wrapper does not provide an implementation.
   */
  public function realpath();

  /**
   * Gets the name of the directory from a given path.
   *
   * This method is usually accessed through drupal_dirname(), which wraps
   * around the normal PHP dirname() function, which does not support stream
   * wrappers.
   *
   * @param $uri
   *   An optional URI.
   *
   * @return
   *   A string containing the directory name, or FALSE if not applicable.
   *
   * @see drupal_dirname()
   */
  public function dirname($uri = NULL);
}


/**
 * Drupal stream wrapper base class for local files.
 *
 * This class provides a complete stream wrapper implementation. URIs such as
 * "public://example.txt" are expanded to a normal filesystem path such as
 * "sites/default/files/example.txt" and then PHP filesystem functions are
 * invoked.
 *
 * DrupalLocalStreamWrapper implementations need to implement at least the
 * getDirectoryPath() and getExternalUrl() methods.
 */
abstract class DPSAE_StreamWrapper implements DPSAE_DrupalStreamWrapperInterface {
  /**
   * Stream context resource.
   *
   * @var Resource
   */
  public $context;

  /**
   * A generic resource handle.
   *
   * @var Resource
   */
  public $handle = NULL;

  /**
   * Instance URI (stream).
   *
   * A stream is referenced as "scheme://target".
   *
   * @var String
   */
  protected $uri;
  
    private $writen = true;

    public function __construct()
    {
        $this->stor = new SaeStorage();
    }

    public function stor() {
        if ( !isset( $this->stor ) ) $this->stor = new SaeStorage();
    }

  /**
   * Gets the path that the wrapper is responsible for.
   * @TODO: Review this method name in D8 per http://drupal.org/node/701358
   *
   * @return
   *   String specifying the path.
   */
  abstract function getDirectoryPath();

  /**
   * Base implementation of setUri().
   */
  function setUri($uri) {
    $this->uri = $uri;
  }

  /**
   * Base implementation of getUri().
   */
  function getUri() {
    return $this->uri;
  }

  /**
   * Returns the local writable target of the resource within the stream.
   *
   * This function should be used in place of calls to realpath() or similar
   * functions when attempting to determine the location of a file. While
   * functions like realpath() may return the location of a read-only file, this
   * method may return a URI or path suitable for writing that is completely
   * separate from the URI used for reading.
   *
   * @param $uri
   *   Optional URI.
   *
   * @return
   *   Returns a string representing a location suitable for writing of a file,
   *   or FALSE if unable to write to the file such as with read-only streams.
   */
  protected function getTarget($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }

    list($scheme, $target) = explode('://', $uri, 2);

    // Remove erroneous leading or trailing, forward-slashes and backslashes.
    return trim($target, '\/');
  }

  /**
   * Base implementation of getMimeType().
   */
  static function getMimeType($uri, $mapping = NULL) {
    if (!isset($mapping)) {
      // The default file map, defined in file.mimetypes.inc is quite big.
      // We only load it when necessary.
      include_once DRUPAL_ROOT . '/includes/file.mimetypes.inc';
      $mapping = file_mimetype_mapping();
    }

    $extension = '';
    $file_parts = explode('.', drupal_basename($uri));

    // Remove the first part: a full filename should not match an extension.
    array_shift($file_parts);

    // Iterate over the file parts, trying to find a match.
    // For my.awesome.image.jpeg, we try:
    //   - jpeg
    //   - image.jpeg, and
    //   - awesome.image.jpeg
    while ($additional_part = array_pop($file_parts)) {
      $extension = strtolower($additional_part . ($extension ? '.' . $extension : ''));
      if (isset($mapping['extensions'][$extension])) {
        return $mapping['mimetypes'][$mapping['extensions'][$extension]];
      }
    }

    return 'application/octet-stream';
  }

  /**
   * Base implementation of chmod().
   */
  function chmod($mode) {
	  return true;
  }

  /**
   * Base implementation of realpath().
   */
  function realpath() {
    return $this->getLocalPath();
  }

  /**
   * Returns the canonical absolute path of the URI, if possible.
   *
   * @param string $uri
   *   (optional) The stream wrapper URI to be converted to a canonical
   *   absolute path. This may point to a directory or another type of file.
   *
   * @return string|false
   *   If $uri is not set, returns the canonical absolute path of the URI
   *   previously set by the DrupalStreamWrapperInterface::setUri() function.
   *   If $uri is set and valid for this class, returns its canonical absolute
   *   path, as determined by the realpath() function. If $uri is set but not
   *   valid, returns FALSE.
   */
  protected function getLocalPath($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }
    $path = $this->getDirectoryPath() . '/' . $this->getTarget($uri);
    return $path;
  }

  /**
   * Support for fopen(), file_get_contents(), file_put_contents() etc.
   *
   * @param $uri
   *   A string containing the URI to the file to open.
   * @param $mode
   *   The file mode ("r", "wb" etc.).
   * @param $options
   *   A bit mask of STREAM_USE_PATH and STREAM_REPORT_ERRORS.
   * @param $opened_path
   *   A string containing the path actually opened.
   *
   * @return
   *   Returns TRUE if file was opened successfully.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-open.php
   */
  public function stream_open($uri, $mode, $options, &$opened_path){
    $this->uri = $uri;
    $path = $this->getLocalPath();

        $pathinfo = parse_url($path);
        $this->domain = $pathinfo['host'];
        $this->file = ltrim(strstr($path, $pathinfo['path']), '/\\');
        $this->position = 0;
        $this->mode = $mode;
        $this->options = $options;
//print_r($this);
        // print_r("OPEN\tpath:{$path}\tmode:{$mode}\toption:{$option}\topened_path:{$opened_path}\n");

        if ( in_array( $this->mode, array( 'r', 'r+', 'rb' ) ) ) {
            if ( $this->fcontent = $this->stor->read($this->domain, $this->file) ) {
            } else {
                trigger_error("fopen({$path}): failed to read from Storage: No such domain or file.", E_USER_WARNING);
                return false;
            }
        } elseif ( in_array( $this->mode, array( 'a', 'a+', 'ab' ) ) ) {
            trigger_error("fopen({$path}): Sorry, saestor does not support appending", E_USER_WARNING);
            if ( $this->fcontent = $this->stor->read($this->domain, $this->file) ) {
            } else {
                trigger_error("fopen({$path}): failed to read from Storage: No such domain or file.", E_USER_WARNING);
                return false;
            }
        } elseif ( in_array( $this->mode, array( 'x', 'x+', 'xb' ) ) ) {
            if ( !$this->stor->getAttr($this->domain, $this->file) ) {
                $this->fcontent = '';
            } else {
                trigger_error("fopen({$path}): failed to create at Storage: File exists.", E_USER_WARNING);
                return false;
            }
        } elseif ( in_array( $this->mode, array( 'w', 'w+', 'wb' ) ) ) {
            $this->fcontent = '';
        } else {
            $this->fcontent = $this->stor->read($this->domain, $this->file);
        }

        return true;
  }

  /**
   * Support for flock().
   *
   * @param $operation
   *   One of the following:
   *   - LOCK_SH to acquire a shared lock (reader).
   *   - LOCK_EX to acquire an exclusive lock (writer).
   *   - LOCK_UN to release a lock (shared or exclusive).
   *   - LOCK_NB if you don't want flock() to block while locking (not
   *     supported on Windows).
   *
   * @return
   *   Always returns TRUE at the present time.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-lock.php
   */
  public function stream_lock($operation) {
	  return true;
  }

  /**
   * Support for fread(), file_get_contents() etc.
   *
   * @param $count
   *   Maximum number of bytes to be read.
   *
   * @return
   *   The string that was read, or FALSE in case of an error.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-read.php
   */
    public function stream_read($count)
    {
        if (in_array($this->mode, array('w', 'x', 'a', 'wb', 'xb', 'ab') ) ) {
            return false;
        }

        $ret = substr( $this->fcontent , $this->position, $count);
        $this->position += strlen($ret);

        return $ret;
    }

  /**
   * Support for fwrite(), file_put_contents() etc.
   *
   * @param $data
   *   The string to be written.
   *
   * @return
   *   The number of bytes written (integer).
   *
   * @see http://php.net/manual/en/streamwrapper.stream-write.php
   */
    public function stream_write($data)
    {
        if ( in_array( $this->mode, array( 'r', 'rb' ) ) ) {
            return false;
        }

        // print_r("WRITE\tcontent:".strlen($this->fcontent)."\tposition:".$this->position."\tdata:".strlen($data)."\n");

        $left = substr($this->fcontent, 0, $this->position);
        $right = substr($this->fcontent, $this->position + strlen($data));
        $this->fcontent = $left . $data . $right;

        //if ( $this->stor->write( $this->domain, $this->file, $this->fcontent ) ) {
        $this->position += strlen($data);
        if ( strlen( $data ) > 0 )
            $this->writen = false;

        return strlen( $data );
        //}
        //else return false;
    }

  /**
   * Support for feof().
   *
   * @return
   *   TRUE if end-of-file has been reached.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-eof.php
   */
    public function stream_eof()
    {
        return $this->position >= strlen( $this->fcontent  );
    }

  /**
   * Support for fseek().
   *
   * @param $offset
   *   The byte offset to got to.
   * @param $whence
   *   SEEK_SET, SEEK_CUR, or SEEK_END.
   *
   * @return
   *   TRUE on success.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-seek.php
   */
    public function stream_seek($offset , $whence = SEEK_SET)
    {


        switch ($whence) {
        case SEEK_SET:

            if ($offset < strlen( $this->fcontent ) && $offset >= 0) {
                $this->position = $offset;
                return true;
            }
            else
                return false;

            break;

        case SEEK_CUR:

            if ($offset >= 0) {
                $this->position += $offset;
                return true;
            }
            else
                return false;

            break;

        case SEEK_END:

            if (strlen( $this->fcontent ) + $offset >= 0) {
                $this->position = strlen( $this->fcontent ) + $offset;
                return true;
            }
            else
                return false;

            break;

        default:

            return false;
        }
    }

  /**
   * Support for fflush().
   *
   * @return
   *   TRUE if data was successfully stored (or there was no data to store).
   *
   * @see http://php.net/manual/en/streamwrapper.stream-flush.php
   */
    public function stream_flush() {
        if (!$this->writen) {
            $this->stor->write( $this->domain, $this->file, $this->fcontent );
            $this->writen = true;
        }

        return $this->writen;
    }

  /**
   * Support for ftell().
   *
   * @return
   *   The current offset in bytes from the beginning of file.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-tell.php
   */
    public function stream_tell()
    {

        return $this->position;
    }

  /**
   * Support for fstat().
   *
   * @return
   *   An array with file status, or FALSE in case of an error - see fstat()
   *   for a description of this array.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-stat.php
   */
    public function stream_stat() {
        return array();
    }

  /**
   * Support for fclose().
   *
   * @return
   *   TRUE if stream was successfully closed.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-close.php
   */
    public function stream_close()
    {
        if (!$this->writen) {
            $this->stor->write( $this->domain, $this->file, $this->fcontent );
            $this->writen = true;
        }
    }

  /**
   * Support for unlink().
   *
   * @param $uri
   *   A string containing the uri to the resource to delete.
   *
   * @return
   *   TRUE if resource was successfully deleted.
   *
   * @see http://php.net/manual/en/streamwrapper.unlink.php
   */
    public function unlink($path)
    {
		$path = $this->getLocalPath($path);
        self::stor();
        $pathinfo = parse_url($path);
        $this->domain = $pathinfo['host'];
        $this->file = ltrim(strstr($path, $pathinfo['path']), '/\\');

        clearstatcache( true );
        return $this->stor->delete( $this->domain , $this->file );
    }

  /**
   * Support for rename().
   *
   * @param $from_uri,
   *   The uri to the file to rename.
   * @param $to_uri
   *   The new uri for file.
   *
   * @return
   *   TRUE if file was successfully renamed.
   *
   * @see http://php.net/manual/en/streamwrapper.rename.php
   */
  public function rename($from_uri, $to_uri) {
    return false;
  }

  /**
   * Gets the name of the directory from a given path.
   *
   * This method is usually accessed through drupal_dirname(), which wraps
   * around the PHP dirname() function because it does not support stream
   * wrappers.
   *
   * @param $uri
   *   A URI or path.
   *
   * @return
   *   A string containing the directory name.
   *
   * @see drupal_dirname()
   */
  public function dirname($uri = NULL) {
    list($scheme, $target) = explode('://', $uri, 2);
    $target  = $this->getTarget($uri);
    $dirname = dirname($target);

    if ($dirname == '.') {
      $dirname = '';
    }

    return $scheme . '://' . $dirname;
  }

  /**
   * Support for mkdir().
   *
   * @param $uri
   *   A string containing the URI to the directory to create.
   * @param $mode
   *   Permission flags - see mkdir().
   * @param $options
   *   A bit mask of STREAM_REPORT_ERRORS and STREAM_MKDIR_RECURSIVE.
   *
   * @return
   *   TRUE if directory was successfully created.
   *
   * @see http://php.net/manual/en/streamwrapper.mkdir.php
   */
  public function mkdir($uri, $mode, $options) {
    return true;
  }

  /**
   * Support for rmdir().
   *
   * @param $uri
   *   A string containing the URI to the directory to delete.
   * @param $options
   *   A bit mask of STREAM_REPORT_ERRORS.
   *
   * @return
   *   TRUE if directory was successfully removed.
   *
   * @see http://php.net/manual/en/streamwrapper.rmdir.php
   */
  public function rmdir($uri, $options) {
	  return true;
  }

  /**
   * Support for stat().
   *
   * @param $uri
   *   A string containing the URI to get information about.
   * @param $flags
   *   A bit mask of STREAM_URL_STAT_LINK and STREAM_URL_STAT_QUIET.
   *
   * @return
   *   An array with file status, or FALSE in case of an error - see fstat()
   *   for a description of this array.
   *
   * @see http://php.net/manual/en/streamwrapper.url-stat.php
   */
    public function url_stat($path, $flags) {
		$path = $this->getLocalPath($path);
        self::stor();
        $pathinfo = parse_url($path);
        $this->domain = $pathinfo['host'];
        $this->file = ltrim(strstr($path, $pathinfo['path']), '/\\');

        if ( $attr = $this->stor->getAttr( $this->domain , $this->file ) ) {
            $stat = array();
            $stat['dev'] = $stat[0] = 0x8001;
            $stat['ino'] = $stat[1] = 0;
            $stat['mode'] = $stat[2] = 33279; //0100000 + 0777;
            $stat['nlink'] = $stat[3] = 0;
            $stat['uid'] = $stat[4] = 0;
            $stat['gid'] = $stat[5] = 0;
            $stat['rdev'] = $stat[6] = 0;
            $stat['size'] = $stat[7] = $attr['length'];
            $stat['atime'] = $stat[8] = 0;
            $stat['mtime'] = $stat[9] = $attr['datetime'];
            $stat['ctime'] = $stat[10] = $attr['datetime'];
            $stat['blksize'] = $stat[11] = 0;
            $stat['blocks'] = $stat[12] = 0;
            return $stat;
        } else {
			//has extension
			if(false !== strpos($path, '.')){
				return false;
			}
			//dir
			$stat = array();
			$stat['dev'] = $stat[0] = 0x8001;
            $stat['ino'] = $stat[1] = 0;
            $stat['mode'] = $stat[2] = 16895; //040000 + 0777;
            $stat['nlink'] = $stat[3] = 0;
            $stat['uid'] = $stat[4] = 0;
            $stat['gid'] = $stat[5] = 0;
            $stat['rdev'] = $stat[6] = 0;
            $stat['size'] = $stat[7] = 4096;
            $stat['atime'] = $stat[8] = 0;
            $stat['mtime'] = $stat[9] = 0;
            $stat['ctime'] = $stat[10] = 0;
            $stat['blksize'] = $stat[11] = 0;
            $stat['blocks'] = $stat[12] = 0;
            return $stat;
        }
    }

  /**
   * Support for opendir().
   *
   * @param $uri
   *   A string containing the URI to the directory to open.
   * @param $options
   *   Unknown (parameter is not documented in PHP Manual).
   *
   * @return
   *   TRUE on success.
   *
   * @see http://php.net/manual/en/streamwrapper.dir-opendir.php
   */
  public function dir_opendir($uri, $options) {
    return false;
  }

  /**
   * Support for readdir().
   *
   * @return
   *   The next filename, or FALSE if there are no more files in the directory.
   *
   * @see http://php.net/manual/en/streamwrapper.dir-readdir.php
   */
  public function dir_readdir() {
    return false;
  }

  /**
   * Support for rewinddir().
   *
   * @return
   *   TRUE on success.
   *
   * @see http://php.net/manual/en/streamwrapper.dir-rewinddir.php
   */
  public function dir_rewinddir() {
    return false;
  }

  /**
   * Support for closedir().
   *
   * @return
   *   TRUE on success.
   *
   * @see http://php.net/manual/en/streamwrapper.dir-closedir.php
   */
  public function dir_closedir() {
    return false;
  }
  
  public function getStorageUrl() {
	  $path = $this->getLocalPath();
        $pathinfo = parse_url($path);
        $domain = $pathinfo['host'];
        $file = ltrim(strstr($path, $pathinfo['path']), '/\\');
        
	  return 'http://'.$_SERVER['HTTP_APPNAME'].'-'.$domain.'.stor.sinaapp.com/'.$file;
  }
}

class DrupalStorageWrapper extends DPSAE_StreamWrapper
{
  protected function getLocalPath($uri = NULL) {
	  if(!isset($uri)){
		  $uri = $this->uri;
	  }
	 return $uri;
	}

	  public function getDirectoryPath() {
		return '';
	  }

	  /**
	   * Overrides getExternalUrl().
	   *
	   * Return the HTML URI of a private file.
	   */
	  function getExternalUrl() {
		  return '';
	  }
}

class DrupalStorageWrapper_ extends SaeStorageWrapper
{
    public function mkdir($path, $mode, $options) {
        return true;
    }

    public function rmdir($path, $options) {
        return true;
    }
    
    public function url_stat($path, $flags) {
        self::stor();
        $pathinfo = parse_url($path);
        $this->domain = $pathinfo['host'];
        $this->file = ltrim(strstr($path, $pathinfo['path']), '/\\');
        if ( $attr = $this->stor->getAttr( $this->domain , $this->file ) ) {
            $stat = array();
            $stat['dev'] = $stat[0] = 0x8001;
            $stat['ino'] = $stat[1] = 0;
            $stat['mode'] = $stat[2] = 33279; //0100000 + 0777;
            $stat['nlink'] = $stat[3] = 0;
            $stat['uid'] = $stat[4] = 0;
            $stat['gid'] = $stat[5] = 0;
            $stat['rdev'] = $stat[6] = 0;
            $stat['size'] = $stat[7] = $attr['length'];
            $stat['atime'] = $stat[8] = 0;
            $stat['mtime'] = $stat[9] = $attr['datetime'];
            $stat['ctime'] = $stat[10] = $attr['datetime'];
            $stat['blksize'] = $stat[11] = 0;
            $stat['blocks'] = $stat[12] = 0;
            return $stat;
        } else {
			//dir
			$stat = array();
			$stat['dev'] = $stat[0] = 0x8001;
            $stat['ino'] = $stat[1] = 0;
            $stat['mode'] = $stat[2] = 16895; //040000 + 0777;
            $stat['nlink'] = $stat[3] = 0;
            $stat['uid'] = $stat[4] = 0;
            $stat['gid'] = $stat[5] = 0;
            $stat['rdev'] = $stat[6] = 0;
            $stat['size'] = $stat[7] = 4096;
            $stat['atime'] = $stat[8] = 0;
            $stat['mtime'] = $stat[9] = 0;
            $stat['ctime'] = $stat[10] = 0;
            $stat['blksize'] = $stat[11] = 0;
            $stat['blocks'] = $stat[12] = 0;
            return $stat;
        }
    }
}

stream_wrapper_register( "dpstor", "DrupalStorageWrapper" );
//var_dump(file_exists('dpstor://drupal/tmp/skype.png'));exit;


function image_saegd_copy_tmp($file){
	$tmp = SAE_TMP_PATH.'/'.md5($file);
	if(!file_exists($tmp)){
		copy($file, $tmp);
	}
	return $tmp;
}

function image_saegd_tmpname($file){
	$tmp = SAE_TMP_PATH.'/'.md5($file);
	return $tmp;
}

/**
 * @file
 * GD2 toolkit for image manipulation within Drupal.
 */

/**
 * @ingroup image
 * @{
 */

/**
 * Retrieve settings for the GD2 toolkit.
 */
function image_saegd_settings() {
  if (image_saegd_check_settings()) {
    $form['status'] = array(
      '#markup' => t('The GD toolkit is installed and working properly.')
    );

    $form['image_jpeg_quality'] = array(
      '#type' => 'textfield',
      '#title' => t('JPEG quality'),
      '#description' => t('Define the image quality for JPEG manipulations. Ranges from 0 to 100. Higher values mean better image quality but bigger files.'),
      '#size' => 10,
      '#maxlength' => 3,
      '#default_value' => variable_get('image_jpeg_quality', 75),
      '#field_suffix' => t('%'),
    );
    $form['#element_validate'] = array('image_saegd_settings_validate');

    return $form;
  }
  else {
    form_set_error('image_toolkit', t('The GD image toolkit requires that the GD module for PHP be installed and configured properly. For more information see <a href="@url">PHP\'s image documentation</a>.', array('@url' => 'http://php.net/image')));
    return FALSE;
  }
}

/**
 * Validate the submitted GD settings.
 */
function image_saegd_settings_validate($form, &$form_state) {
  // Validate image quality range.
  $value = $form_state['values']['image_jpeg_quality'];
  if (!is_numeric($value) || $value < 0 || $value > 100) {
    form_set_error('image_jpeg_quality', t('JPEG quality must be a number between 0 and 100.'));
  }
}

/**
 * Verify GD2 settings (that the right version is actually installed).
 *
 * @return
 *   A boolean indicating if the GD toolkit is available on this machine.
 */
function image_saegd_check_settings() {
  if ($check = get_extension_funcs('gd')) {
    if (in_array('imagegd2', $check)) {
      // GD2 support is available.
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * Scale an image to the specified size using GD.
 *
 * @param $image
 *   An image object. The $image->resource, $image->info['width'], and
 *   $image->info['height'] values will be modified by this call.
 * @param $width
 *   The new width of the resized image, in pixels.
 * @param $height
 *   The new height of the resized image, in pixels.
 * @return
 *   TRUE or FALSE, based on success.
 *
 * @see image_resize()
 */
function image_saegd_resize(stdClass $image, $width, $height) {
  $res = image_saegd_create_tmp($image, $width, $height);

  if (!imagecopyresampled($res, $image->resource, 0, 0, 0, 0, $width, $height, $image->info['width'], $image->info['height'])) {
    return FALSE;
  }

  imagedestroy($image->resource);
  // Update image object.
  $image->resource = $res;
  $image->info['width'] = $width;
  $image->info['height'] = $height;
  return TRUE;
}

/**
 * Rotate an image the given number of degrees.
 *
 * @param $image
 *   An image object. The $image->resource, $image->info['width'], and
 *   $image->info['height'] values will be modified by this call.
 * @param $degrees
 *   The number of (clockwise) degrees to rotate the image.
 * @param $background
 *   An hexadecimal integer specifying the background color to use for the
 *   uncovered area of the image after the rotation. E.g. 0x000000 for black,
 *   0xff00ff for magenta, and 0xffffff for white. For images that support
 *   transparency, this will default to transparent. Otherwise it will
 *   be white.
 * @return
 *   TRUE or FALSE, based on success.
 *
 * @see image_rotate()
 */
function image_saegd_rotate(stdClass $image, $degrees, $background = NULL) {
  // PHP installations using non-bundled GD do not have imagerotate.
  if (!function_exists('imagerotate')) {
    watchdog('image', 'The image %file could not be rotated because the imagerotate() function is not available in this PHP installation.', array('%file' => $image->source));
    return FALSE;
  }

  $width = $image->info['width'];
  $height = $image->info['height'];

  // Convert the hexadecimal background value to a color index value.
  if (isset($background)) {
    $rgb = array();
    for ($i = 16; $i >= 0; $i -= 8) {
      $rgb[] = (($background >> $i) & 0xFF);
    }
    $background = imagecolorallocatealpha($image->resource, $rgb[0], $rgb[1], $rgb[2], 0);
  }
  // Set the background color as transparent if $background is NULL.
  else {
    // Get the current transparent color.
    $background = imagecolortransparent($image->resource);

    // If no transparent colors, use white.
    if ($background == 0) {
      $background = imagecolorallocatealpha($image->resource, 255, 255, 255, 0);
    }
  }

  // Images are assigned a new color palette when rotating, removing any
  // transparency flags. For GIF images, keep a record of the transparent color.
  if ($image->info['extension'] == 'gif') {
    $transparent_index = imagecolortransparent($image->resource);
    if ($transparent_index != 0) {
      $transparent_gif_color = imagecolorsforindex($image->resource, $transparent_index);
    }
  }

  $image->resource = imagerotate($image->resource, 360 - $degrees, $background);

  // GIFs need to reassign the transparent color after performing the rotate.
  if (isset($transparent_gif_color)) {
    $background = imagecolorexactalpha($image->resource, $transparent_gif_color['red'], $transparent_gif_color['green'], $transparent_gif_color['blue'], $transparent_gif_color['alpha']);
    imagecolortransparent($image->resource, $background);
  }

  $image->info['width'] = imagesx($image->resource);
  $image->info['height'] = imagesy($image->resource);
  return TRUE;
}

/**
 * Crop an image using the GD toolkit.
 *
 * @param $image
 *   An image object. The $image->resource, $image->info['width'], and
 *   $image->info['height'] values will be modified by this call.
 * @param $x
 *   The starting x offset at which to start the crop, in pixels.
 * @param $y
 *   The starting y offset at which to start the crop, in pixels.
 * @param $width
 *   The width of the cropped area, in pixels.
 * @param $height
 *   The height of the cropped area, in pixels.
 * @return
 *   TRUE or FALSE, based on success.
 *
 * @see image_crop()
 */
function image_saegd_crop(stdClass $image, $x, $y, $width, $height) {
  $res = image_saegd_create_tmp($image, $width, $height);

  if (!imagecopyresampled($res, $image->resource, 0, 0, $x, $y, $width, $height, $width, $height)) {
    return FALSE;
  }

  // Destroy the original image and return the modified image.
  imagedestroy($image->resource);
  $image->resource = $res;
  $image->info['width'] = $width;
  $image->info['height'] = $height;
  return TRUE;
}

/**
 * Convert an image resource to grayscale.
 *
 * Note that transparent GIFs loose transparency when desaturated.
 *
 * @param $image
 *   An image object. The $image->resource value will be modified by this call.
 * @return
 *   TRUE or FALSE, based on success.
 *
 * @see image_desaturate()
 */
function image_saegd_desaturate(stdClass $image) {
  // PHP installations using non-bundled GD do not have imagefilter.
  if (!function_exists('imagefilter')) {
    watchdog('image', 'The image %file could not be desaturated because the imagefilter() function is not available in this PHP installation.', array('%file' => $image->source));
    return FALSE;
  }

  return imagefilter($image->resource, IMG_FILTER_GRAYSCALE);
}

/**
 * GD helper function to create an image resource from a file.
 *
 * @param $image
 *   An image object. The $image->resource value will populated by this call.
 * @return
 *   TRUE or FALSE, based on success.
 *
 * @see image_load()
 */
function image_saegd_load(stdClass $image) {
  $extension = str_replace('jpg', 'jpeg', $image->info['extension']);
  $function = 'imagecreatefrom' . $extension;

  $tmp = image_saegd_copy_tmp($image->source);
  return (function_exists($function) && $image->resource = $function($tmp));
}

/**
 * GD helper to write an image resource to a destination file.
 *
 * @param $image
 *   An image object.
 * @param $destination
 *   A string file URI or path where the image should be saved.
 * @return
 *   TRUE or FALSE, based on success.
 *
 * @see image_save()
 */
function image_saegd_save(stdClass $image, $destination) {
  $scheme = file_uri_scheme($destination);
  // Work around lack of stream wrapper support in imagejpeg() and imagepng().
  if ($scheme && file_stream_wrapper_valid_scheme($scheme)) {
    // If destination is not local, save image to temporary local file.
    $local_wrappers = file_get_stream_wrappers(STREAM_WRAPPERS_LOCAL);
    if (!isset($local_wrappers[$scheme])) {
      $permanent_destination = $destination;
      $destination = drupal_tempnam('temporary://', 'gd_');
    }
    // Convert stream wrapper URI to normal path.
    $destination = drupal_realpath($destination);
  }

  $extension = str_replace('jpg', 'jpeg', $image->info['extension']);
  $function = 'image' . $extension;
  if (!function_exists($function)) {
    return FALSE;
  }
  $tmp = image_saegd_tmpname($destination);
  if ($extension == 'jpeg') {
    $success = $function($image->resource, $tmp, variable_get('image_jpeg_quality', 75));
  }
  else {
    // Always save PNG images with full transparency.
    if ($extension == 'png') {
      imagealphablending($image->resource, FALSE);
      imagesavealpha($image->resource, TRUE);
    }
    $success = $function($image->resource, $tmp);
  }
  if($success){
	  $success = copy($tmp, $destination);var_dump($tmp);var_dump($destination);
  }
  // Move temporary local file to remote destination.
  if (isset($permanent_destination) && $success) {
    return (bool) file_unmanaged_move($tmp, $permanent_destination, FILE_EXISTS_REPLACE);
  }
  return $success;
}

/**
 * Create a truecolor image preserving transparency from a provided image.
 *
 * @param $image
 *   An image object.
 * @param $width
 *   The new width of the new image, in pixels.
 * @param $height
 *   The new height of the new image, in pixels.
 * @return
 *   A GD image handle.
 */
function image_saegd_create_tmp(stdClass $image, $width, $height) {
  $res = imagecreatetruecolor($width, $height);

  if ($image->info['extension'] == 'gif') {
    // Grab transparent color index from image resource.
    $transparent = imagecolortransparent($image->resource);

    if ($transparent >= 0) {
      // The original must have a transparent color, allocate to the new image.
      $transparent_color = imagecolorsforindex($image->resource, $transparent);
      $transparent = imagecolorallocate($res, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);

      // Flood with our new transparent color.
      imagefill($res, 0, 0, $transparent);
      imagecolortransparent($res, $transparent);
    }
  }
  elseif ($image->info['extension'] == 'png') {
    imagealphablending($res, FALSE);
    $transparency = imagecolorallocatealpha($res, 0, 0, 0, 127);
    imagefill($res, 0, 0, $transparency);
    imagealphablending($res, TRUE);
    imagesavealpha($res, TRUE);
  }
  else {
    imagefill($res, 0, 0, imagecolorallocate($res, 255, 255, 255));
  }

  return $res;
}

/**
 * Get details about an image.
 *
 * @param $image
 *   An image object.
 * @return
 *   FALSE, if the file could not be found or is not an image. Otherwise, a
 *   keyed array containing information about the image:
 *   - "width": Width, in pixels.
 *   - "height": Height, in pixels.
 *   - "extension": Commonly used file extension for the image.
 *   - "mime_type": MIME type ('image/jpeg', 'image/gif', 'image/png').
 *
 * @see image_get_info()
 */
function image_saegd_get_info(stdClass $image) {
  $details = FALSE;
  $tmp = image_saegd_copy_tmp($image->source);
  $data = getimagesize($tmp);

  if (isset($data) && is_array($data)) {
    $extensions = array('1' => 'gif', '2' => 'jpg', '3' => 'png');
    $extension = isset($extensions[$data[2]]) ?  $extensions[$data[2]] : '';
    $details = array(
      'width'     => $data[0],
      'height'    => $data[1],
      'extension' => $extension,
      'mime_type' => $data['mime'],
    );
  }

  return $details;
}

/**
 * @} End of "ingroup image".
 */
