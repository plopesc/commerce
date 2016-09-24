<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\Entity\ProductInterface;

/**
 * Interface ProductVariationRenderDefaultInterface.
 *
 * @package Drupal\commerce_product
 */
interface ProductVariationRendererInterface {


  /**
   * Whether this product variation renderer should be used to build the view.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface
   *   The current product.
   * @param string
   *   The product view mode.
   *
   * @return bool
   *   TRUE if this builder should be used or FALSE to let other renderers
   *   decide.
   */
  public function applies(ProductInterface $product, $view_mode);

  /**
   * Builds the product variation render array.
   *
   * @param array $build
   *   The product render array.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface
   *   The current product.
   * @param string
   *   The product view mode.
   */
  public function build(array &$build, ProductInterface $product, $view_mode);

}
