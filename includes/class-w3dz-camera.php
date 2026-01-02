<?php
/**
 * Camera management for WooCommerce 3D Asset Display Zyne
 * Handles camera angle settings for 3D models
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class W3DZ_Camera {
    
    /**
     * Default camera presets
     * These are quick-select options for common viewing angles
     */
    const PRESETS = array(
        'front' => array(
            'theta' => 0,
            'phi' => 75,
            'radius' => 3,
            'label' => 'Front View',
            'icon' => 'front'
        ),
        'side' => array(
            'theta' => 90,
            'phi' => 75,
            'radius' => 3,
            'label' => 'Side View',
            'icon' => 'side'
        ),
        'top' => array(
            'theta' => 0,
            'phi' => 0,
            'radius' => 5,
            'label' => 'Top View',
            'icon' => 'top'
        ),
        'diagonal' => array(
            'theta' => 45,
            'phi' => 65,
            'radius' => 3,
            'label' => '3/4 View',
            'icon' => 'diagonal'
        )
    );
    
    /**
     * Get camera settings for a product
     * Returns product-specific settings or global defaults
     * 
     * @param int $product_id Product ID
     * @return array Camera settings with theta, phi, radius, source
     */
    public static function get_camera_settings($product_id) {
        // Get product-specific settings
        $theta = get_post_meta($product_id, '_w3dz_camera_theta', true);
        $phi = get_post_meta($product_id, '_w3dz_camera_phi', true);
        $radius = get_post_meta($product_id, '_w3dz_camera_radius', true);
        
        // If product has custom settings, use them
        // Check for empty string because 0 is a valid value
        if ($theta !== '' && $phi !== '' && $radius !== '') {
            return array(
                'theta' => floatval($theta),
                'phi' => floatval($phi),
                'radius' => floatval($radius),
                'source' => 'product'
            );
        }
        
        // Otherwise, use global defaults
        return self::get_default_camera();
    }
    
    /**
     * Get global default camera settings
     * Falls back to hardcoded defaults if not set
     * 
     * @return array Default camera settings
     */
    public static function get_default_camera() {
        // Hardcoded fallback defaults
        $defaults = array(
            'theta' => 0,
            'phi' => 75,
            'radius' => 3,
            'source' => 'global'
        );
        
        // Try to get from settings
        $theta = W3DZ_Settings::get_option('default_camera_theta');
        $phi = W3DZ_Settings::get_option('default_camera_phi');
        $radius = W3DZ_Settings::get_option('default_camera_radius');
        
        // Override defaults if settings exist
        if ($theta !== null) {
            $defaults['theta'] = floatval($theta);
        }
        if ($phi !== null) {
            $defaults['phi'] = floatval($phi);
        }
        if ($radius !== null) {
            $defaults['radius'] = floatval($radius);
        }
        
        return $defaults;
    }
    
    /**
     * Get camera orbit string in model-viewer format
     * 
     * @param int $product_id Product ID
     * @return string Camera orbit string (e.g., "45deg 75deg 3m")
     */
    public static function get_camera_orbit($product_id) {
        $settings = self::get_camera_settings($product_id);
        return self::format_camera_orbit($settings['theta'], $settings['phi'], $settings['radius']);
    }
    
    /**
     * Format camera orbit string for model-viewer
     * 
     * @param float $theta Horizontal angle (0-360 degrees)
     * @param float $phi Vertical angle (0-180 degrees)
     * @param float $radius Distance in meters
     * @return string Formatted camera orbit (e.g., "45deg 75deg 3m")
     */
    public static function format_camera_orbit($theta, $phi, $radius) {
        return sprintf('%sdeg %sdeg %sm', $theta, $phi, $radius);
    }
    
    /**
     * Parse camera orbit string to array
     * Useful for reading saved strings back into components
     * 
     * @param string $orbit Camera orbit string (e.g., "45deg 75deg 3m")
     * @return array|false Array with theta, phi, radius or false on failure
     */
    public static function parse_camera_orbit($orbit) {
        if (empty($orbit)) {
            return false;
        }
        
        // Match pattern: "45deg 75deg 3m" or "45.5deg 75.2deg 3.5m"
        $pattern = '/^(\d+(?:\.\d+)?)deg\s+(\d+(?:\.\d+)?)deg\s+(\d+(?:\.\d+)?)m$/';
        
        if (preg_match($pattern, trim($orbit), $matches)) {
            return array(
                'theta' => floatval($matches[1]),
                'phi' => floatval($matches[2]),
                'radius' => floatval($matches[3])
            );
        }
        
        return false;
    }
    
    /**
     * Validate camera settings
     * 
     * @param float $theta Horizontal angle
     * @param float $phi Vertical angle
     * @param float $radius Distance
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public static function validate_camera_settings($theta, $phi, $radius) {
        // Validate theta (0-360)
        if ($theta < 0 || $theta > 360) {
            return new WP_Error('invalid_theta', __('Horizontal angle must be between 0 and 360 degrees', 'w3dz-asset-display'));
        }
        
        // Validate phi (0-180)
        if ($phi < 0 || $phi > 180) {
            return new WP_Error('invalid_phi', __('Vertical angle must be between 0 and 180 degrees', 'w3dz-asset-display'));
        }
        
        // Validate radius (0.5-50 meters is reasonable)
        if ($radius < 0.5 || $radius > 50) {
            return new WP_Error('invalid_radius', __('Distance must be between 0.5 and 50 meters', 'w3dz-asset-display'));
        }
        
        return true;
    }
    
    /**
     * Get all available presets
     * 
     * @return array Presets array with keys: front, side, top, diagonal
     */
    public static function get_presets() {
        return self::PRESETS;
    }
    
    /**
     * Get preset by key
     * 
     * @param string $key Preset key (front, side, top, diagonal)
     * @return array|false Preset data or false if not found
     */
    public static function get_preset($key) {
        return isset(self::PRESETS[$key]) ? self::PRESETS[$key] : false;
    }
    
    /**
     * Get model-viewer camera attributes for a product
     * Returns associative array of attributes to be applied
     * 
     * @param int $product_id Product ID
     * @return array Attributes array for model-viewer
     */
    public static function get_model_viewer_attributes($product_id) {
        $settings = self::get_camera_settings($product_id);
        
        $attributes = array(
            'camera-orbit' => self::format_camera_orbit(
                $settings['theta'], 
                $settings['phi'], 
                $settings['radius']
            ),
            // Fixed camera target
            'camera-target' => '0m 2m 0m',
            // Field of view
            'field-of-view' => '30deg',
            // Min/max camera orbit based on radius
            'min-camera-orbit' => sprintf('auto auto %sm', max(0.5, $settings['radius'] * 0.3)),
            'max-camera-orbit' => sprintf('auto auto %sm', min(50, $settings['radius'] * 3))
        );
        
        return $attributes;
    }
    
    /**
     * Sanitize camera settings from form input
     * Ensures values are within valid ranges
     * 
     * @param array $input Raw input data with keys: theta, phi, radius
     * @return array Sanitized data (empty strings if not set)
     */
    public static function sanitize_camera_input($input) {
        $sanitized = array(
            'theta' => '',
            'phi' => '',
            'radius' => ''
        );
        
        // Sanitize theta (0-360)
        if (isset($input['theta']) && $input['theta'] !== '') {
            $theta = floatval($input['theta']);
            $sanitized['theta'] = max(0, min(360, $theta));
        }
        
        // Sanitize phi (0-180)
        if (isset($input['phi']) && $input['phi'] !== '') {
            $phi = floatval($input['phi']);
            $sanitized['phi'] = max(0, min(180, $phi));
        }
        
        // Sanitize radius (0.5-50)
        if (isset($input['radius']) && $input['radius'] !== '') {
            $radius = floatval($input['radius']);
            $sanitized['radius'] = max(0.5, min(50, $radius));
        }
        
        return $sanitized;
    }
    
    /**
     * Check if product has custom camera settings
     * 
     * @param int $product_id Product ID
     * @return bool True if product has custom settings
     */
    public static function has_custom_camera($product_id) {
        $theta = get_post_meta($product_id, '_w3dz_camera_theta', true);
        $phi = get_post_meta($product_id, '_w3dz_camera_phi', true);
        $radius = get_post_meta($product_id, '_w3dz_camera_radius', true);
        
        return ($theta !== '' && $phi !== '' && $radius !== '');
    }
    
    /**
     * Clear product camera settings (reset to global defaults)
     * 
     * @param int $product_id Product ID
     * @return bool Success status
     */
    public static function clear_camera_settings($product_id) {
        delete_post_meta($product_id, '_w3dz_camera_theta');
        delete_post_meta($product_id, '_w3dz_camera_phi');
        delete_post_meta($product_id, '_w3dz_camera_radius');
        
        return true;
    }
	
	/**
     * Get camera orbit string with filter support
     * This allows Visual Editor format to be read correctly
     * 
     * @param int $product_id Product ID
     * @return string Camera orbit string (e.g., "45deg 75deg 3m")
     */
    public static function get_camera_orbit_with_filter($product_id) {
        // First check if there's a saved camera orbit (Visual Editor format)
        $orbit = get_post_meta($product_id, '_w3dz_camera_orbit', true);
        
        if (!empty($orbit)) {
            // Apply filter to allow Pro plugin to modify
            return apply_filters('w3dz_camera_orbit', $orbit, $product_id);
        }
        
        // Fall back to old format (theta, phi, radius)
        $settings = self::get_camera_settings($product_id);
        $orbit = self::format_camera_orbit($settings['theta'], $settings['phi'], $settings['radius']);
        
        // Apply filter
        return apply_filters('w3dz_camera_orbit', $orbit, $product_id);
    }
}