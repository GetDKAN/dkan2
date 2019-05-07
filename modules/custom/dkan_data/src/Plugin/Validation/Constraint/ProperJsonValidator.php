<?php

namespace Drupal\dkan_data\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 *
 */
class ProperJsonValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    foreach ($items as $item) {
      $info = $this->isProper($item->value);
      if (!$info['valid']) {
        $this->addViolations($info['errors']);
      }
    }
  }

  /**
   * Is proper JSON?
   *
   * @param string $value
   */
  private function isProper($value) {
    /** @var $controller \Drupal\dkan_api\Controller\Dataset **/
    $controller = \Drupal::service("dkan_api.controller.dataset");
    $engine = $controller->getEngine();
    return $engine->validate($value);
  }

  /**
   * Add Violations.
   */
  private function addViolations($errors) {
    foreach ($errors as $error) {
      $this->context->addViolation($error['message']);
    }
  }

}
