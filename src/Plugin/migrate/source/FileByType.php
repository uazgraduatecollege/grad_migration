<?php

namespace Drupal\grad_migration\Plugin\migrate\source;

use Drupal\file\Plugin\migrate\source\d7\File;

/**
 * Drupal 7 file source (optionally filtered by type) from database.
 *
 * @MigrateSource(
 *   id = "d7_file_by_type",
 *   source_module = "file"
 * )
 */
class FileByType extends File
{

  /**
   * {@inheritdoc}
   */
    public function query()
    {
        $query = parent::query();
        // Filter by file type, if configured.
        if (isset($this->configuration['type'])) {
            $query->condition('f.filemime', $this->configuration['type'], 'IN');
        }

        // Get the alt text, if configured.
        if (isset($this->configuration['get_alt'])) {
            $alt_alias = $query->addJoin('left', 'field_data_field_images', 'alt', 'f.fid = %alias.field_images_fid');
            $query->addField($alt_alias, 'field_images_alt', 'alt');

            $alt_alias_singular = $query->addJoin('left', 'field_data_field_image', 'alt_singular', 'f.fid = %alias.field_image_fid');
            $query->addField($alt_alias_singular, 'field_image_alt', 'alt_singular');
        }

        // Get the title text, if configured.
        if (isset($this->configuration['get_title'])) {
            $title_alias = $query->addJoin('left', 'field_data_field_images', 'title', 'f.fid = %alias.field_images_fid');
            $query->addField($title_alias, 'field_images_title', 'title');

            $title_alias_singular = $query->addJoin('left', 'field_data_field_image', 'title_singular', 'f.fid = %alias.field_image_fid');
            $query->addField($title_alias_singular, 'field_image_title', 'title_singular');
        }

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function fields()
    {
        $fields = parent::fields();
        $fields['type'] = $this->t('The type of file.');
        $fields['alt'] = $this->t('Alt text of the file (if present)');
        $fields['alt_singular'] = $this->t('Alt text of the file (if present)');
        $fields['title'] = $this->t('Title text of the file (if present)');
        $fields['title_singular'] = $this->t('Title text of the file (if present)');
        return $fields;
    }
}
