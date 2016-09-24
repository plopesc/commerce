<?php

namespace Drupal\commerce_product;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class ProductVariationRenderDefault.
 *
 * @package Drupal\commerce_product
 */
class ProductVariationRendererDefault implements ProductVariationRendererInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\commerce_product\ProductVariationFieldRenderer
   */
  protected $productVariationFieldRenderer;

  /**
   * Constructs a new ProductVariationFieldRenderer object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_product\ProductVariationFieldRenderer $product_variation_field_renderer
   *   The product variation field renderer
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ProductVariationFieldRenderer $product_variation_field_renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->productVariationFieldRenderer = $product_variation_field_renderer;
  }


  /**
   * {@inheritdoc}
   */
  public function applies(ProductInterface $product, $view_mode) {
    return TRUE;
  }

  public function build(array &$build, ProductInterface $product, $view_mode) {
    /** @var \Drupal\commerce_product\Entity\ProductTypeInterface $product_type */
    $product_type = $this->entityTypeManager->getStorage('commerce_product_type')->load($product->bundle());
    if ($product_type->shouldInjectVariationFields() && $product->getDefaultVariation()) {

      $variation = $this->entityTypeManager->getStorage('commerce_product_variation')->loadFromContext($product);
      $rendered_fields = $this->productVariationFieldRenderer->renderFields($variation, $view_mode);
      foreach ($rendered_fields as $field_name => $rendered_field) {
        // Group attribute fields to allow them to be excluded together.
        if (strpos($field_name, 'attribute_') !== FALSE) {
          $build['variation_attributes']['variation_' . $field_name] = $rendered_field;
        }
        else {
          $build['variation_' . $field_name] = $rendered_field;
        }
      }
    }
  }

}
