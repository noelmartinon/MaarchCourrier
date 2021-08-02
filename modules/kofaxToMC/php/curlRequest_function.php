<?php
function curlRequest($body = null, $url, $auth, $method)
{
	$req = [
		'url' => $url,
		'method' => $method,
	];
	if ($body !== null) {
		$resources = [];
		$resI = 0;
		foreach ($body as $k => $v) {
			if (is_resource($body[$k])) {
				$resources["--res#{$resI}"] = $v;
				$body[$k] = "--res#{$resI}";
				$resI++;
			}
		}
		$req['body'] = json_encode($body);
		$parts = preg_split('/(--res#\d+)/', $req['body'], 0, PREG_SPLIT_DELIM_CAPTURE);
		$stream = fopen('php://temp', 'w+');
		foreach ($parts as $part) {
			if (isset($resources[$part])) {
				stream_copy_to_stream($resources[$part], $stream);
			} else {
				fwrite($stream, $part);
			}
		}
		rewind($stream);
		$req['body'] = stream_get_contents($stream);
		fclose($stream);

		$req['headers'] = ['content-type:application/json'];
	}
	if (!empty($auth)) {
		$req['basicAuth'] = $auth;
	}
	$res = execSimple($req);
	if (!empty($res['errors'])) {
		return ['errors' => $res['errors']];
	}
	return $res['response'];
}

function execSimple(array $args)
{
	$opts = [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_SSL_VERIFYPEER => false];

	//Headers
	if (!empty($args['headers'])) {
		$opts[CURLOPT_HTTPHEADER] = $args['headers'];
	}
	//Auth
	if (!empty($args['basicAuth'])) {
		$opts[CURLOPT_HTTPHEADER][] = 'Authorization: Basic ' . base64_encode($args['basicAuth']['user']. ':' .$args['basicAuth']['password']);
	}
	if (!empty($args['bearerAuth'])) {
		$opts[CURLOPT_HTTPHEADER][] = 'Authorization: Bearer ' . $args['bearerAuth']['token'];
	}

	//QueryParams
	if (!empty($args['queryParams'])) {
		$args['url'] .= '?';
		$i = 0;
		foreach ($args['queryParams'] as $queryKey => $queryParam) {
			if ($i > 0) {
				$args['url'] .= '&';
			}
			$args['url'] .= "{$queryKey}={$queryParam}";
			++$i;
		}
	}

	//Body
	if (!empty($args['body'])) {
		$opts[CURLOPT_POSTFIELDS] = $args['body'];
	}
	//Method
	if ($args['method'] == 'POST') {
		$opts[CURLOPT_POST] = true;
	} elseif ($args['method'] == 'PUT' || $args['method'] == 'PATCH' || $args['method'] == 'DELETE') {
		$opts[CURLOPT_CUSTOMREQUEST] = $args['method'];
	}

	//Url
	$opts[CURLOPT_URL] = $args['url'];

	$curl = curl_init();
	curl_setopt_array($curl, $opts);
	$rawResponse = curl_exec($curl);
	$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
	$errors = curl_error($curl);
	curl_close($curl);

	$headers = substr($rawResponse, 0, $headerSize);
	$headers = explode("\r\n", $headers);
	$response = substr($rawResponse, $headerSize);

	return ['code' => $code, 'headers' => $headers, 'response' => json_decode($response), 'errors' => $errors];
}
