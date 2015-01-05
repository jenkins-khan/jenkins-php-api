<?php
namespace JenkinsApi\Jenkins;

use JenkinsApi\Jenkins;

/**
 * Factory class for Jenkins
 *
 * @package    JenkinsApi\Jenkins
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @version    $Id$
 */
class Factory
{
  /**
   * @param string $url
   * 
   * @return Jenkins
   */
  public function build($url)
  {
    return new Jenkins($url);
  }
}
