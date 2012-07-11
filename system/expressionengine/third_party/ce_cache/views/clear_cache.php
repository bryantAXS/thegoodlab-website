<?php
if ( $show_form ): //show the form

	//open form
	echo form_open( $action_url, '' );

	//driver name
	echo form_hidden( 'driver', $driver );

	echo '<p>' . lang( "{$module}_confirm_clear" ) . '</p>';

	//submit
	echo form_submit( array( 'name' => 'submit', 'value' => lang( "{$module}_confirm_clear_button" ), 'class' => 'submit' ) );

	//close form
	echo form_close();
else: //show the success message
	echo '<p>' . lang( "{$module}_clear_cache_success" ) . '</p>';
	echo '<p>' . $back_link . '</p>';
endif;
?>