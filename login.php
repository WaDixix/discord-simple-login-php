<?php

session_start();

$configuration = [
	"tokenURL" => "https://discordapp.com/api/oauth2/token",
	"apiURLBase" => "https://discordapp.com/api/users/@me",
	"OAUTH2_CLIENT_ID" => "XXXXXXXXXXXXXXXX",
	"OAUTH2_CLIENT_SECRET" => "XXXXXXXXXXXXXXXX",
	"RETURN_URL" => "https://example.com/login.php",
	"scope" => ["identify", "email"] // https://discord.com/developers/docs/topics/oauth2#shared-resources-oauth2-scopes
];

if (isset($_GET["login"]))
{
	$_SESSION["state"] = bin2hex(random_bytes(32));

	$params = array(
		"client_id" => $configuration["OAUTH2_CLIENT_ID"],
		"redirect_uri" => $configuration["RETURN_URL"],
		"response_type" => "code",
		"scope" => implode(" ", $configuration["scope"]),
		"state" => $_SESSION["state"]
	);

	header("Location: https://discordapp.com/api/oauth2/authorize?" . http_build_query($params));
	die();
}

if (isset($_GET["code"], $_GET["state"]))
{

	if (empty($_SESSION["state"]) || $_SESSION["state"] !== $_GET["state"])
	{
		header("Location: ?login");
		die();
	}

	unset($_SESSION["state"]);

	$token = HTTP_POST($configuration["tokenURL"], [
		"grant_type" => "authorization_code",
		"client_id" => $configuration["OAUTH2_CLIENT_ID"],
		"client_secret" => $configuration["OAUTH2_CLIENT_SECRET"],
		"redirect_uri" => $configuration["RETURN_URL"],
		"code" => $_GET["code"]
	]);

	$token = json_decode($token, true)["access_token"];

	$user = HTTP_POST($configuration["apiURLBase"], NULL, $token);

	$user = json_decode($user, true);

	foreach ($user as $key => $value)
	{
		echo sprintf("<p><b>%s</b>: %s </p>", $key, htmlspecialchars($value));
	}
}
else
{
	header("Location: ?login");
	die();
}

function HTTP_POST($url, $post = NULL, $token = NULL)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);

	if (isset($post))
	{
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
	}

	if (isset($token))
	{
		$headers[] = "Accept: application/json";
		$headers[] = "Authorization: Bearer $token";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$output = curl_exec($ch);
	curl_close($ch);

	return $output;
}