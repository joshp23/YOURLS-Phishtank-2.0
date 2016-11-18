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
		if(isset($_POST['phishtank_recheck'])) yourls_update_option( 'phishtank_recheck', $_POST['phishtank_recheck'] );
		if(isset($_POST['phishtank_soft'])) yourls_update_option( 'phishtank_soft', $_POST['phishtank_soft'] );
		if(isset($_POST['phishtank_cust_toggle'])) yourls_update_option( 'phishtank_cust_toggle', $_POST['phishtank_cust_toggle'] );
	}

	// Get values from database
	$phishtank_api_key = yourls_get_option( 'phishtank_api_key' );
	$phishtank_recheck = yourls_get_option( 'phishtank_recheck' );
	$phishtank_soft = yourls_get_option( 'phishtank_soft' );
	$phishtank_cust_toggle = yourls_get_option( 'phishtank_cust_toggle' );
	$phishtank_intercept = yourls_get_option( 'phishtank_intercept' );

	// set defaults
	if ($phishtank_recheck !== "false") {
		$rck_chk = 'checked';
		$vis_rck = 'inline';
		} else {
		$rck_chk = null;
		$vis_rck = 'none';
		}
	if ($phishtank_soft !== "false") { 
		$pl_ck = 'checked';
		$vis_pl = 'inline';
		} else {
		$pl_ck = null;
		$vis_pl = 'none';
		}
	if ($phishtank_cust_toggle !== "true") { 
		$url_chk = null;
		$vis_url = 'none';
		} else {
		$url_chk = 'checked';
		$vis_url = 'inline';
		}

	// Create nonce
	$nonce = yourls_create_nonce( 'phishtank' );

	echo <<<HTML
		<h2>Phishtank API Key</h2>
		<p>You can use Phistank's API without a key, but you will get a higher rate limit if you use one. <a href="https://www.phishtank.com/" target="_blank">Click here</a> to learn more, or to register this application and obtain a key.</p>
		<form method="post">
		<input type="hidden" name="nonce" value="$nonce" />
		<p><label for="phishtank_api_key">Your Key  </label> <input type="text" size=60 id="phishtank_api_key" name="phishtank_api_key" value="$phishtank_api_key" /></p>

		<h2>Redirect Rechecks: old links behavior</h2>
		<p>Old links can be re-checked every time that they are clicked. <b>Default behavior is to check them</b>.</p>

		<div class="checkbox">
		  <label>
		    <input type="hidden" name="phishtank_recheck" value="false" />
		    <input name="phishtank_recheck" type="checkbox" value="true" $rck_chk > Recheck old links?
		  </label>
		</div>

		<div style="display:$vis_rck;" >
			<p>You can decide to either preserve or delete links that fail a re-check here. <b>Default is to preserve them</b>, as many links tend not to stay blacklisted indefinately.</p>
		
			<div class="checkbox">
			  <label>
			    <input type="hidden" name="phishtank_soft" value="false" />
			    <input name="phishtank_soft" type="checkbox" value="true" $pl_ck > Preserve links & intercept on failed re-check?
			  </label>
			</div>

			<div class="checkbox" style="display:$vis_pl;">
			  <label>
				<input name="phishtank_cust_toggle" type="hidden" value="false" /><br>
				<input name="phishtank_cust_toggle" type="checkbox" value="true" $url_chk >Use Custom Intercept URL?
			  </label>
			</div>
			<div style="display:$vis_url;">
				<p>Setting the above option without setting this will fall back to default behavior.</p>
				<p><label for="phishtank_intercept">Intercept URL </label> <input type="text" size=40 id="phishtank_intercept" name="phishtank_intercept" value="$phishtank_intercept" /></p>
			</div>
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
	// Are we performing rechecks?
	$phishtank_recheck = yourls_get_option( 'phishtank_recheck' );
	if ($phishtank_recheck == "true" || $phishtank_recheck == null) {
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
				// We got a hit, do we delete or intercept?
				$phishtank_soft = yourls_get_option( 'phishtank_soft' );
				// Intercept by default
				if( $phishtank_soft !== "false" ) {
					// use default intercept page?
					$phishtank_cust_toggle = yourls_get_option( 'phishtank_cust_toggle' );
					$phishtank_intercept = yourls_get_option( 'phishtank_intercept' );
					if (($phishtank_cust_toggle == "true") && ($phishtank_intercept !== '')) {
						// How to pass keyword and url to redirect?
						yourls_redirect( $phishtank_intercept, 302 );
						die ();
					}
					// Or go to default flag intercept 
					display_phlagpage();
				} else {
				// Otherwise delete & die
				yourls_delete_link_by_keyword( $keyword );
				yourls_die( 'The page that you are trying to visit has been blacklisted. We have deleted this link from our record. Have a nice day', 'Domain blacklisted', '403' );
				} 
			}
		}
		// Nothing found, move along
	}
	// Re-check disabled, move along
}
// Soft on Spam ~ intercept warning
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

	$intercept = file_get_contents( dirname( __FILE__ ) . '/assets/intercept.php' );
	// Replace all %stuff% in the intercept with variable $stuff
	$intercept = preg_replace_callback( '/%([^%]+)?%/', function( $match ) use( $vars ) { return $vars[ $match[1] ]; }, $intercept );

	echo $intercept;

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
        curl_setopt ($ch, CURLOPT_USERAGENT, "YOURLS");
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
