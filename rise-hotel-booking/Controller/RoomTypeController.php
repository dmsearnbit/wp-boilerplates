<?php

class RoomTypeController {
	public function __construct() {
		// register room type taxonomy
		add_action( 'init', array( $this, 'createRoomTypeTaxonomy' ) );

		// add our custom fields in room type taxonomy -- add new
		add_action( 'rise_room_type_add_form_fields', array( $this, 'addFieldsRoomTypeNew' ) );

		// add our custom fields in room type taxonomy -- edit
		add_action( 'rise_room_type_edit_form_fields', array( $this, 'addFieldsRoomTypeEdit' ) );

		// save custom fields in room type taxonomy
		add_action( 'created_rise_room_type', array( $this, 'saveRoomTypeCustomFields' ) );
		add_action( 'edited_rise_room_type', array( $this, 'saveRoomTypeCustomFields' ) );

		// add custom fields' columns to room type taxonomy table
		add_filter( 'manage_edit-rise_room_type_columns', array( $this, 'addRoomTypeColumns' ) );
		add_action( 'manage_rise_room_type_custom_column', array( $this, 'addRoomTypeColumnContent' ), 10, 3 );

		/*
		check quickEditRoomType function for the explanation why I removed this
		add_action( 'quick_edit_custom_box', array( $this, 'quickEditRoomType' ), 10, 3 );
		*/
	}


	/**
	 * <p><b>create taxonomy for room type</b></p>
	 */
	public function createRoomTypeTaxonomy() {
		$args = [
			'label'  => __( 'Room Types', 'rise-hotel-booking' ),
			'labels' => [
				'menu_name'                  => __( 'Room Types', 'rise-hotel-booking' ),
				'all_items'                  => __( 'All Room Types', 'rise-hotel-booking' ),
				'edit_item'                  => __( 'Edit Room Type', 'rise-hotel-booking' ),
				'view_item'                  => __( 'View Room Type', 'rise-hotel-booking' ),
				'update_item'                => __( 'Update Room Type', 'rise-hotel-booking' ),
				'add_new_item'               => __( 'Add new Room Type', 'rise-hotel-booking' ),
				'new_item'                   => __( 'New Room Type', 'rise-hotel-booking' ),
				'parent_item'                => __( 'Parent Room Type', 'rise-hotel-booking' ),
				'parent_item_colon'          => __( 'Parent Room Type', 'rise-hotel-booking' ),
				'search_items'               => __( 'Search Room Types', 'rise-hotel-booking' ),
				'popular_items'              => __( 'Popular Room Types', 'rise-hotel-booking' ),
				'separate_items_with_commas' => __( 'Separate Room Types with commas', 'rise-hotel-booking' ),
				'add_or_remove_items'        => __( 'Add or remove Room Types', 'rise-hotel-booking' ),
				'choose_from_most_used'      => __( 'Choose most used Room Types', 'rise-hotel-booking' ),
				'not_found'                  => __( 'No Room Types found', 'rise-hotel-booking' ),
				'name'                       => __( 'Room Types', 'rise-hotel-booking' ),
				'singular_name'              => __( 'Room Type', 'rise-hotel-booking' ),
			],

			'public'               => true,
			'show_ui'              => true,
			'show_in_menu'         => true,
			'show_in_nav_menus'    => true,
			'show_tagcloud'        => true,
			'show_in_quick_edit'   => true,
			'show_admin_column'    => false,
			'show_in_rest'         => true,
			'hierarchical'         => true,
			'query_var'            => true,
			'sort'                 => false,
			'rewrite_no_front'     => false,
			'rewrite_hierarchical' => false,
			'rewrite'              => true
		];

		register_taxonomy( 'rise_room_type', 'rise_room', $args );
	}


	/**
	 * <p><b>define custom fields for adding new room type taxonomy</b></p>
	 */
	public function addFieldsRoomTypeNew() {
		echo '<div class="form-field">
        <label for="rise_room_type_capacity">'.__("Room Capacity", "rise-hotel-booking").'</label>
        <input type="number" name="rise_room_type_capacity" id="rise_room_type_capacity" min="1" />
        </div>';
	}


	/**
	 * <p><b>define custom fields for editing room type taxonomy</b></p>
	 *
	 * @param $term
	 */
	public function addFieldsRoomTypeEdit( $term ) {
		$value = get_term_meta( $term->term_id, 'rise_room_type_capacity', true );

		echo '<tr class="form-field">
        <th>
            <label for="rise_room_type_capacity">'.__("Room Capacity", "rise-hotel-booking").'</label>
        </th>
        <td>
            <input name="rise_room_type_capacity" id="rise_room_type_capacity" type="text" value="' . esc_attr( $value ) . '" />
        </td>
        </tr>';
	}


	/**
	 * <p><b>save custom field data in room type taxonomy</b></p>
	 *
	 * @param $term_id
	 */
	public function saveRoomTypeCustomFields( $term_id ) {
		update_term_meta(
			$term_id,
			'rise_room_type_capacity',
			sanitize_text_field( $_POST['rise_room_type_capacity'] )
		);
	}


	/**
	 * <p><b>adding custom column to display our custom data in room type taxonomy</b></p>
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function addRoomTypeColumns( $columns ) {
		$columns['capacity'] = __( 'Capacity', 'rise-hotel-booking' );

		return $columns;
	}


	/**
	 * <p><b>fill the column we just created in addRoomTypeColumns function above</b></p>
	 *
	 * @param $content
	 * @param $column_name
	 * @param $term_id
	 *
	 * @return mixed
	 */
	public function addRoomTypeColumnContent( $content, $column_name, $term_id ) {
		$capacity = get_term_meta( $term_id, 'rise_room_type_capacity', true );

		switch ( $column_name ) {
			case 'capacity':
				echo wp_kses( $capacity, array() );
				break;
		}

		return $content;
	}


	/*
	 * This part has been removed because we can't pre-fill the input in quick edit
	 * will be added again when we find a way to do so
	 * -----------------------------------------------------------------------------

	// create quick edit input for room type taxonomy
	public function quickEditRoomType( $column_name, $screen, $taxonomyName ) {
		$taxonomy = get_taxonomy( $taxonomyName );
		if ( $screen != 'rise_room' && $column_name != 'capacity' ) {
			return false;
		}
		?>
		<fieldset>
			<div class="inline-edit-col">
				<label>
					<span class="title"><?php _e( 'Capacity', 'rise-hotel-booking' ); ?></span>
					<span class="input-text-wrap"><input type="number" name="rise_room_type_capacity" class="ptitle"
														 min="1" value="<?php echo esc_attr($column_name) ?>"></span>
				</label>
			</div>
		</fieldset>
		<?php
	}
	*/
}