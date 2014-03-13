<?php

global $wpcom_api_key, $akismet_api_host, $akismet_api_port;

$wpcom_api_key    = defined( 'WPCOM_API_KEY' ) ? constant( 'WPCOM_API_KEY' ) : '';	
$akismet_api_host = Akismet::get_api_key() . '.rest.akismet.com';
$akismet_api_port = 80;

function akismet_test_mode() {
	_deprecated_function( __FUNCTION__, '3.0', 'Akismet::is_test_mode()' );
	
	return Akismet::is_test_mode();
}

function akismet_http_post($request, $host, $path, $port = 80, $ip=null) {
	_deprecated_function( __FUNCTION__, '3.0', 'Akismet::http_post()' );

	$path = str_ireplace( '/1.1/', '', $path );

	return Akismet::http_post( $request, $path, $ip ); 
}

function akismet_microtime() {
	_deprecated_function( __FUNCTION__, '3.0', 'Akismet::_get_microtime()' );
	
	return Akismet::_get_microtime();
}

function akismet_delete_old() {
	_deprecated_function( __FUNCTION__, '3.0', 'Akismet::delete_old_comments()' );

	return Akismet::delete_old_comments();
}

function akismet_delete_old_metadata() { 
	_deprecated_function( __FUNCTION__, '3.0', 'Akismet::delete_old_comments_meta()' );

	return Akismet::delete_old_comments_meta();
}

function akismet_check_db_comment( $id, $recheck_reason = 'recheck_queue' ) {
	_deprecated_function( __FUNCTION__, '3.0', 'Akismet::check_db_comment()' );
   
   	return Akismet::check_db_comment( $id, $recheck_reason );
}

function akismet_rightnow() {
	_deprecated_function( __FUNCTION__, '3.0', 'Akismet_Admin::rightnow_stats()' );
	
	if ( !class_exists( 'Akismet_Admin' ) )
		return false;
   
   	return Akismet_Admin::rightnow_stats();
}
