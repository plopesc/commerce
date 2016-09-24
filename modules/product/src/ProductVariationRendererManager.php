<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\Entity\ProductInterface;

/**
 * Class ProductVariationRenderManager.
 *
 * @package Drupal\commerce_product
 */
class ProductVariationRendererManager implements ProductVariationRendererManagerInterface {

  /**
   * Holds arrays of product variation renders, keyed by priority.
   *
   * @var array
   */
  protected $renders = [];

  /**
   * Holds the array of breadcrumb builders sorted by priority.
   *
   * Set to NULL if the array needs to be re-calculated.
   *
   * @var \Drupal\commerce_product\ProductVariationRendererInterface[]|null
   */
  protected $sortedRenders;

  /**
   * {@inheritdoc}
   */
  public function addRenderer(ProductVariationRendererInterface $renderer, $priority) {
    $this->renders[$priority][] = $renderer;
    // Force the builders to be re-sorted.
    $this->sortedRenders = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(ProductInterface $product, $view_mode) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array &$build, ProductInterface $product, $view_mode) {
    // Call the build method of registered product variation renderers,
    // until one of them returns an array.
    foreach ($this->getSortedRenderers() as $builder) {
      if (!$builder->applies($product, $view_mode)) {
        // The builder does not apply, so we continue with the other builders.
        continue;
      }

      $builder->build($build, $product, $view_mode);
    }

  }

  /**
   * Returns the sorted array of breadcrumb builders.
   *
   * @return \Drupal\commerce_product\ProductVariationRendererInterface[]
   *   An array of breadcrumb builder objects.
   */
  protected function getSortedRenderers() {
    if (!isset($this->sortedRenders)) {
      // Sort the builders according to priority.
      krsort($this->renders);
      // Merge nested builders from $this->builders into $this->sortedBuilders.
      $this->sortedRenders = [];
      foreach ($this->renders as $builders) {
        $this->sortedRenders = array_merge($this->sortedRenders, $builders);
      }
    }
    return $this->sortedRenders;
  }

}
