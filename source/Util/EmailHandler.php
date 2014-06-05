<?php
/**
 * @author Justin Foell
 */

class WPAM_Util_EmailHandler {

	public function mailAffiliate( $address, $subject, $message ) {
		//#61 override email & name
		add_filter( 'wp_mail_from', array( $this, 'filterMailAddress' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'filterMailName' ) );
		wp_mail( $address, $subject, $message );
		remove_filter( 'wp_mail_from', array( $this, 'filterMailAddress' ) );
		remove_filter( 'wp_mail_from_name', array( $this, 'filterMailName' ) );		
	}

	public function mailNewAffiliate( $user_id, $user_pass ) {
		//#62 piggyback onto the username / password email
		add_filter( 'wp_mail', array( $this, 'filterMail' ) );
		add_filter( 'wp_mail_from', array( $this, 'filterMailAddress' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'filterMailName' ) );
		wp_new_user_notification( $user_id, $user_pass );
		remove_filter( 'wp_mail', array( $this, 'filterMail' ) );
		remove_filter( 'wp_mail_from', array( $this, 'filterMailAddress' ) );
		remove_filter( 'wp_mail_from_name', array( $this, 'filterMailName' ) );
	}
	
	public function filterMail( $args ) {
		//only add to the user/password email
		if( strpos( $args['subject'], __( 'Your username and password' ) ) === FALSE )
			return $args;
		
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$args['subject'] = sprintf( __( '[%s] Your username and password for Affiliate Manager', 'wpam' ), $blogname );
		$args['message'] = sprintf( __( 'New affiliate registration for %s: has been approved!', 'wpam' ), $blogname ) . "\r\n\r\n" . $args['message'];
		return $args;
	}

	public function filterMailAddress( $address ) {
		$addrOverride = get_option( WPAM_PluginConfig::$EmailAddressOption );
		if( ! empty( $addrOverride ) )
			return $addrOverride;
		
		return $address;
	}

	public function filterMailName( $name ) {
		$nameOverride = get_option( WPAM_PluginConfig::$EmailNameOption );
		if( ! empty( $nameOverride ) )
			return $nameOverride;
		
		return $name;
	}	

}
