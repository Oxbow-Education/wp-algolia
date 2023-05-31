<?php


// Add the settings page
function algolia_sync_plugin_settings_page()
{
  add_options_page(
    'Algolia Sync Plugin Settings',
    'Algolia Sync',
    'manage_options',
    'algolia_sync_plugin',
    'algolia_sync_plugin_render_settings_page'
  );
}
add_action('admin_menu', 'algolia_sync_plugin_settings_page');

// Render the settings page
function algolia_sync_plugin_render_settings_page()
{
?>
  <div class="wrap">
    <h1>Algolia Sync Plugin Settings</h1>
    <form method="post" action="options.php">
      <?php settings_fields('algolia_sync_plugin_settings'); ?>
      <?php do_settings_sections('algolia_sync_plugin'); ?>
      <?php submit_button(); ?>
    </form>
  </div>
<?php
}

// Register settings
function algolia_sync_plugin_register_settings()
{
  register_setting('algolia_sync_plugin_settings', 'algolia_sync_plugin_api_key');
  register_setting('algolia_sync_plugin_settings', 'algolia_sync_plugin_app_id');
  register_setting('algolia_sync_plugin_settings', 'algolia_sync_plugin_post_types');
  register_setting('algolia_sync_plugin_settings', 'algolia_sync_plugin_index');
}
add_action('admin_init', 'algolia_sync_plugin_register_settings');

// Add settings sections and fields
function algolia_sync_plugin_render_settings_fields()
{
  add_settings_section(
    'algolia_sync_plugin_section',
    'Algolia Settings',
    'algolia_sync_plugin_section_callback',
    'algolia_sync_plugin'
  );

  add_settings_field(
    'algolia_sync_plugin_api_key',
    'Algolia API Key',
    'algolia_sync_plugin_api_key_callback',
    'algolia_sync_plugin',
    'algolia_sync_plugin_section'
  );

  add_settings_field(
    'algolia_sync_plugin_app_id',
    'Algolia App ID',
    'algolia_sync_plugin_app_id_callback',
    'algolia_sync_plugin',
    'algolia_sync_plugin_section'
  );

  add_settings_field(
    'algolia_sync_plugin_post_types',
    'Post Types to Sync',
    'algolia_sync_plugin_post_types_callback',
    'algolia_sync_plugin',
    'algolia_sync_plugin_section'
  );

  add_settings_field(
    'algolia_sync_plugin_index',
    'Algolia Index',
    'algolia_sync_plugin_index_callback',
    'algolia_sync_plugin',
    'algolia_sync_plugin_section'
  );
}
add_action('admin_init', 'algolia_sync_plugin_render_settings_fields');

// Callback functions for rendering settings fields
function algolia_sync_plugin_section_callback()
{
  echo '<p>Configure Algolia API and synchronization settings.</p>';
}

function algolia_sync_plugin_api_key_callback()
{
  $api_key = get_option('algolia_sync_plugin_api_key');
  echo '<input type="text" name="algolia_sync_plugin_api_key" value="' . esc_attr($api_key) . '" />';
}

function algolia_sync_plugin_app_id_callback()
{
  $app_id = get_option('algolia_sync_plugin_app_id');
  echo '<input type="text" name="algolia_sync_plugin_app_id" value="' . esc_attr($app_id) . '" />';
}

function algolia_sync_plugin_post_types_callback()
{
  $post_types = get_option('algolia_sync_plugin_post_types');
  $all_post_types = get_post_types();
  foreach ($all_post_types as $post_type) {
    $checked = in_array($post_type, $post_types) ? 'checked' : '';
    echo '<label><input type="checkbox" name="algolia_sync_plugin_post_types[]" value="' . esc_attr($post_type) . '" ' . $checked . ' /> ' . esc_html($post_type) . '</label><br>';
  }
}

function algolia_sync_plugin_index_callback()
{
  $index = get_option('algolia_sync_plugin_index');
  echo '<input type="text" name="algolia_sync_plugin_index" value="' . esc_attr($index) . '" />';
}


// Sync posts with Algolia on publish
function delete_object_from_algolia($post_id, $index)
{
  global $algolia;
  $index = $algolia->initIndex($index);
  $index->deleteObject($post_id);
}


function algolia_sync_plugin_sync_on_publish($post_id)
{
  // Check if sync is enabled for the post type
  $post_types = get_option('algolia_sync_plugin_post_types');
  $post_type = get_post_type($post_id);
  if (!in_array($post_type, $post_types)) {
    return;
  }

  // Sync post with Algolia
  $algolia_api_key = get_option('algolia_sync_plugin_api_key');
  $algolia_app_id = get_option('algolia_sync_plugin_app_id');
  $algolia_index = get_option('algolia_sync_plugin_index');

  // Perform the synchronization with Algolia using the Algolia API
  // Replace this code with your own logic to sync the post with Algolia

  // Example code using the Algolia PHP SDK
  require_once 'path/to/algolia-php-sdk/autoload.php';
  $client = Algolia\AlgoliaSearch\SearchClient::create($algolia_app_id, $algolia_api_key);
  $index = $client->initIndex($algolia_index);


  $post = get_post($post_id);
  $post_status = $post->post_status;
  if ($post_status == 'publish') {
    $record = new stdClass();
    $record->objectID = $post_id;
    $record->title = $post->post_title;
    $record->content = $post->post_content;
    $post_metas = get_post_custom($post_id);
    foreach ($post_metas as $key => $values) {
      $post_meta_obj->$key = count($values) == 1 ? $values[0] : $values;
    }
    $index->saveObject($record);
  } else {;
    delete_object_from_algolia($post_id, $algolia_index);
  }
}
add_action('save_post', 'algolia_sync_plugin_sync_on_publish');
