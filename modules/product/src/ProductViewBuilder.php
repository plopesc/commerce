<?php

namespace Drupal\commerce_product;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProductViewBuilder extends EntityViewBuilder {

  /**
   * The product field variation renderer
   *
   * @var \Drupal\commerce_product\ProductVariationFieldRenderer
   */
  protected $productVariationFieldRenderer;

  /**
   * Constructs a new BlockViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\commerce_product\ProductVariationFieldRenderer $product_variation_field_renderer
   *   The product variation field renderer
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, ProductVariationFieldRenderer $product_variation_field_renderer) {
    parent::__construct($entity_type, $entity_manager, $language_manager);
    $this->productVariationFieldRenderer = $product_variation_field_renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('commerce_product.variation_field_renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildMultiple(array $build_list) {
    // Build the view modes and display objects.
    $view_modes = array();
    $entity_type_key = "#{$this->entityTypeId}";
    $view_hook = "{$this->entityTypeId}_view";

    // Find the keys for the ContentEntities in the build; Store entities for
    // rendering by view_mode.
    $children = Element::children($build_list);
    foreach ($children as $key) {
      if (isset($build_list[$key][$entity_type_key])) {
        $entity = $build_list[$key][$entity_type_key];
        if ($entity instanceof FieldableEntityInterface) {
          $view_modes[$build_list[$key]['#view_mode']][$key] = $entity;
        }
      }
    }

    // Build content for the displays represented by the entities.
    foreach ($view_modes as $view_mode => $view_mode_entities) {
      $displays = EntityViewDisplay::collectRenderDisplays($view_mode_entities, $view_mode);
      $this->buildComponents($build_list, $view_mode_entities, $displays, $view_mode);
      foreach (array_keys($view_mode_entities) as $key) {
        // Allow for alterations while building, before rendering.
        $entity = $build_list[$key][$entity_type_key];
        $display = $displays[$entity->bundle()];

        // Add product variation fields to the render array
        $this->addProductVariationFields($build_list[$key], $entity, $view_mode, $key);

        $this->moduleHandler()->invokeAll($view_hook, [&$build_list[$key], $entity, $display, $view_mode]);
        $this->moduleHandler()->invokeAll('entity_view', [&$build_list[$key], $entity, $display, $view_mode]);

        $this->alterBuild($build_list[$key], $entity, $display, $view_mode);

        // Assign the weights configured in the display.
        // @todo: Once https://www.drupal.org/node/1875974 provides the missing
        //   API, only do it for 'extra fields', since other components have
        //   been taken care of in EntityViewDisplay::buildMultiple().
        foreach ($display->getComponents() as $name => $options) {
          if (isset($build_list[$key][$name])) {
            $build_list[$key][$name]['#weight'] = $options['weight'];
          }
        }

        // Allow modules to modify the render array.
        $this->moduleHandler()->alter(array($view_hook, 'entity_view'), $build_list[$key], $entity, $display);
      }
    }

    return $build_list;
  }

  /**
   * Adds the product variation fields to the product render array.
   *
   * @param array $build
   *   The entity render array.
   * @param $product
   *   The product being rendered.
   * @param $view_mode
   *   The view mode used to render the product.
   */
  protected function addProductVariationFields(array &$build, $product, $view_mode) {
    /** @var \Drupal\commerce_product\Entity\ProductTypeInterface $product_type */
    $product_type = $this->entityManager->getStorage('commerce_product_type')
      ->load($product->bundle());
    if ($product_type->shouldInjectVariationFields() && $product->getDefaultVariation()) {

      $variation = $this->entityManager->getStorage('commerce_product_variation')
        ->loadFromContext($product);
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