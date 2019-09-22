<?php

if (function_exists('acf_add_local_field_group')):

    acf_add_local_field_group(array(
        'key'                   => 'group_custom_scripts',
        'title'                 => __('Scripts', 'custom-scripts'),
        'fields'                => array(
            array(
                'key'               => 'field_custom_scripts',
                'label'             => __('Scripts', 'custom-scripts'),
                'name'              => 'scripts',
                'type'              => 'repeater',
                'instructions'      => __('Scripts à insérer dans la page (inclure les balises &lt;script&gt;)',
                    'custom-scripts'),
                'required'          => 0,
                'conditional_logic' => 0,
                'wrapper'           => array(
                    'width' => '',
                    'class' => '',
                    'id'    => '',
                ),
                'collapsed'         => '',
                'min'               => 0,
                'max'               => 0,
                'layout'            => 'row',
                'button_label'      => __('Ajouter un script', 'custom-scripts'),
                'sub_fields'        => array(
                    array(
                        'key'               => 'field_custom_scripts_position',
                        'label'             => __('Position', 'custom-scripts'),
                        'name'              => 'position',
                        'type'              => 'radio',
                        'instructions'      => '',
                        'required'          => 0,
                        'conditional_logic' => 0,
                        'wrapper'           => array(
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ),
                        'allow_null'        => 0,
                        'other_choice'      => 0,
                        'save_other_choice' => 0,
                        'default_value'     => '',
                        'layout'            => 'horizontal',
                        'return_format'     => 'value',
                    ),
                    array(
                        'key'               => 'field_custom_scripts_script',
                        'label'             => __('Script', 'custom-scripts'),
                        'name'              => 'script',
                        'type'              => 'textarea',
                        'instructions'      => '',
                        'required'          => 0,
                        'conditional_logic' => 0,
                        'wrapper'           => array(
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ),
                        'default_value'     => '',
                        'placeholder'       => '',
                        'maxlength'         => '',
                        'rows'              => '',
                        'new_lines'         => '',
                        'mode'              => 'htmlmixed',
                        'theme'             => 'monokai',
                    ),
                ),
            ),
        ),
        'location'              => array(
            array(
                array(
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'custom-scripts',
                ),
            ),
        ),
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'seamless',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen'        => '',
        'active'                => true,
        'description'           => '',
        'modified'              => 1560279377,
    ));

endif;
