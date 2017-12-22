<?php
/**
 * Plugin Name:       Inku Committees
 * Description:       Plugin for handling officials and committees.
 * Version:           0.0.1
 * Author:            Joonas Palosuo
 * License:           WTFPL
 * Text Domain:       inku-committees
 * Domain Path:				/languages
 */

class Inku_Committees_Plugin {
  /*
   * Initialization method
   */
  public function __construct() {
    // Require user to be admin
    if (is_admin()) {
			add_action( 'admin_menu', array($this, 'register_admin_menu' ));  // Create admin menu page
			register_activation_hook( __FILE__ , 'wp_csv_to_db_activate'); // Add settings on plugin activation
    }
  }

  public function register_admin_menu() {
    add_menu_page(
      __('Committee management','inku-committees'),  // Page title
      __('Committee management','inku-committees'),  // Menu title
      'manage_options',
      'committee-management',
      array($this, 'render_committee_page'));

    add_menu_page(
      __('Position management','inku-committees'),
      __('Position management','inku-committees'),
      'manage_options',
      'position-management',
      array($this, 'render_position_page'));

    add_menu_page(
      __('Filler management','inku-committees'),
      __('Filler management','inku-committees'),
      'manage_options',
      'filler-management',
      array($this, 'render_filler_page'));
  }

  public function render_filler_page() {
    $this->render_page('filler-form', array());
  }

  public function render_committee_page() {
    $this->render_page('data-form', array('table_name' => 'Committees'));
  }
  
  public function render_position_page() {
    $this->render_page('data-form', array('table_name' => 'Positions'));
  }

  private function render_page($template, $data) {
    echo $this->get_template_html( $template, $data );
  }

  /**
   * Renders the contents of the given template to a string and returns it.
   *
   * @param string $template_name The name of the template to render (without .php)
   * @param array  $attributes    The PHP variables for the template
   *
   * @return string               The contents of the template.
   */
  private function get_template_html( $template_name, $attributes = null ) {
    if ( ! $attributes ) {
      $attributes = array();
    }

    ob_start();
    require( 'templates/' . $template_name . '.php');
    $html = ob_get_contents();
    ob_end_clean();

    return $html;
  }
}

$inku_committees = new Inku_Committees_Plugin();


