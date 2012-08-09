<?php
	$hostname = strtolower($_SERVER['SERVER_ADDR']);

	$cache_key = 'damnfowarder_txt_' . MD5($hostname);

	if(!apc_exists($cache_key)) {
		$result = dns_get_record($hostname, DNS_TXT);

		// if include www
		if(strpos($hostname, 'www.') === 0 && count($result) == 0) {
			$result = dns_get_record(substr($hostname, 4), DNS_TXT);
		}

		if(count($result)>0) {
			if(strpos($result[0]['txt'], 'damnforwarder ') === 0) {
				$forward_to = explode(' ', $result[0]['txt']);

				array_shift($forward_to);

				apc_store($cache_key, $forward_to, $result[0]['ttl']);
			}
		}
	} else {
		$forward_to = apc_fetch($cache_key);
	}

	if(isset($forward_to)) {
		$options = isset($forward_to[1]) ? intval($forward_to[1]) : 307;
		switch ($options) {
			case 301:
				header('HTTP/1.1 301 Moved Permanently');
				break;
			case 307:
			default:
				header('HTTP/1.1 307 Temporary Redirect');
				break;
		}

		header("Location: {$forward_to[0]}");
	} else {
?>
<h1>Damn Forwarder</h1>

<p>Simple URL forwarding.</p>

<h2>Usage</h2>
<pre>
yourdomain.com&#09;IN&#09;A&#09;205.134.228.144
yourdomain.com&#09;IN&#09;TXT&#09;"damnforwarder http(s)://url.to/redirect [options]"
</pre>


<p>Note: If the hostname includes "www", the root domain's TXT record will also be scanned.</p>

<h2>Options</h2>
<p>-p, --permanent &nbsp;&nbsp;&nbsp;&nbsp; Trigger a 301 redirection instead of 307 (default) </p>
<?php
	}