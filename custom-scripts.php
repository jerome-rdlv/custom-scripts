<?php

/**
 * Plugin Name: Custom Scripts
 * Plugin URI: https://gitlab.rue-de-la-vieille.fr/jerome/custom-scripts
 * Author: Jérôme Mulsant
 * Author URI: https://rue-de-la-vieille.fr/
 * Description: Insert custom scripts in pages
 * License: MIT License
 * Version: GIT
 */

namespace Rdlv\WordPress\CustomScripts;

new CustomScripts();

class CustomScripts
{
    const TEXTDOMAIN = 'custom-scripts';
    
    const SCRIPT_INC_HEAD = 'head';
    const SCRIPT_INC_BODY_TOP = 'body_top';
    const SCRIPT_INC_BODY_BOTTOM = 'body_bottom';

    private $scriptIncs;

    public function __construct()
    {
        if (!function_exists('acf_add_options_page') || !function_exists('get_field')) {
            return;
        }

        add_action('plugins_loaded', [$this, 'load_text_domain']);
        add_action('plugins_loaded', [$this, 'plugins_loaded']);
        add_action('init', [$this, 'init']);
    }

    /**
     * Load plugin text domain. This function allows the plugin to be
     * installed anywhere, in plugins/ or in mu-plugins/ for example.
     * @return bool
     */
    public function load_text_domain()
    {
        /** This filter is documented in wp-includes/l10n.php */
        $locale = apply_filters( 'plugin_locale', determine_locale(), self::TEXTDOMAIN );
        $mofile = self::TEXTDOMAIN . '-' . $locale . '.mo';

        // Try to load from the languages directory first.
        if( load_textdomain( self::TEXTDOMAIN, WP_LANG_DIR . '/plugins/' . $mofile ) ) {
            return true;
        }

        // Load from plugin languages folder.
        return load_textdomain(self::TEXTDOMAIN, __DIR__ . '/languages/' . $mofile);
    }

    public function plugins_loaded()
    {
        if (function_exists('acf_add_options_page')) {
            acf_add_options_page(array(
                'page_title'  => __('Scripts', 'custom-scripts'),
                'menu_title'  => __('Scripts', 'custom-scripts'),
                'menu_slug'   => 'custom-scripts',
                'capability'  => 'edit_posts',
                'parent_slug' => 'options-general.php',
            ));
        }
    }

    public function init()
    {
        require_once __DIR__ . '/fields.php';

        $this->scriptIncs = [
            self::SCRIPT_INC_HEAD        => __('Entête', 'custom-scripts'),
            self::SCRIPT_INC_BODY_TOP    => __('Juste après &lt;body&gt;', 'custom-scripts'),
            self::SCRIPT_INC_BODY_BOTTOM => __('Juste avant &lt;/body&gt;', 'custom-scripts'),
        ];

        add_action('wp_head', function () {
            $this->scripts_inc(self::SCRIPT_INC_HEAD);
        }, 0);
        add_action('wp_body_open', function () {
            $this->scripts_inc(self::SCRIPT_INC_BODY_TOP);
        });
        add_action('wp_footer', function () {
            $this->scripts_inc(self::SCRIPT_INC_BODY_BOTTOM);
        }, 100);

        $filter_name = sprintf('acf/load_field/name=%s', apply_filters('custom_scripts_field_name', 'scripts'));
        add_filter($filter_name, [$this, 'scripts_insert_position']);
        add_filter($filter_name, [$this, 'code_field_type']);
    }

    public function scripts_inc($position)
    {
        $scripts = get_field('scripts', 'options');
        if ($scripts) {
            foreach ($scripts as $script) {
                if ($script['position'] === $position) {
                    echo $script['script'];
                }
            }
        }
    }

    public function scripts_insert_position($field)
    {
        global $post;

        if ($post && $post->post_type === 'acf-field-group' && is_admin()) {
            return $field;
        }
        if ($field['type'] !== 'repeater') {
            return $field;
        }
        if (!isset($field['sub_fields'][0])) {
            return $field;
        }
        if ($field['sub_fields'][0]['name'] !== 'position') {
            return $field;
        }

        $field['sub_fields'][0]['choices'] = $this->scriptIncs;

        return $field;
    }

    public function code_field_type($field)
    {
        $type = 'textarea';
        if (function_exists('acf_get_field_types') && array_key_exists('acf_code_field', acf_get_field_types())) {
            $type = 'acf_code_field';
        }
        $field['sub_fields'][1]['type'] = $type;
        return $field;
    }
}