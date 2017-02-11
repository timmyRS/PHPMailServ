<?php
if(empty($argv))
{
	die("ERROR: Please run this script using the `php run.php`-command in your shell.");
}
echo "Initiating...\n";
set_time_limit(0);
error_reporting(E_ALL);
require "src/Client.class.php";

$config=json_decode(file_get_contents("config.json"),true);
if($config==null)
{
	if(function_exists("json_last_error_msg"))
	{
		die("ERROR: Couldn't load config.json - ".json_last_error_msg()."\n");
	} else
	{
		die("ERROR: Couldn't load config.json - JSON error #".json_last_error()."\n");
	}
}

require "src/EmailAddr.class.php";
if(empty($config["users"]))
{
	die("ERROR: `users` is not defined in config.json\n");
}
if(empty($config["motd"]))
{
	$config["motd"] = "You've reached a PHPMailServ server. (Ohh no!)";
}
if(empty($config["hostname"]))
{
	$config["hostname"] = "localhost";
}
if(empty($config["size"]))
{
	$config["size"] = 10240000;
}
if(empty($config["sender_blacklist"]))
{
	$config["sender_blacklist"] = ["wildcard@localhost"];
}
$addrs = [];
foreach($config["users"] as $name => $data)
{
	$arr = explode("@", $name);
	if(count($arr) == 2)
	{
		$addr = new EmailAddr($name, $data);
		array_push($addrs, $addr);
	} else
	{
		echo "Skipped invalid user {$name}\n";
	}
}
unset($config["users"]);
echo "Loaded ".count($addrs)." addresses.\n";

$sSocket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Socket Error #1\n");
socket_set_option($sSocket, SOL_SOCKET, SO_REUSEADDR, 1) or die("Socket Error #2\n");
socket_bind($sSocket, "0.0.0.0",25) or die("Socket Error #3\n");
socket_listen($sSocket) or die("Socket Error #4\n");
echo "Listening on <0.0.0.0:25>...\n";

$connections = [];
while(true)
{
	$sReader = [$sSocket];
	foreach($connections as $x => $c)
	{
		if($c->closed)
		{
			unset($connections[$x]);
			$c->log("~ disconnected");
			continue;
		}
		array_push($sReader, $c->socket);
	}
	$null = null;
	$num_changed_sockets = socket_select($sReader, $null, $null, null);
	if($num_changed_sockets === false)
	{
		echo "ERROR: ".socket_strerror(socket_last_error())."\n";
		exit;
	}
	if($num_changed_sockets > 0)
	{
		if(in_array($sSocket, $sReader))
		{
			$c = new Client(socket_accept($sSocket));
			array_push($connections, $c);
			$c->log("~ connected");
			$c->send("220 ".$config["motd"]);
		} else
		{
			foreach($connections as $c)
			{
				if(in_array($c->socket, $sReader))
				{
					$data = socket_read($c->socket, 1);
					if($data)
					{
						if($data != "")
						{
							if($data == "\n")
							{
								$c->line = trim($c->line);
								if($c->line != "")
								{
									$c->log("> ".$c->line)->handle()->line="";
								}
							} else if($data != "\r")
							{
								$c->line .= $data;
							}
						}
					} else
					{
						$c->disconnect();
					}
				}
			}
		}
	}
}
?>
