<?php

/**
 * Plugin Name: Killerapps Settings
 * Description:  Custom settings pages for WordPress
 * Version: 1.0
 * Author: Zosia Sobocinska
 * Author URI: http://www.killeapps.pl
 * License: GPLv2 or later
 */

class KillerappsCreateSettingsPage {

  private $title;
  private $group;
  private $option_name;
  private $sections;
  private $capability;
  private $fields;

  public function KillerappsCreateSettingsPage($title, $group, $option_name, $sections, $capability) {
    $this->title = $title;
    $this->group = $group;
    $this->option_name = $option_name;
    $this->sections = $sections;
    $this->capability = $capability;
    $this->fields = array();

    foreach ($this->sections as $section) {
      foreach ($section['fields'] as $field) {
        $settingsField = new KillerappsCreateSettingsField($field["id"], $field["title"], $field["description"], $field["type"], $field['default'], $field['options'], $this->group, $this->option_name);
        $this->fields[$field["id"]] = $settingsField;
      }
    }


    add_action('admin_menu', array($this, 'add_plugin_page'));
    add_action('admin_init', array($this, 'page_init'));
  }

  public function add_plugin_page() {
    add_options_page(
            $this->title, $this->title, $this->capability, $this->group, array($this, 'create_admin_page')
    );
  }

  public function create_admin_page() {
    // Set class property
    $this->options = get_option($this->option_name);
    ?>
    <div class="wrap">
      <?php screen_icon(); ?>
      <h2><?php echo $this->title ?></h2>
      <form method="post" action="options.php">
        <?php
        // This prints out all hidden setting fields
        settings_fields($this->group);
        do_settings_sections($this->group);
        submit_button();
        ?>
      </form>
    </div>
    <?php
  }

  public function page_init() {
    register_setting(
            $this->group, $this->option_name, array($this, 'sanitize')
    );

    foreach ($this->sections as $section) {

      add_settings_section(
              $section['id'], $section['title'], NULL, // Callback
              $this->group
      );
    }
    foreach ($this->fields as $field) {
      $field->add_to_section($section["id"]);
    }
  }

  public function sanitize($input) {
    $new_input = array();
    foreach ($this->fields as $field) {
      try {
        $id = $field->id;
        if (isset($input[$id])) {
          $new_input[$id] = $field->sanitize($input[$id]);
        }
      } catch (Exception $e) {
        
      }
    }
    return $new_input;
  }

  public function get_field($field_id) {
    return $this->fields[$field_id];
  }

}

class KillerappsCreateSettingsField {

  public $id;
  private $title;
  private $description;
  private $type;
  private $default;
  private $options;
  private $group;
  private $option_name;

  function KillerappsCreateSettingsField($id, $title, $description, $type, $default, $options, $group, $option_name) {
    $this->id = $id;
    $this->title = $title;
    $this->description = $description;
    $this->type = $type;
    $this->default = $default;
    $this->options = $options;
    $this->group = $group;
    $this->option_name = $option_name;
  }

  function add_to_section($section_id) {
    add_settings_field(
            $this->id, $this->title, array($this, 'callback'), $this->group, $section_id
    );
  }

  function get_value() {
    $current_option = get_option($this->option_name);
    $value = $current_option[$this->id];
    return $value;
  }

  function callback() {
    $value = $this->get_value();
    if ($value === NULL && $this->default) {
      update_option($this->id, $this->default);
      $value = $this->default;
    }
    if ($this->description) {
      echo "<p class='setting-field-description'>" . $this->description . "</p>";
    }
    echo "<p class='setting-field setting-field-{$this->type}'>";
    switch ($this->type) {
      case "image":
      case "gallery":
        $limit = 1;
        if ($this->type == 'gallery') {
          $limit = 'false';
          // @todo test if limit is set
        }
        ?>
        <div class="image-setting">
          <?php echo "<input type='hidden' id='{$this->id}' name='{$this->option_name}[{$this->id}]' value='{$value}'/>"; ?>
          <input type="button" value="Select Image" class="killerapps-settings-image-select button" data-killerapps-limit="<?php echo $limit ?>"/>
          <?php if ($value): ?>
            <span class="killerapps-settings-image-container" data-killerapps-settings-image-id="<?php echo $value ?>"><?php echo wp_get_attachment_image($value) ?><input type="button" value="x" class="killerapps-settings-image-detach button"/></span>
          <?php endif; ?>
          <br/><br/>
        </div>
        <div class="clear"></div>
        <?php
        break;
      case "checkbox_list":
        echo "checkbox list not implemented";
        break;
      case "posts":
      case "radio":
        ?>
        <ul class="killerapps-radio-list">
          <?php
          if ($this->type == 'posts') {
          	$this->type = 'radio';
          	$options = array();
            $post_query = new WP_Query($this->options['query_args']);
            while ($post_query->have_posts()) {
              $post_query->the_post();
              $label = get_the_title();
              if ($this->options['link']) {
                $permalink = get_permalink();
                $label = "<a href='{$permalink}'>{$label}</a>";
              }
              $options[get_the_ID()] = $label;
            }
            $type = $this->type;
          } else {
            $options = $this->options;
            $type = $this->type;
          }
          if ($type == "checkbox_list") {
            $type = "checkbox";
          }
          $name = "{$this->option_name}[{$this->id}]";

          foreach ($options as $option_value => $option_label) {
            $id = "{$name}[{$option_value}]";
            if ($type == "radio" && $value == $option_value) {
              $checked = "checked";
            } else {
              $checked = '';
            }
            echo "<li><input type='{$type}' name='{$name}' id='{$id}' value='{$option_value}' {$checked}/><label for='{$id}'>{$option_label}</label></li>";
          }
          ?></ul><?php
          break;
        case "editor":
			wp_editor(html_entity_decode(stripcslashes($value)), "{$this->option_name}[{$this->id}]");
            break;
        case "textarea":
			echo "<textarea id='{$this->id}' name='{$this->option_name}[{$this->id}]' class='killerapps-settings-{$this->type}'>{$value}</textarea>";
          break;
        default:
          $more_data = '';
          switch ($this->type) {
            case 'number':
              if ($this->options && $this->options['step']) {
                $more_data .= ' step="' . $this->options['step'] . '"';
              }
              break;
            case 'range':
              if ($this->options['step'])
                $more_data .= ' step="' . $this->options['step'] . '"';
              if ($this->options['max'])
                $more_data .= " max={$this->options['max']}";
              if ($this->options['min'])
                $more_data .= " min={$this->options['min']}";
              break;
          }
          if ($this->options['min'] || $this->options['min'] == 0)
            echo "<span class='killerapps-settings-range-min'>{$this->options['min']}</span>";
          echo "<input type='{$this->type}' id='{$this->id}' name='{$this->option_name}[{$this->id}]' value='{$value}' class='killerapps-settings-{$this->type}' {$more_data} />";
          if ($this->options['max'])
            echo "<span class='killerapps-settings-range-max'>{$this->options['max']}</span>";
          if ($this->type == "range") {
            echo "<span class='killerapps-settings-range-value'>{$value}</span>";
          }
          if ($this->type == "url") {
            echo " <a href='{$value}'>click</a>";
          }
      }
      echo "</p>";
    }

    function sanitize($value) {
    	return killerapps_sanitize($value, $this->type);
    }

  }

  function killerapps_settings_admin_enqueue() {
    wp_enqueue_media();
    wp_enqueue_style('killerapps-settings', plugins_url('/css/settings.css', __FILE__));
    wp_enqueue_script('json', plugins_url('/js/json.js', __FILE__), array('jquery'));
    wp_enqueue_script('killerapps-settings', plugins_url('/js/settings.js', __FILE__), array('jquery', 'json'));
  }

  function killerapps_settings_init() {
    do_action('killerapps-settings-init');
  }

  if (is_admin()) {
    add_action('admin_enqueue_scripts', 'killerapps_settings_admin_enqueue');
  }
  add_action('plugins_loaded', 'killerapps_settings_init');
  if (isset($_POST['option_page'])) {
  	foreach ($_POST as $key => $options) {
      if ($key[0] != "_" && !in_array($key, array("option_page", "action", "submit"))) {
      	foreach ($options as $option => $value) {
      		$value = strip_tags($value, "<strong><em><p><ul><ol><li><a><img><quote><code><pre><address><h1><h2><h3><h4><table><thead><tbody><tr><td><tfoot><caption>");
			$_POST[$key][$option] = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
		}
      }
    }
  }
