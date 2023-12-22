<div class="rise_container my-3">
    <div class="rise_container_content">
        <div class="row align-items-baseline">
			<?php
			foreach ( $posts as $post ) {
				if ( $post->post_status == 'publish' ) {
					$shortDescription = get_post_meta( $post->ID, 'rise_room_shortDescription', true );
					?>
                    <div class="col-sm-12 col-md-6">
                        <div class="rise_room">
                            <div class="rise_room_thumbnail">
								<?php echo get_the_post_thumbnail( intval( $post->ID ) ) ?>
                            </div>
                            <div class="rise_room_title">
                                <h4>
                                    <a href="<?php echo get_permalink( intval( $post->ID ) ) ?>"><?php echo wp_kses( $post->post_title, array() ) ?></a>
                                </h4>
                            </div>
							<?php
							if ( ! empty( $shortDescription ) ) {
								?>
                                <div class="rise_room_description">
                                    <p><?php echo wp_kses( $shortDescription, array() ) ?></p>
                                </div>
								<?php
							}
							?>
                        </div>
                    </div>
					<?php
				}
			}
			?>
        </div>
    </div>
</div>