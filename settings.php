<?php
/**
 * Plugin Name: Killerapps Settings
 * Description:  Custom settings pages for WordPress
 * Version: 1.0
 * Author: Zosia Sobocinska
 * Author URI: http://www.killeapps.pl
 * License: GPLv2 or later
 */

add_action('admin_enqueue_scripts','killerapps_settings_admin_enqueue');
function killerapps_settings_admin_enqueue() {
	wp_enqueue_media();
    wp_enqueue_style( 'cc1-settings', plugins_url( '/css/settings.css', __FILE__ ) );
	wp_enqueue_script( 'cc1-settings', plugins_url( '/js/settings.js', __FILE__), array('jquery'));
}

 class KillerappsCreateSettingsPage
{

	private $title;
	private $group;
	private $option_name;
	private $sections;
	private $capability;
	private $fields;

    public function KillerappsCreateSettingsPage($title, $group, $option_name, $sections, $capability)
    {
    	$this->title = $title;
		$this->group = $group;
		$this->option_name = $option_name;
		$this->sections = $sections;
		$this->capability = $capability;
		$this->fields = array();

        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    public function add_plugin_page()
    {
        add_options_page(
            $this->title, 
            $this->title, 
            $this->capability, 
            $this->group, 
            array( $this, 'create_admin_page' )
        );
    }

    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( $this->option_name );
		
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

	public function page_init()
	{
		register_setting(
			$this->group,
			$this->option_name,
			array( $this, 'sanitize' )
		);

		foreach ($this->sections as $section) {
				
			add_settings_section(
				$section['id'],
				$section['title'],
				NULL, // Callback
				$this->group
			);
			
			foreach ($section['fields'] as $field) {
				$settingsField = new KillerappsCreateSettingsField($field["id"], $field["title"], $field["description"], $field["type"], $field['default'], $field['options'], $this->group, $this->option_name);
				$this->fields[] = $settingsField;
				$settingsField->add_to_section($section["id"]);
			}
		}
	}

	public function sanitize( $input )
	{
		$new_input = array();
		foreach ($this->fields as $field) {
			try {
				$id = $field->id;
				if( isset( $input[$id] ) )
				$new_input[$id] = $field->sanitize($input[$id]);
			} catch (Exception $e) {}
		}
		return $new_input;
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
	private $section_id;

	function KillerappsCreateSettingsField($id, $title, $description, $type, $default, $options, $group, $option_name) {
		$this->id = $id;
		$this->title = $title;
		$this->description = $description;
		$this->type = $type;
		$this->default = $default;
		$this->options = $options;
		$this->group = $group;
		$this->option_name = $option_name;
		$this->section_id = $section_id;
	}

	function add_to_section($section_id) {
		add_settings_field(
			$this->id,
			$this->title,
			array( $this, 'callback' ),
			$this->group,
			$section_id
		);
	}

	function callback() {

		$options = get_option( $this->option_name );
		$value = $options[$this->id];
		if ( $value === NULL && $this->default) {
    		update_option( $this->id, $this->default );
			$value = $this->default;
		}
		if ($this->description) {echo "<p class='setting-field-description'>" . $this->description . "</p>";}
		echo "<p class='setting-field setting-field-{$this->type}'>";
		switch ($this->type) {
			case "image":
				?>
				<div class="image-setting">
					<?php echo "<input type='hidden' id='{$this->id}' name='{$this->option_name}[{$this->id}]' value='{$value}'/>"; ?>
					<input type="button" value="Select Image" class="killerapps-settings-image-select button"/>
					<span class="killerapps-settings-image-container"><?php echo wp_get_attachment_image($value) ?></span>
					<br/><br/>
					<input type="button" value="Detach" class="killerapps-settings-image-detach button <?php if(!$value) {echo "hidden";} ?>"/>
				</div>
				<div class="clear"></div>
				<?php
				break;
			default:
				$more = '';
				switch ($this->type) {
					case 'number':
						if ($this->options && $this->options['step']) {
							$more .= ' step="' . $this->options['step'] . '"';
						}
						break;
					case 'range':
						if ($this->options['step'])
							$more .= ' step="' . $this->options['step'] . '"';
						if ($this->options['max'])
							$more .= " max={$this->options['max']}";
						if ($this->options['min'])
							$more .= " min={$this->options['min']}";
						$more .= ' data-wtf';
						break;
				}
				if ($this->options['min'] || $this->options['min'] == 0)
					echo "<span class='killerapps-settings-range-min'>{$this->options['min']}</span>";
				echo "<input type='{$this->type}' id='{$this->id}' name='{$this->option_name}[{$this->id}]' value='{$value}' class='killerapps-settings-{$this->type}' {$more} />";
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
		$value = sanitize_text_field($value);
		switch ($this->type) {
			case 'number':
			case 'range':
				return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
				break;
			case 'image':
				$value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
				if (wp_get_attachment_image( $value ) ) {
					return $value;
				} else {
					return '';
				}
				break;

			default:
				return filter_var($value, FILTER_SANITIZE_STRIPPED);
		}
	}
}

if( is_admin() ) {
	do_action('killerapps-settings-init');
}

