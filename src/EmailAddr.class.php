<?php
class EmailAddr
{
	public $name;
	public $data;

	public function __construct($name, $data)
	{
		$this->name = $name;
		$this->data = $data;
		if(empty($data["folder"]))
		{
			$folder = $name;
		} else
		{
			$folder = $data["folder"];
		}
		if(empty($data["dontend"]))
		{
			$this->data["dontend"] = false;
		}
		if(empty($data["auth"]))
		{
			$this->data["auth"] = false;
		}
		$folder = str_replace("@", "__", strtolower($folder));
		$chars = range("a","z");
		for($i=0;$i<10;$i++)
		{
			array_push($chars,$i);
		}
		array_push($chars,"-");
		$arr = str_split($folder);
		$folder = "";
		foreach($arr as $char)
		{
			if(in_array($char, $chars))
			{
				$folder.=$char;
			} else
			{
				$folder.="_";
			}
		}
		$this->data["folder"] = $folder;
	}

	public function storeEmail($data)
	{
		$add = "";
		if(!file_exists($this->data["folder"]))
		{
			mkdir($this->data["folder"]);
		}
		while(file_exists($this->data["folder"]."/".time().$add.".txt"))
		{
			if($add == "")
			{
				$add = "-0";
			} else
			{
				$add = ($add * -1 + 1);
			}
		}
		file_put_contents($this->data["folder"]."/".time().$add.".txt",$data);
	}

	public static function find($mail)
	{
		global $addrs;
		foreach($addrs as $addr)
		{
			if($addr->name == $mail)
			{
				return $addr;
			}
		}
		return null;
	}
}
?>