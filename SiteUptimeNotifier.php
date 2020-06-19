<?php

declare(strict_types = 1);

namespace RonanMockett;

class SiteUptimeNotifier {
	private $file_path;
	private $sites;
	private $slack_webhook; // Fallback OR Default Webhook

	public function __construct() {
		$this->file_path = './example-siteList.json';
		$this->sites = json_decode( file_get_contents( $this->file_path ) );
		$this->slack_webhook = 'ENTER_SLACK_APP_WEBHOOK_HERE';
	}

	private function getSiteStatus( string $url ) : int {
		// create a new cURL resource
		$ch = curl_init();
		// set URL and other appropriate options
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_HEADER, TRUE );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array("Cache-Control: no-cache") );
		curl_setopt( $ch, CURLOPT_FRESH_CONNECT, TRUE );
		curl_setopt( $ch, CURLOPT_NOBODY, TRUE );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_TIMEOUT_MS, 3500 );
		# Send request
		curl_exec( $ch );
		$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		# Close resource, and free up system resources
		curl_close($ch);

		return (int) $response_code;
	}
	
	private function checkResponseCode( int $response_code, string $response_type ) : string {
		$status = 'active';
		$message = '';

		switch ( true ) {
			case $response_code === 0 :
				$status = "failed";
				$message = " - *Timed out / Refused Connection*";
				break;
			case $response_code >= 500 :
				$status = 'failed';
				$message = 'reported a server error. Your website may be down. *Response Code: ' . $response_code . '*';
				break;
			case $response_code !== 200 :
				$status = 'failed';
				$message = 'returned an unexpected response. *Response Code: ' . $response_code . '*';
				break;
		}

		if ( $response_type === 'status' ) {
			return $status;
		} else {
			return $message;
		}
	}

	private function updateSiteData( int $index, \stdClass $site, string $previousStatus, string $newStatus ) {
		$sites = $this->sites;

		$sites[$index]->currentStatus = $newStatus;
		if ( $newStatus === 'failed' ) {
			if ($previousStatus !== $newStatus)
				$sites[$index]->failed_timestamp = time();
		} else {
			$sites[$index]->failed_timestamp = null;
		}
	}

	private function getDowntime( int $failed_timestamp, int $current_timestamp ) : string {
		$difference = $current_timestamp - $failed_timestamp;
		$hours = floor( $difference/60/60 );
		$minutes = floor( $difference/60%60 );
		$seconds = $difference%60;
		$time = '';

		if ( $hours > 0 ) {
			$time .= $hours . ngettext( ' hour', ' hours', (int) $hours );
		}

		if ( $minutes > 0 ) {
			$time .= $hours > 0 ? ' ' : '';
			$time .= $minutes . ngettext( ' minute', ' minutes', (int) $minutes );
		}

		if ( $seconds > 0 ) {
			$time .= ( $minutes > 0 || $hours > 0 ) ? ' and ' : ' ';
			$time .= $seconds . ngettext( ' second', ' seconds', (int) $seconds );
		} else {
			$time .= ' and 0 seconds';
		}

		return '*' . $time . '*';
	}

	private function report( \stdClass $site, int $response_code, string $previousStatus, string $newStatus ) {
		$webhook = $site->channel_webhook ?? $this->slack_webhook;

		// If site was previously down but is now up then notify slack that it is back up and report downtime. 
		if ( $previousStatus === 'failed' ) {
			if ( $newStatus === 'active' ) {
				$payload =  array(
					"text" => '*ONLINE - <' . $site->url . '|' . $site->name . '>* is now back online. Your site may have been down for ' . $this->getDowntime( $site->failed_timestamp, time() )
				);
				$this->sendPayload( $webhook, $payload );
			}
			// Nothing new to report.
			return;
		}

		// Site is down, report to slack.
		if ( $newStatus !== 'active' ) {
			$payload = array(
				"text" => '*ALERT - <' . $site->url . '|' . $site->name . '>* ' . $this->checkResponseCode( $response_code, 'message' )
			);
			$this->sendPayload( $webhook, $payload );
		}
	}

	private function sendPayload( string $webhook, array $payload ) {
		// create a new cURL resource
		$ch = curl_init();
		// set URL and other appropriate options
		curl_setopt( $ch, CURLOPT_URL, $webhook );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			"Content-type:  application/json"
		) );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($payload) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		// Send request.
		curl_exec($ch);
		// Close resource, and free up system resources
		curl_close($ch);
	}

	public function run() {
		$sites = $this->sites;

		if ( empty($sites) || !is_array($sites) )
			return;

		foreach( $sites as $index => $site ) {
			if ( !$site->enabled ) continue;

			// Get site status
			$previousStatus = $site->currentStatus;
			$response_code = $this->getSiteStatus( $site->url );
			$newStatus = $this->checkResponseCode( $response_code, 'status' );

			// Report to Slack
			$this->report($site, $response_code, $previousStatus, $newStatus);

			// Update site data
			$this->updateSiteData( $index, $site, $previousStatus, $newStatus );
		}

		// Update example-siteList.json
		file_put_contents( $this->file_path, json_encode($sites) );
	}
}

$siteChecker = new SiteUptimeNotifier();
$siteChecker->run();