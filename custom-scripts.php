<?php

/**
 * Plugin Name: Custom Scripts
 * Plugin URI: https://github.com/jerome-rdlv/custom-scripts
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
    private const TEXTDOMAIN = 'custom-scripts';
    private const PAGE_SLUG = 'custom-scripts';
    private const FIELD_NAME = 'custom_scripts';

    private const SCRIPT_INC_HEAD = 'head';
    private const SCRIPT_INC_BODY_TOP = 'body_top';
    private const SCRIPT_INC_BODY_BOTTOM = 'body_bottom';
    private const SCRIPT_INC_DISABLED = 'disabled';

    private array $scriptIncs;
    private string $field_name;

    public function __construct()
    {
        add_action('plugins_loaded', [$this, 'plugins_loaded']);
    }

    /**
     * Load plugin text domain. This function allows the plugin to be
     * installed anywhere, in plugins/ or in mu-plugins/ for example.
     * @return bool
     */
    public function load_text_domain(): bool
    {
        /** This filter is documented in wp-includes/l10n.php */
        $locale = apply_filters('plugin_locale', determine_locale(), self::TEXTDOMAIN);
        $mofile = self::TEXTDOMAIN.'-'.$locale.'.mo';

        // Try to load from the languages directory first.
        if (load_textdomain(self::TEXTDOMAIN, WP_LANG_DIR.'/plugins/'.$mofile)) {
            return true;
        }

        // Load from plugin languages folder.
        return load_textdomain(self::TEXTDOMAIN, __DIR__.'/languages/'.$mofile);
    }

    public function plugins_loaded(): void
    {
        if (!function_exists('acf_add_options_page') || !function_exists('get_field')) {
            return;
        }

        $this->load_text_domain();

        add_action('init', [$this, 'init']);

        if (function_exists('acf_add_options_page')) {
            acf_add_options_page(
                [
                    'page_title' => __('Scripts', 'custom-scripts'),
                    'menu_title' => __('Scripts', 'custom-scripts'),
                    'menu_slug' => self::PAGE_SLUG,
                    'capability' => 'edit_posts',
                    'parent_slug' => 'options-general.php',
                ]
            );
        }
    }

    public function init(): void
    {
        $this->field_name = apply_filters('custom_scripts_field_name', self::FIELD_NAME);

        $type = 'textarea';
        if (function_exists('acf_get_field_types') && array_key_exists('acf_code_field', acf_get_field_types())) {
            $type = 'acf_code_field';
        }
        acf_add_local_field_group(
            [
                'key' => 'custom_scripts_group',
                'title' => __('Scripts', 'custom-scripts'),
                'fields' => [
                    [
                        'key' => 'custom_scripts_field',
                        'label' => __('Scripts', 'custom-scripts'),
                        'name' => $this->field_name,
                        'type' => 'repeater',
                        'instructions' => __(
                            'Script à insérer dans la page (inclure les balises &lt;script&gt; le cas échéant)',
                            'custom-scripts'
                        ),
                        'button_label' => __('Ajouter un script', 'custom-scripts'),
                        'layout' => 'row',
                        'sub_fields' => [
                            [
                                'key' => 'custom_scripts_field_position',
                                'label' => __('Position', 'custom-scripts'),
                                'name' => 'position',
                                'type' => 'radio',
                                'allow_null' => 0,
                                'other_choice' => 0,
                                'save_other_choice' => 0,
                                'default_value' => self::SCRIPT_INC_HEAD,
                                'layout' => 'horizontal',
                                'return_format' => 'value',
                            ],
                            [
                                'key' => 'custom_scripts_field_script',
                                'label' => __('Script', 'custom-scripts'),
                                'name' => 'script',
                                'type' => $type,
                                'rows' => 5,
                                'mode' => 'htmlmixed',
                                'theme' => 'monokai',
                            ],
                        ],
                    ],
                ],
                'location' => [
                    [
                        [
                            'param' => 'options_page',
                            'operator' => '==',
                            'value' => self::PAGE_SLUG,
                        ],
                    ],
                ],
                'style' => 'seamless',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'modified' => 1560279377,
            ]
        );

        $this->scriptIncs = [
            self::SCRIPT_INC_HEAD => __('Entête', 'custom-scripts'),
            self::SCRIPT_INC_BODY_TOP => __('Juste après &lt;body&gt;', 'custom-scripts'),
            self::SCRIPT_INC_BODY_BOTTOM => __('Juste avant &lt;/body&gt;', 'custom-scripts'),
            self::SCRIPT_INC_DISABLED => __('Désactivé', 'custom-scripts'),
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

        $filter_name = sprintf('acf/load_field/name=%s', $this->field_name);
        add_filter($filter_name, [$this, 'scripts_insert_position']);
    }

    public function scripts_inc($position): void
    {
        $scripts = get_field($this->field_name, 'options');
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