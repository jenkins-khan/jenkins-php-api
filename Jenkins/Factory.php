<?php
 
class Jenkins_Factory 
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
