<?php

namespace Drupal\api_wrapper\Attribute;
use Attribute;

#[Attribute]
class Endpoint {

  private ?string $description;

  private ?string $label;

  private string $method;

  private string $path;

  public function __construct(string $method, string $path, ?string $label = NULL, ?string $description = NULL) {
    $this->method = $method;
    $this->path = $path;
    $this->label = $label;
    $this->description = $description;
  }

}
