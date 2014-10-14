<?php

class acf_field_widget_field extends acf_field
{
    /**
     *  __construct
     *
     *  This function will setup the field type data
     *
     *  @type    function
     *  @date    5/03/2014
     *  @since   5.0.0
     *
     *  @param   n/a
     *  @return  n/a
     */
    function __construct()
    {
        $this->name = 'widget_field';
        $this->label = __('Widget Field', 'acf-widget_field');
        $this->category = 'relational';

        $this->defaults = array(
            'widget_id' => '',
        );

        // do not delete!
        parent::__construct();
    }

    /**
     *  render_field()
     *
     *  Create the HTML interface for your field
     *
     *  @param   $field (array) the $field being rendered
     *
     *  @type    action
     *  @since   3.6
     *  @date    23/01/13
     *
     *  @param   $field (array) the $field being edited
     *  @return  n/a
     */
    function render_field($field)
    {
        global $wp_registered_sidebars;
        global $wp_registered_widgets;

        $widgets = array();
        echo "<select name=\"" . esc_attr($field['name']) . "\">";
            echo "<option value=''>Please select ...</option>";
        foreach (wp_get_sidebars_widgets() as $sidebar_id => $sidebar) {
            if ($sidebar_id == 'wp_inactive_widgets') {
                echo "<optgroup label='Inactive Widgets'>";
            } else {
                echo "<optgroup label='{$wp_registered_sidebars[$sidebar_id]['name']}'>";
            }

            if (empty($sidebar)) {
                echo "<option disabled='disabled'>No widgets currently assigned</option>";
            }
            foreach ($sidebar as $widget_id) {
                $selected = ($wp_registered_widgets[$widget_id]['id'] == esc_attr($field['value'])) ? ' selected="selected"' : '';
                $title = $this->get_widget_instance_title($widget_id);
                echo "<option value='{$wp_registered_widgets[$widget_id]['id']}'{$selected}>{$title}</option>";
            }
            echo "</optgroup>";
        }
        echo "</select>";
    }

    /**
     *  format_value()
     *
     *  This filter is appied to the $value after it is loaded from the db and before it is returned to the template
     *
     *  @type    filter
     *  @since   3.6
     *  @date    23/01/13
     *
     *  @param   $value (mixed) the value which was loaded from the database
     *  @param   $post_id (mixed) the $post_id from which the value was loaded
     *  @param   $field (array) the field array holding all the field options
     *
     *  @return  $value (mixed) the modified value
     */
    function format_value($value, $post_id, $field)
    {
        // bail early if no value
        if (empty($value)) {
            return $value;
        }

        return $this->render_widget_instance($value);
    }

    /**
     * @param   $widget_id (string) the id of the widget instace to render.
     *
     * @return  (string) the rendered output
     */
    function get_widget_instance_title($widget_id)
    {
        global $wp_registered_widgets;

        $widget_instance = $wp_registered_widgets[$widget_id];

        switch ($widget_instance['classname']) {
        case 'widget_text':
            $settings = $widget_instance['callback'][0]->get_settings();
            if (!isset($settings[$widget_instance['params'][0]['number']]['title'])) {
                return $widget_instance['name'];
            }
            $instance_title = $settings[$widget_instance['params'][0]['number']]['title'];

            return "{$widget_instance['name']}: {$instance_title}";
        default:
            return $widget_instance['name'];
        }
    }

    /**
     * @param   $widget_id (string) the id of the widget instace to render.
     *
     * @return  (string) the rendered output
     */
    function render_widget_instance($widget_id)
    {
        global $wp_registered_widgets, $wp_registered_sidebars, $sidebars_widgets;

        if (!array_key_exists($widget_id, $wp_registered_widgets)) {
            echo 'No widget found with that id'; return;
        }

        foreach ($sidebars_widgets as $sidebar => $sidebar_widget) {
            foreach ($sidebar_widget as $widget) {
                if ($widget == $widget_id) {
                    $current_sidebar = $sidebar;
                }
            }
        }

        $presentation = (isset($current_sidebar)) ? $wp_registered_sidebars[$current_sidebar] : array(
            'name' => '', 
            'id' => '',
            'description' => '',
            'class' => '',
            'before_widget'=> '',
            'after_widget'=> '',
            'before_title'=> '',
            'after_title' => ''
        );

        // Clear formatting unless required
        if (!$format) { 
            $presentation['before_widget'] = '';
            $presentation['after_widget'] = '';
        }

        $params = array_merge(
            array(array_merge($presentation, array('widget_id' => $widget_id, 'widget_name' => $wp_registered_widgets[$widget_id]['name']))),
            (array) $wp_registered_widgets[$widget_id]['params']
        );

        // Substitute HTML id and class attributes into before_widget
        $classname_ = '';
        foreach ((array) $wp_registered_widgets[$widget_id]['classname'] as $cn) {
            if (is_string($cn)) {
                $classname_ .= '_' . $cn;
            } elseif (is_object($cn)) {
                $classname_ .= '_' . get_class($cn);
            }
        }
        $classname_ = ltrim($classname_, '_');
        $params[0]['before_widget'] = sprintf($params[0]['before_widget'], $widget_id, $classname_);

        $params = apply_filters('dynamic_sidebar_params', $params); // doesnt't add/minus from data

        $callback = $wp_registered_widgets[$widget_id]['callback'];

        $output = '';
        if (is_callable($callback)) {
            ob_start();
            call_user_func_array($callback, $params);
            $output = ob_get_contents();
            ob_end_clean();
        }

        return $output;
    }

}

// create field
new acf_field_widget_field();
