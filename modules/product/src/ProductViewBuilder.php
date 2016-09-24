<?php

namespace Drupal\commerce_product;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Render\Element;

class ProductViewBuilder extends EntityViewBuilder {

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

        \Drupal::getContainer()->get('commerce_product.product_variation_render')->build($build_list[$key], $entity, $view_mode);

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

}