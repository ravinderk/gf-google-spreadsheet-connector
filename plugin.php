<?php
/*
Plugin Name: Gravity Form: Google spreadsheet connector
Plugin URI:  http://ravinder.me
Description: This plugin will store gravity form entries to google spreadsheet
Version:     0.1
Author:      Ravinder Kumar
Author URI:  http://ravinder.me
License:     GPL2
 
Gravity Form: Google spreadsheet connector is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Gravity Form: Google spreadsheet connector is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with {Plugin Name}. If not, see {License URI}.
*/

Class RK_Google_Spreadsheet_Connector{

	/**
	 * @var RK_Google_Spreadsheet_Connector The reference to *RK_Google_Spreadsheet_Connector* instance of this class
	 */
	private static $instance;

	/**
	 * Returns the *RK_Google_Spreadsheet_Connector* instance of this class.
	 *
	 * @return RK_Google_Spreadsheet_Connector The *RK_Google_Spreadsheet_Connector* instance.
	 */
	public static function getInstance(){
		if (null === static::$instance) {
			static::$instance = new static();
		}

		return static::$instance;
	}
	/**
	 * Protected constructor to prevent creating a new instance of the
	 * *RK_Google_Spreadsheet_Connector* via the `new` operator from outside of this class.
	 */
	protected function __construct(){}


	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *RK_Google_Spreadsheet_Connector* instance.
	 *
	 * @return void
	 */
	private function __clone() {}


	/**
	 * Private unserialize method to prevent unserializing of the *RK_Google_Spreadsheet_Connector*
	 * instance.
	 *
	 * @return void
	 */
	private function __wakeup(){}

	/**
	 * Initiate features
	 */
	public function run(){

		// Add setting.
		add_filter( 'gform_form_settings', array( $this, 'gf_plugin_setting' ), 10, 2 );

		// Store settings.
		add_filter( 'gform_pre_form_settings_save', array( $this, 'save_form_setting' ) );

		// Store entry to google spreasheet if form setting enable.
		add_action( 'gform_after_submission', array( $this, 'sync_entry' ), 10, 2 );
	}

	function gf_plugin_setting( $settings, $form ) {
		ob_start();
		?>
		<tr>
			<th><?php _e( 'Sync Form Entry', 'gravityforms' ) ?></th>
			<td>
				<input type="checkbox" id="storeEntryToGoogleSpreadsheet" name="storeEntryToGoogleSpreadsheet" value="1" <?php checked( rgar( $form, 'storeEntryToGoogleSpreadsheet' ) , 1 ); ?>/>
				<label for="storeEntryToGoogleSpreadsheet"><?php  _e( 'Store Entry to Google Spreadsheet', 'gravityforms' ); ?></label>
			</td>
		</tr>

		<tr>
			<th><?php _e( 'Webapp URL', 'gravityforms' ) ?></th>
			<td>
				<input type="text" id="googleSpreadsheetWebappURL" name="googleSpreadsheetWebappURL" value="<?php echo rgar( $form, 'googleSpreadsheetWebappURL' ); ?>"/>
				<label for="googleSpreadsheetWebappURL"><?php  _e( 'Google Spreadsheet Webapp URL', 'gravityforms' ); ?></label>
			</td>
		</tr>
		<?php
		$settings['Google Spreadsheet Settings']['storeEntryToGoogleSpreadsheet'] = ob_get_contents();
		ob_get_clean();

		return $settings;
	}


	function save_form_setting( $form ){
		$form['storeEntryToGoogleSpreadsheet'] = rgpost( 'storeEntryToGoogleSpreadsheet' );
		$form['googleSpreadsheetWebappURL'] = rgpost( 'googleSpreadsheetWebappURL' );

		return $form;
	}

	function sync_entry( $entry, $form ){
		//error_log( print_r( $entry, true )."\n", 3, WP_CONTENT_DIR.'/debug_new.log' );
		//error_log( print_r( $form, true )."\n", 3, WP_CONTENT_DIR.'/debug_new.log' );


		// TODO: Add Google spreadsheet URL validation.
		if(
			absint( $form['storeEntryToGoogleSpreadsheet'] )
			&& ! empty( $form['googleSpreadsheetWebappURL'] )
		) {
			// Get all form fields.
			$all_form_fields = $this->get_all_form_fields( absint( $form['id'] ) );

			// Put all the form fields (names and values) in this array
			$body = array();

			foreach ( $all_form_fields as $field ) {
				if( ! empty( $entry[ $field[0] ] ) ) {
					$body[ $field[1] ] = $entry[ $field[0] ];
				}
			}

			// Send the data to Google Spreadsheet via HTTP POST request.
			$response = wp_remote_post(
				$form['googleSpreadsheetWebappURL'],
				array(
					'method' => 'POST',
					'sslverify' => false,
					'body' => $body
				)
			);
		}
	}

	/**
	 * Get all gravity form field
	 * Credit: http://stackoverflow.com/a/20351786/2121471
	 * @param int $form_id Gravity form ID.
	 *
	 * @return array Gravity form field ids.
	 */
	function get_all_form_fields( $form_id ){
		$form = RGFormsModel::get_form_meta($form_id);
		$fields = array();

		if( is_array($form['fields'] ) ) {
			foreach( $form['fields'] as $field ) {
				if(
					isset( $field['inputs'] )
					&& is_array( $field['inputs'] )
				){

					foreach( $field['inputs'] as $input )
						$fields[] =  array($input['id'], GFCommon::get_label( $field, $input['id'] ) );
				} else if ( ! rgar($field, 'displayOnly' ) ){
					$fields[] =  array( $field['id'], GFCommon::get_label ($field ) );
				}
			}
		}

		return $fields;
	}
}

/**
 * Initiate feature
 */
RK_Google_Spreadsheet_Connector::getInstance()->run();