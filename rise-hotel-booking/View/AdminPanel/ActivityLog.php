<div class="wrap">
    <div class="row">
        <div class="col-12 mb-3">
            <h1 class="wp-heading-inline">
				<?php echo wp_kses( $page_title, array() ) ?>
            </h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <table id="rise-activity-log-table">
                <thead>
                <tr>
                    <th><?php _e( 'ID', 'rise-hotel-booking' ) ?></th>
                    <th><?php _e( 'Activity Type', 'rise-hotel-booking' ) ?></th>
                    <th><?php _e( 'Details', 'rise-hotel-booking' ) ?></th>
                    <th><?php _e( 'Date', 'rise-hotel-booking' ) ?></th>
                </tr>
                </thead>
            </table>
        </div>
    </div>
</div>