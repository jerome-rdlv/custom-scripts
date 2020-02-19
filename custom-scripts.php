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
    const PAGE_SLUG = 'custom-scripts';
    const POST_ID = 'custom-scripts';

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
        $locale = apply_filters('plugin_locale', determine_locale(), self::TEXTDOMAIN);
        $mofile = self::TEXTDOMAIN . '-' . $locale . '.mo';

        // Try to load from the languages directory first.
        if (load_textdomain(self::TEXTDOMAIN, WP_LANG_DIR . '/plugins/' . $mofile)) {
            return true;
        }

        // Load from plugin languages folder.
        return load_textdomain(self::TEXTDOMAIN, __DIR__ . '/languages/' . $mofile);
    }

    public function plugins_loaded()
    {
        if (function_exists('acf_add_options_page')) {
            acf_add_options_page([
                'page_title'  => __('Scripts', 'custom-scripts'),
                'menu_title'  => __('Scripts', 'custom-scripts'),
                'menu_slug'   => self::PAGE_SLUG,
                'capability'  => 'edit_posts',
                'parent_slug' => 'options-general.php',
                'post_id'     => self::POST_ID,
            ]);
        }
    }

    public function init()
    {
        $type = 'textarea';
        if (function_exists('acf_get_field_types') && array_key_exists('acf_code_field', acf_get_field_types())) {
            $type = 'acf_code_field';
        }
        acf_add_local_field_group([
            'key'                   => 'group_custom_scripts',
            'title'                 => __('Scripts', 'custom-scripts'),
            'fields'                => [
                [
                    'key'          => 'field_custom_scripts',
                    'label'        => __('Scripts', 'custom-scripts'),
                    'name'         => 'scripts',
                    'type'         => 'repeater',
                    'instructions' => __('Script à insérer dans la page (inclure les balises &lt;script&gt; le cas échéant)',
                        'custom-scripts'),
                    'button_label' => __('Ajouter un script', 'custom-scripts'),
                    'layout'       => 'row',
                    'sub_fields'   => [
                        [
                            'key'               => 'field_custom_scripts_position',
                            'label'             => __('Position', 'custom-scripts'),
                            'name'              => 'position',
                            'type'              => 'radio',
                            'allow_null'        => 0,
                            'other_choice'      => 0,
                            'save_other_choice' => 0,
                            'default_value'     => self::SCRIPT_INC_HEAD,
                            'layout'            => 'horizontal',
                            'return_format'     => 'value',
                        ],
                        [
                            'key'   => 'field_custom_scripts_script',
                            'label' => __('Script', 'custom-scripts'),
                            'name'  => 'script',
                            'type'  => $type,
                            'rows'  => 5,
                            'mode'  => 'htmlmixed',
                            'theme' => 'monokai',
                        ],
                    ],
                ],
            ],
            'location'              => [
                [
                    [
                        'param'    => 'options_page',
                        'operator' => '==',
                        'value'    => self::PAGE_SLUG,
                    ],
                ],
            ],
            'style'                 => 'seamless',
            'label_placement'       => 'top',
            'instruction_placement' => 'label',
            'modified'              => 1560279377,
        ]);

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
    }

    public function scripts_inc($position)
    {
        $scripts = get_field('scripts', self::POST_ID);
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
}