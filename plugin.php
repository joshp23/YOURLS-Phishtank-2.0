<?php
/*
Plugin Name: Phishtank-2.0
Plugin URI: https://github.com/joshp23/YOURLS-Phishtank-2.0
Description: Prevent shortening malware URLs using phishtank API
Version: 2.0
Author: Josh Panter
Author URI: https://unfettered.net/
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

// Add the admin page
yourls_add_action( 'plugins_loaded', 'phishtank_add_page' );

function phishtank_add_page() {
        yourls_register_plugin_page( 'phishtank', 'Phishtank', 'phishtank_do_page' );
}

// Display admin page
function phishtank_do_page() {

	// Check if a form was submitted
	if( isset( $_POST['phishtank_api_key'] ) ) {
		// Check nonce
		yourls_verify_nonce( 'phishtank' );
		
		// Process form - update option in database
		yourls_update_option( 'phishtank_api_key', $_POST['phishtank_api_key'] );
		if(isset($_POST['phishtank_soft'])) yourls_update_option( 'phishtank_soft', $_POST['phishtank_soft'] );
	}

	// Get value from database
	$phishtank_api_key = yourls_get_option( 'phishtank_api_key' );
	$phishtank_soft = yourls_get_option( 'phishtank_soft' );
	
	if ($phishtank_soft == "1") $ck = 'checked';
	if ($phishtank_soft == "0") $ck = null;

	// Create nonce
	$nonce = yourls_create_nonce( 'phishtank' );

	echo <<<HTML
		<h2>Phishtank API Key</h2>
		<p>You can use Phistank's API without a key, but you will get a higher rate limit if you use one. <a href="https://www.phishtank.com/" target="_blank">Click here</a> to learn more, or to register this application and obtain a key.</p>
		<form method="post">
		<input type="hidden" name="nonce" value="$nonce" />
		<p><label for="phishtank_api_key">Your Key  </label> <input type="text" size=60 id="phishtank_api_key" name="phishtank_api_key" value="$phishtank_api_key" /></p>

		<h2>Preserve old links?</h2>
		<p>Old links will be re-checked every time that they are clicked. You can decide how to handle old links that are found to be dirty on a re-check here. <b>Default is to delete them.</b> Check below to preserve these dirty old links, and display a warning page on redirect isntead.</p>
		
		<div class="checkbox">
		  <label>
		    <input type="hidden" name="phishtank_soft" value="0" />
		    <input name="phishtank_soft" type="checkbox" value="1" $ck > Soft on Spam?
		  </label>
		</div>
		<p><input type="submit" value="Submit" /></p>
		</form>
HTML;
}

// Check phishtank when a new link is added
yourls_add_filter( 'shunt_add_new_link', 'phishtank_check_add' );
function phishtank_check_add( $false, $url ) {
    // Sanitize URL and make sure there's a protocol
    $url = yourls_sanitize_url( $url );

    // only check for 'http(s)'
    if( !in_array( yourls_get_protocol( $url ), array( 'http://', 'https://' ) ) )
        return $false;
    
    // is the url malformed?
    if ( phishtank_is_blacklisted( $url ) === yourls_apply_filter( 'phishtank_malformed', 'malformed' ) ) {
		return array(
			'status' => 'fail',
			'code'   => 'error:nourl',
			'message' => yourls__( 'Missing or malformed URL' ),
			'errorCode' => '400',
		);
    }
	
    // is the url blacklisted?
    if ( phishtank_is_blacklisted( $url ) != false ) {
		return array(
			'status' => 'fail',
			'code'   => 'error:spam',
			'message' => 'This domain is blacklisted',
			'errorCode' => '403',
		);
    }
	
	// All clear, not interrupting the normal flow of events
	return $false;
}


// Re-Check phishtank on redirection
yourls_add_action( 'redirect_shorturl', 'phishtank_check_redirect' );
function phishtank_check_redirect( $url, $keyword = false ) {
	
	if( is_array( $url ) && $keyword == false ) {
		$keyword = $url[1];
		$url = $url[0];
	}
	
	// Check when the link was added
	// If shorturl is fresh (ie probably clicked more often?) check once every 15 times, otherwise once every 5 times
	// Define fresh = 3 days = 259200 secondes
	// TODO: when there's a shorturl_meta table, store last check date to allow checking every 2 or 3 days
	$now  = date( 'U' );
	$then = date( 'U', strtotime( yourls_get_keyword_timestamp( $keyword ) ) );
	$chances = ( ( $now - $then ) > 259200 ? 15 : 5 );
	
	if( $chances == mt_rand( 1, $chances ) ) {
		if( phishtank_is_blacklisted( $url ) != false ) {
			$phishtank_soft = yourls_get_option( 'phishtank_soft' );
			if( $phishtank_soft == "1" ) display_phlagpage();
			if( $phishtank_soft == "0" ) {
				// Delete link & die
				yourls_delete_link_by_keyword( $keyword );
				yourls_die( 'This domain has been blacklisted. This short URL has been deleted from our record.', 'Domain blacklisted', '403' );
			} 
		}
	}
	// Nothing, move along

}
// Soft on Spam ~ interstitial warning
function display_phlagpage($keyword) {

        $title = yourls_get_keyword_title( $keyword );
        $url   = yourls_get_keyword_longurl( $keyword );
        $base  = YOURLS_SITE;
	$img   = yourls_plugin_url( dirname( __FILE__ ).'/assets/caution.png' );
	$css   = yourls_plugin_url( dirname( __FILE__ ).'/assets/bootstrap.min.css');

	$vars = array();
		$vars['keyword'] = $keyword;
		$vars['reason'] = $reason;
		$vars['title'] = $title;
		$vars['url'] = $url;
		$vars['base'] = $base;
		$vars['img'] = $img;
		$vars['css'] = $css;

	$notice = file_get_contents( dirname( __FILE__ ) . '/danger.php' );
	// Replace all %stuff% in the notice with variable $stuff
	$notice = preg_replace_callback( '/%([^%]+)?%/', function( $match ) use( $vars ) { return $vars[ $match[1] ]; }, $danger );

	echo $danger;

	die();
}

// Is the link spam? true / false 
function phishtank_is_blacklisted( $url ) {
	$parsed = parse_url( $url );
	
	if( !isset( $parsed['host'] ) )
		return yourls_apply_filter( 'phishtank_malformed', 'malformed' );
	
	// Remove www. from domain (but not from www.com)
	$parsed['host'] = preg_replace( '/^www\.(.+\.)/i', '$1', $parsed['host'] );
	
	// Phishtank API
	$phishtank_api_key = yourls_get_option( 'phishtank_api_key' );

        $API="http://checkurl.phishtank.com/checkurl/";
        $url_64=base64_encode($url);

        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt ($ch, CURLOPT_POST, TRUE);
        curl_setopt ($ch, CURLOPT_USERAGENT, "x90");
        curl_setopt ($ch, CURLOPT_POSTFIELDS, "format=xml&app_key=$phishtank_api_key&url=$url_64");
        curl_setopt ($ch, CURLOPT_URL, "$API");
        $result = curl_exec($ch);
        curl_close($ch);

        if (preg_match("/phish_detail_page/",$result)) {
			return yourls_apply_filter( 'phishtank_blacklisted', true );
	}
	
	// All clear, probably not spam
	return yourls_apply_filter( 'phishtank_clean', false );
}
