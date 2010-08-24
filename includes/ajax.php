<?php

require_once("config.php");
require_once("mysql.php");

if(isset($_GET["str"]))
{
    $queryString = $_GET["str"];
    $length = $_GET["length"];
    $name = $_GET["myName"];
    
    $query="SELECT * FROM classes WHERE class_name LIKE '%".mysql_real_escape_string($queryString)."%' LIMIT 10";
    $result= mysql_query($query); // fire query
    
    echo "<table class='autocomplete_list' style='background-color: #ddddd9' width='".$length.";'>";
    while($row = mysql_fetch_row($result)) // fetch result
    {
	echo "<tr width='".$length.";'>";
	echo "<td style='color: #000000; text-decoration: none; cursor:pointer; font-size:10px; margin:0pt;' onclick='setValue(".$row[0].",\"".$name."\")' onkeydown='setValue(".$row[0].",".$name.")' width='".$length.";'>";
	
	echo $row[1]; // display result
	echo " (" . $row[0] . ")";
	echo "</td>";
	echo "</tr>";
    }
    echo "</table>";
}
else
{
    echo "";
}
?>
