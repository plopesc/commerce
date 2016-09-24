<?php

namespace Drupal\commerce_product;

/**
 * Interface ProductVariationRenderManagerInterface.
 *
 * @package Drupal\commerce_product
 */
interface ProductVariationRendererManagerInterface {

  /**
   * Adds another breadcrumb builder.
   *
   * @param \Drupal\commerce_product\ProductVariationRendererInterface $renderer
   *   The product variation renderer to add.
   * @param int $priority
   *   Priority of the product variation renderer.
   */
  public function addRenderer(ProductVariationRendererInterface $renderer, $priority);

}
