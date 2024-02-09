<?php

namespace Drupal\api_wrapper\Attribute;

use Attribute;

#[Attribute]
class ApiWrap {
  private string $basePath;

  public function __construct(string $basePath) {
    $this->basePath = $basePath;
  }

}
