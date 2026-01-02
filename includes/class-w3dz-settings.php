<?php
/**
 * Settings page for WooCommerce 3D Asset Display Zyne
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class W3DZ_Settings {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Option name in database
     */
    private $option_name = 'w3dz_settings';
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Add settings page to WooCommerce menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            __('3D & AR Settings', 'w3dz-asset-display'),
            __('3D & AR', 'w3dz-asset-display'),
            'manage_woocommerce',
            'w3dz-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Enqueue admin assets (color picker)
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our settings page
        if ('woocommerce_page_w3dz-settings' !== $hook) {
            return;
        }
        
        // WordPress color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Enqueue admin CSS
        wp_enqueue_style(
            'w3dz-admin-style',
            W3DZ_PLUGIN_URL . 'assets/css/w3dz-admin.css',
            array(),
            W3DZ_VERSION
        );
        
        // Custom admin script for color picker initialization
        wp_add_inline_script('wp-color-picker', '
            jQuery(document).ready(function($) {
                $(".w3dz-color-picker").wpColorPicker();
            });
        ');
    }
    
    /**
     * Register settings and fields
     */
    public function register_settings() {
        // Register the settings
        register_setting(
            'w3dz_settings_group',
            $this->option_name,
            array($this, 'sanitize_settings')
        );
        
        // Section 1: Default Camera Settings
        add_settings_section(
            'w3dz_camera_section',
            __('Default Camera Settings', 'w3dz-asset-display'),
            array($this, 'render_camera_section_desc'),
            'w3dz-settings'
        );
        
        add_settings_field(
            'default_camera_theta',
            __('Horizontal Angle (Theta)', 'w3dz-asset-display'),
            array($this, 'render_number_field'),
            'w3dz-settings',
            'w3dz_camera_section',
            array(
                'name' => 'default_camera_theta',
                'min' => 0,
                'max' => 360,
                'step' => 1,
                'default' => 0,
                'unit' => 'deg',
                'description' => __('Horizontal rotation angle (0-360 degrees). 0° = front view, 90° = side view.', 'w3dz-asset-display')
            )
        );
        
        add_settings_field(
            'default_camera_phi',
            __('Vertical Angle (Phi)', 'w3dz-asset-display'),
            array($this, 'render_number_field'),
            'w3dz-settings',
            'w3dz_camera_section',
            array(
                'name' => 'default_camera_phi',
                'min' => 0,
                'max' => 180,
                'step' => 1,
                'default' => 75,
                'unit' => 'deg',
                'description' => __('Vertical angle (0-180 degrees). 90° = horizontal, 0° = top view, 180° = bottom view.', 'w3dz-asset-display')
            )
        );
        
        add_settings_field(
            'default_camera_radius',
            __('Camera Distance', 'w3dz-asset-display'),
            array($this, 'render_number_field'),
            'w3dz-settings',
            'w3dz_camera_section',
            array(
                'name' => 'default_camera_radius',
                'min' => 0.5,
                'max' => 50,
                'step' => 0.5,
                'default' => 3,
                'unit' => 'm',
                'description' => __('Distance from camera to object (0.5-50 meters). Smaller = closer zoom.', 'w3dz-asset-display')
            )
        );
        
        // Section 2: Product Archive (List Page) Settings
        add_settings_section(
            'w3dz_archive_section',
            __('Product List Page Settings', 'w3dz-asset-display'),
            array($this, 'render_archive_section_desc'),
            'w3dz-settings'
        );
        
        add_settings_field(
            'archive_enable_hover',
            __('Enable Hover 3D Preview', 'w3dz-asset-display'),
            array($this, 'render_checkbox_field'),
            'w3dz-settings',
            'w3dz_archive_section',
            array(
                'name' => 'archive_enable_hover',
                'label' => __('Show 3D model when hovering over products in shop/category pages', 'w3dz-asset-display'),
                'default' => true
            )
        );
        
        add_settings_field(
            'archive_show_progress',
            __('Show Loading Progress Bar', 'w3dz-asset-display'),
            array($this, 'render_checkbox_field'),
            'w3dz-settings',
            'w3dz_archive_section',
            array(
                'name' => 'archive_show_progress',
                'label' => __('Display a progress bar when loading 3D models', 'w3dz-asset-display'),
                'default' => true
            )
        );
        
        add_settings_field(
            'archive_progress_color',
            __('Progress Bar Color', 'w3dz-asset-display'),
            array($this, 'render_color_field'),
            'w3dz-settings',
            'w3dz_archive_section',
            array(
                'name' => 'archive_progress_color',
                'default' => '#0073aa'
            )
        );
        
        add_settings_field(
            'enable_auto_rotate',
            __('Enable Auto-Rotation', 'w3dz-asset-display'),
            array($this, 'render_checkbox_field'),
            'w3dz-settings',
            'w3dz_archive_section',
            array(
                'name' => 'enable_auto_rotate',
                'label' => __('Automatically rotate 3D models when displayed (applies to both list and single product pages)', 'w3dz-asset-display'),
                'default' => true
            )
        );
        
        add_settings_field(
            'rotation_speed',
            __('Rotation Speed', 'w3dz-asset-display'),
            array($this, 'render_select_field'),
            'w3dz-settings',
            'w3dz_archive_section',
            array(
                'name' => 'rotation_speed',
                'options' => array(
                    '20deg' => __('Slow (Elegant)', 'w3dz-asset-display'),
                    '30deg' => __('Medium (Recommended)', 'w3dz-asset-display'),
                    '45deg' => __('Fast (Dynamic)', 'w3dz-asset-display')
                ),
                'default' => '30deg'
            )
        );
        
        add_settings_field(
            'enable_360_autoplay',
            __('Enable 360° Auto-Play', 'w3dz-asset-display'),
            array($this, 'render_checkbox_field'),
            'w3dz-settings',
            'w3dz_archive_section',
            array(
                'name' => 'enable_360_autoplay',
                'label' => __('Automatically play 360° image sequence when viewer opens', 'w3dz-asset-display'),
                'default' => true
            )
        );

        add_settings_field(
            '360_playback_speed',
            __('360° Playback Speed', 'w3dz-asset-display'),
            array($this, 'render_select_field'),
            'w3dz-settings',
            'w3dz_archive_section',
            array(
                'name' => '360_playback_speed',
                'options' => array(
                    '50' => __('Very Slow (50ms per frame)', 'w3dz-asset-display'),
                    '75' => __('Slow (75ms per frame)', 'w3dz-asset-display'),
                    '100' => __('Medium (100ms per frame)', 'w3dz-asset-display'),
                    '125' => __('Fast (125ms per frame)', 'w3dz-asset-display'),
                    '150' => __('Very Fast (150ms per frame)', 'w3dz-asset-display')
                ),
                'default' => '100',
                'description' => __('Speed of 360° rotation animation frames', 'w3dz-asset-display')
            )
        );

        add_settings_field(
            '360_loop_infinite',
            __('360° Loop Infinitely', 'w3dz-asset-display'),
            array($this, 'render_checkbox_field'),
            'w3dz-settings',
            'w3dz_archive_section',
            array(
                'name' => '360_loop_infinite',
                'label' => __('Loop 360° animation continuously (if unchecked, plays once and stops)', 'w3dz-asset-display'),
                'default' => false
            )
        );

        add_settings_field(
            '360_autoplay_hover',
            __('Auto-Play on Hover (Archive)', 'w3dz-asset-display'),
            array($this, 'render_checkbox_field'),
            'w3dz-settings',
            'w3dz_archive_section',
            array(
                'name' => '360_autoplay_hover',
                'label' => __('Auto-play 360° animation when hovering over products in shop/category pages', 'w3dz-asset-display'),
                'default' => false
            )
        );
        
        // Section 3: Performance Settings
        add_settings_section(
            'w3dz_performance_section',
            __('Performance Settings', 'w3dz-asset-display'),
            array($this, 'render_performance_section_desc'),
            'w3dz-settings'
        );
        
        add_settings_field(
            'max_concurrent_loads',
            __('Maximum Concurrent Loads', 'w3dz-asset-display'),
            array($this, 'render_number_field'),
            'w3dz-settings',
            'w3dz_performance_section',
            array(
                'name' => 'max_concurrent_loads',
                'min' => 1,
                'max' => 3,
                'default' => 1,
                'description' => __('Maximum number of 3D models that can load simultaneously on list pages. Lower numbers improve performance.', 'w3dz-asset-display')
            )
        );
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        
        // Show success message if settings saved
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WordPress Settings API adds this parameter
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'w3dz_messages',
                'w3dz_message',
                __('Settings saved successfully.', 'w3dz-asset-display'),
                'updated'
            );
        }
        
        // Show error messages
        settings_errors('w3dz_messages');
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; border-radius: 4px;">
                <h2 style="margin-top: 0;"><?php esc_html_e('How to Use', 'w3dz-asset-display'); ?></h2>
                <ol style="line-height: 1.8;">
                    <li><?php esc_html_e('Configure your preferred display settings below', 'w3dz-asset-display'); ?></li>
                    <li><?php esc_html_e('Go to any product edit page', 'w3dz-asset-display'); ?></li>
                    <li><?php esc_html_e('Scroll to "3D & 360 View" tab in Product Data', 'w3dz-asset-display'); ?></li>
                    <li><?php esc_html_e('Upload your 3D model (.glb file)', 'w3dz-asset-display'); ?></li>
                    <li><?php esc_html_e('Optionally customize camera angle for this product', 'w3dz-asset-display'); ?></li>
                    <li><?php esc_html_e('Save the product and view it on the frontend', 'w3dz-asset-display'); ?></li>
                </ol>
            </div>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('w3dz_settings_group');
                do_settings_sections('w3dz-settings');
                submit_button(__('Save Settings', 'w3dz-asset-display'));
                ?>
            </form>
            
            <?php
            /**
             * Hook: w3dz_after_settings_form
             * 
             * Allows extending the settings page with additional options.
             * 
             * @since 1.0.0
             */
            do_action('w3dz_after_settings_form');
            ?>
            
        </div>
        <?php
    }
    
    /**
     * Section descriptions
     */
    public function render_camera_section_desc() {
        echo '<p>' . esc_html__('Set the default camera angle for all 3D models. Individual products can override these settings.', 'w3dz-asset-display') . '</p>';
        echo '<p style="background: #f0f6fc; border-left: 4px solid #0073aa; padding: 12px; margin: 15px 0;">';
        echo '<strong>' . esc_html__('Quick Reference:', 'w3dz-asset-display') . '</strong><br>';
        echo esc_html__('Front view: 0° horizontal, 75° vertical, 3m distance', 'w3dz-asset-display') . '<br>';
        echo esc_html__('Side view: 90° horizontal, 75° vertical, 3m distance', 'w3dz-asset-display') . '<br>';
        echo esc_html__('Top view: 0° horizontal, 0° vertical, 5m distance', 'w3dz-asset-display') . '<br>';
        echo esc_html__('3/4 view (recommended): 45° horizontal, 65° vertical, 3m distance', 'w3dz-asset-display');
        echo '</p>';
    }
    
    public function render_archive_section_desc() {
        echo '<p>' . esc_html__('Configure how 3D models appear on product list pages (shop, category, tag pages).', 'w3dz-asset-display') . '</p>';
    }
    
    public function render_performance_section_desc() {
        echo '<p>' . esc_html__('Optimize performance for pages with many products.', 'w3dz-asset-display') . '</p>';
    }
    
    /**
     * Render checkbox field
     */
    public function render_checkbox_field($args) {
        $options = get_option($this->option_name, array());
        $name = $args['name'];
        $default = isset($args['default']) ? $args['default'] : false;
        $value = isset($options[$name]) ? $options[$name] : $default;
        $label = isset($args['label']) ? $args['label'] : '';
        
        ?>
        <label>
            <input type="checkbox" 
                   name="<?php echo esc_attr($this->option_name . '[' . $name . ']'); ?>" 
                   value="1" 
                   <?php checked($value, true); ?> />
            <?php echo esc_html($label); ?>
        </label>
        <?php
    }
    
    /**
     * Render color picker field
     */
    public function render_color_field($args) {
        $options = get_option($this->option_name, array());
        $name = $args['name'];
        $default = isset($args['default']) ? $args['default'] : '#000000';
        $value = isset($options[$name]) ? $options[$name] : $default;
        
        ?>
        <input type="text" 
               name="<?php echo esc_attr($this->option_name . '[' . $name . ']'); ?>" 
               value="<?php echo esc_attr($value); ?>" 
               class="w3dz-color-picker" 
               data-default-color="<?php echo esc_attr($default); ?>" />
        <?php
    }
    
    /**
     * Render select field
     */
    public function render_select_field($args) {
        $options = get_option($this->option_name, array());
        $name = $args['name'];
        $select_options = $args['options'];
        $default = isset($args['default']) ? $args['default'] : '';
        $value = isset($options[$name]) ? $options[$name] : $default;
        $description = isset($args['description']) ? $args['description'] : '';
        
        ?>
        <select name="<?php echo esc_attr($this->option_name . '[' . $name . ']'); ?>">
            <?php foreach ($select_options as $key => $label) : ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($value, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Render number field
     */
    public function render_number_field($args) {
        $options = get_option($this->option_name, array());
        $name = $args['name'];
        $min = isset($args['min']) ? $args['min'] : 1;
        $max = isset($args['max']) ? $args['max'] : 100;
        $step = isset($args['step']) ? $args['step'] : 1;
        $default = isset($args['default']) ? $args['default'] : $min;
        $value = isset($options[$name]) ? $options[$name] : $default;
        $description = isset($args['description']) ? $args['description'] : '';
        $unit = isset($args['unit']) ? $args['unit'] : '';
        
        ?>
        <input type="number" 
               name="<?php echo esc_attr($this->option_name . '[' . $name . ']'); ?>" 
               value="<?php echo esc_attr($value); ?>" 
               min="<?php echo esc_attr($min); ?>" 
               max="<?php echo esc_attr($max); ?>"
               step="<?php echo esc_attr($step); ?>"
               class="small-text" />
        <?php if ($unit) : ?>
            <span class="unit"><?php echo esc_html($unit); ?></span>
        <?php endif; ?>
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Sanitize settings before saving
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Camera settings (numbers with validation)
        $sanitized['default_camera_theta'] = isset($input['default_camera_theta']) 
            ? max(0, min(360, floatval($input['default_camera_theta']))) 
            : 0;
        
        $sanitized['default_camera_phi'] = isset($input['default_camera_phi']) 
            ? max(0, min(180, floatval($input['default_camera_phi']))) 
            : 75;
        
        $sanitized['default_camera_radius'] = isset($input['default_camera_radius']) 
            ? max(0.5, min(50, floatval($input['default_camera_radius']))) 
            : 3;
        
        // Checkboxes
        $sanitized['archive_enable_hover'] = isset($input['archive_enable_hover']) ? true : false;
        $sanitized['archive_show_progress'] = isset($input['archive_show_progress']) ? true : false;
        $sanitized['enable_auto_rotate'] = isset($input['enable_auto_rotate']) ? true : false;
        
        // Color
        $sanitized['archive_progress_color'] = isset($input['archive_progress_color']) 
            ? sanitize_hex_color($input['archive_progress_color']) 
            : '#0073aa';
        
        // Rotation speed
        $allowed_speeds = array('20deg', '30deg', '45deg');
        $sanitized['rotation_speed'] = isset($input['rotation_speed']) 
            && in_array($input['rotation_speed'], $allowed_speeds)
            ? $input['rotation_speed'] 
            : '30deg';
        
        // Number
        $sanitized['max_concurrent_loads'] = isset($input['max_concurrent_loads']) 
            ? absint($input['max_concurrent_loads']) 
            : 1;
        
        // Ensure max_concurrent_loads is within range
        if ($sanitized['max_concurrent_loads'] < 1) {
            $sanitized['max_concurrent_loads'] = 1;
        }
        if ($sanitized['max_concurrent_loads'] > 3) {
            $sanitized['max_concurrent_loads'] = 3;
        }
        
        // 360 degree Auto-play settings
        $sanitized['enable_360_autoplay'] = isset($input['enable_360_autoplay']) ? true : false;
        $sanitized['360_autoplay_hover'] = isset($input['360_autoplay_hover']) ? true : false;
        $sanitized['360_loop_infinite'] = isset($input['360_loop_infinite']) ? true : false;

        // 360 degree Playback speed
        $allowed_360_speeds = array('50', '75', '100', '125', '150');
        $sanitized['360_playback_speed'] = isset($input['360_playback_speed']) 
            && in_array($input['360_playback_speed'], $allowed_360_speeds)
            ? $input['360_playback_speed'] 
            : '100';
        
        return $sanitized;
    }
    
    /**
     * Get a specific option value
     */
    public static function get_option($key, $default = null) {
        $options = get_option('w3dz_settings', array());
        
        // Return value if exists
        if (isset($options[$key])) {
            return $options[$key];
        }
        
        // Return default if provided
        if ($default !== null) {
            return $default;
        }
        
        // Return hardcoded defaults
        $defaults = array(
            // Camera defaults
            'default_camera_theta' => 0,
            'default_camera_phi' => 75,
            'default_camera_radius' => 3,
            // Archive defaults
            'archive_enable_hover' => true,
            'archive_show_progress' => true,
            'archive_progress_color' => '#0073aa',
            'enable_auto_rotate' => true,
            'rotation_speed' => '30deg',
            'max_concurrent_loads' => 1,
            'enable_360_autoplay' => true,
            '360_playback_speed' => '100',
            '360_loop_infinite' => false,
            '360_autoplay_hover' => false
        );
        
        return isset($defaults[$key]) ? $defaults[$key] : null;
    }
}