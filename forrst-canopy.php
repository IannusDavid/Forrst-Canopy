<?php

class ForrstCanopyException extends Exception {
  public $authed, $authed_as;
  
  function __construct($data, $code = 0) {
    $this->authed = $data['authed'];
    $this->authed_as = $data['authed_as'];
    parent::__construct($data['message'], $code);
  }
    
  public function __toString() {
    $style = 'style="font-family: Helvetica;"';
    $info =  "<p $style>Something went wrong with the last request to the Forrst API</p>";
    $info .= "<h2 $style>Details</h2><strong $style>Message</strong>: " . $this->message . '<br />';
    $info .= "<p $style><strong>File:</strong> " . $this->file . '</p>';
    $info .= "<p $style><strong>Line:</strong> " . $this->line . '</p>';
    $info .= "<h2 $style>Trace</h2>";
    
    $info .= '<pre style="background: #eee; padding: 5px;">' . print_r($this->getTrace(), 1) . '</pre>';
    
    return $info;
  }
  
}

// Handles all of the url requests
class ForrstCanopyCurl {
  private static $curl = null; // CURL instance
  private static $response = null; // The returned data from CURL
  
  const API_BASE = 'http://forrst.com/api/v2/';
  
  public static function getJSON($url) {
    self::$curl = curl_init(self::checkURL($url));
    self::setOptions();
    
    self::$response = json_decode(curl_exec(self::$curl));
    curl_close(self::$curl);
        
    // Make sure everything was okay
    if( self::$response->stat != 'ok' )
      return false;
    
    return self::$response;
  }
  
  /**
   * Set default options for CURL
   *
   * @return void
   * @author Baylor Rae'
   */
  private function setOptions() {
    if( !empty(self::$curl) ) {
      curl_setopt_array(self::$curl, array(
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_USERAGENT => 'Forrst Canopy'
        ));
    }
  }
  
  /**
   * Check to see if we need to prepend the API_BASE url
   *
   * @param string $url 
   * @return void
   * @author Baylor Rae'
   */
  private static function checkURL($url) {
    return (preg_match('/^http(s)?:\/\//', $url)) ? $url : self::API_BASE . $url;
  }
  
  /**
   * Returns information about the response
   *
   * @return array
   * @author Baylor Rae'
   */
  public function exceptionData() {
    return array(
      'message' => self::$response->resp->error,
      'authed' => self::$response->authed,
      'authed_as' => self::$response->authed_as
    );
  }
}

class ForrstCanopyUsers {
  private $authed = false;
  
  // TODO: Add authentication when it's live
  function __construct($email_or_username, $password) {
    if( ForrstCanopyCurl::getJSON(sprintf('users/auth?email_or_username=%s&password=%s', $email_or_username, $password)) ) {
      $this->authed = true;
    }else {
      throw new ForrstCanopyException(ForrstCanopyCurl::exceptionData());
    }
  }
  
  /**
   * Returns information about a given user
   *
   * @param mixed $username_or_id 
   * @return (object) with information about the user
   * @author Baylor Rae'
   * @see http://forrst.com/api#m-users-info
   */
  public static function info($username_or_id) {
    $means = (is_string($username_or_id)) ? 'username' : 'id';
    $info = ForrstCanopyCurl::getJSON(sprintf('users/info?%s=%s', $means, $username_or_id));
    
    return $info;
  }
  
  /**
   * Get all posts from a given user
   *
   * @param mixed $username_or_id 
   * @param string $params (optional)
   * @return (array) with all posts
   * @author Baylor Rae'
   * @see http://forrst.com/api#m-user-posts
   */
  public static function posts($username_or_id, $params = null) {
    $means = (is_string($username_or_id)) ? 'username' : 'id';
    $url = sprintf('users/posts?%s=%s', $means, $username_or_id);
    
    if( $params !== null ) {
      foreach ($params as $k => $v) {
        //TODO Error / Type checking
        $url .= "&$k=$v";
      }
    }
    
    return ForrstCanopyCurl::getJSON($url);
  }
    
}
class_alias('ForrstCanopyUsers', 'ForrstCanopyUser');

class ForrstCanopyPosts {
  
  /**
   * Get data about a single post.
   *  Note: 
   *  - Questions: content is the question.
   *  - Code: content contains the code snippet.
   *  - For code, snaps, and links, description is the post description; it is not used for questions.
   *
   * @param mixed $id use string for tiny_id
   * @return (object) information about a specific post
   * @author Baylor Rae'
   * @see http://forrst.com/api#m-posts-show
   */
  public static function show($id) {
    $means = (is_string($id)) ? 'tiny_id' : 'id';
    return ForrstCanopyCurl::getJSON(sprintf('posts/show?%s=%s', $means, $id));
  }
  
  /**
   * Gets all posts in reverse chronological order
   *
   * @param int $after (optional)
   * @return (array) of all recent posts
   * @author Baylor Rae'
   * @see http://forrst.com/api#m-posts-all
   */
  public static function all($after = null) {
    $url = 'posts/all';
    
    if( $after !== null )
      $url .= sprintf('?after=%s', $after);
      
    return ForrstCanopyCurl::getJSON($url);
  }
  
  /**
   * Gets all posts of a specific type
   *
   * @param string $post_type ['code', 'link', 'question', 'snap']
   * @param string $params (optional)
   * @return void
   * @author Baylor Rae'
   * @see http://forrst.com/api#m-posts-list
   */
  public static function _list($post_type, $params = null) {
    $url = sprintf('posts/list?post_type=%s', $post_type);
        
    if( $params !== null ) {
      foreach ($params as $k => $v) {
        //TODO Error / Type checking
        $url .= "&$k=$v";
      }
    }
      
    return ForrstCanopyCurl::getJSON($url);
  }
  
}
class_alias('ForrstCanopyPosts', 'ForrstCanopyPost');

/**
 * Returns all API status
 *
 * @return (object)
 * @author Baylor Rae'
 * @see http://forrst.com/api#m-stats
 */
function ForrstCanopy_stats() {
  return ForrstCanopyCurl::getJSON('stats');
}
