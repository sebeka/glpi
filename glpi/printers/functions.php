<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Dol�ans, jmd@indepnet.net
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

 ----------------------------------------------------------------------
 LICENSE

This file is part of GLPI.

    GLPI is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    GLPI is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with GLPI; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
 

include ("_relpos.php");
// FUNCTIONS Printers 
//fonction imprimantes

function titlePrinters(){
           GLOBAL  $lang,$HTMLRel;
           
           echo "<div align='center'><table border='0'><tr><td>";

           echo "<img src=\"".$HTMLRel."pics/printer.png\" alt='".$lang["printers"][0]."' title='".$lang["printers"][0]."'></td><td><a  class='icon_consol' href=\"printers-info-form.php?new=1\"><b>".$lang["printers"][0]."</b></a>";


           echo "</td></tr></table></div>";
}



function searchFormPrinters($field="",$phrasetype= "",$contains="",$sort= "") {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang;

	$option["printer.name"]				= $lang["printers"][5];
	$option["printer.ID"]				= $lang["printers"][19];
	$option["glpi_dropdown_locations.name"]			= $lang["printers"][6];
	$option["glpi_type_printers.name"]				= $lang["printers"][9];
	$option["printer.serial"]			= $lang["printers"][10];
	$option["printer.otherserial"]		= $lang["printers"][11]	;
	$option["printer.comments"]			= $lang["printers"][12];
	$option["printer.contact"]			= $lang["printers"][8];
	$option["printer.contact_num"]		= $lang["printers"][7];
	$option["printer.date_mod"]			= $lang["printers"][16];
	

	echo "<form method='get' action=\"".$cfg_install["root"]."/printers/printers-search.php\">";
	echo "<div align='center'><table  width='750' class='tab_cadre'>";
	echo "<tr><th colspan='2'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";
	echo "<select name=\"field\" size='1'>";
        echo "<option value='all' ";
	if($field == "all") echo "selected";
	echo ">".$lang["search"][7]."</option>";
        reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=\"".$key."\""; 
		if($key == $field) echo "selected";
		echo ">". $val ."</option>\n";
	}
	echo "</select>&nbsp;";
	echo $lang["search"][1];
	echo "&nbsp;<select name='phrasetype' size='1' >";
	echo "<option value='contains'";
	if($phrasetype == "contains") echo "selected";
	echo ">".$lang["search"][2]."</option>";
	echo "<option value='exact'";
	if($phrasetype == "exact") echo "selected";
	echo ">".$lang["search"][3]."</option>";
	echo "</select>";
	echo "<input type='text' size='15' name=\"contains\" value=\"". $contains ."\" />";
	echo "&nbsp;";
	echo $lang["search"][4];
	echo "&nbsp;<select name='sort' size='1'>";
	reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=\"".$key."\"";
		if($key == $sort) echo "selected";
		echo ">".$val."</option>\n";
	}
	echo "</select> ";
	echo "</td><td width='80' align='center' class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</td></tr></table></div></form>";
}


function showPrintersList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start) {

	// Lists Printers

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang;

	$db = new DB;
	// Build query
	if($field=="all") {
		$where = " (";
		$fields = $db->list_fields("glpi_printers");
		$columns = $db->num_fields($fields);
		
		for ($i = 0; $i < $columns; $i++) {
			if($i != 0) {
				$where .= " OR ";
			}
			$coco = mysql_field_name($fields, $i);

			if($coco == "location") {
				$where .= " glpi_dropdown_locations.name LIKE '%".$contains."%'";
			}
			elseif($coco == "type") {
				$where .= " glpi_type_printers.name LIKE '%".$contains."%'";
			}
			else {
   				$where .= "printer.".$coco . " LIKE '%".$contains."%'";
			}
		}
		$where .= ")";
	}
	else {
		if ($phrasetype == "contains") {
			$where = "($field LIKE '%".$contains."%')";
		}
		else {
			$where = "($field LIKE '".$contains."')";
		}
	}

	if (!$start) {
		$start = 0;
	}
	if (!$order) {
		$order = "ASC";
	}
	$query = "select printer.ID from glpi_printers as printer LEFT JOIN glpi_dropdown_locations on printer.location=glpi_dropdown_locations.ID ";
	$query .= "LEFT JOIN glpi_type_printers on printer.type = glpi_type_printers.ID ";
	$query .= "where $where ORDER BY $sort $order";
	
//	echo $query;
	// Get it from database	
	if ($result = $db->query($query)) {
		$numrows = $db->numrows($result);

		// Limit the result, if no limit applies, use prior result
		if ($numrows>$cfg_features["list_limit"]) {
			$query_limit = $query ." LIMIT $start,".$cfg_features["list_limit"]." ";
			$result_limit = $db->query($query_limit);
			$numrows_limit = $db->numrows($result_limit);

		} else {
			$numrows_limit = $numrows;
			$result_limit = $result;
		}


		if ($numrows_limit>0) {
			// Produce headline
			echo "<center><table class='tab_cadre'><tr>";

			// Name
			echo "<th>";
			if ($sort=="name") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=printer.name&order=ASC&start=$start\">";
			echo $lang["printers"][5]."</a></th>";

			// Location			
			echo "<th>";
			if ($sort=="location") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=printer.location&order=ASC&start=$start\">";
			echo $lang["printers"][6]."</a></th>";

			// Type
			echo "<th>";
			if ($sort=="type") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=printer.type&order=ASC&start=$start\">";
			echo $lang["printers"][9]."</a></th>";

			// Last modified		
			echo "<th>";
			if ($sort=="date_mod") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=printer.date_mod&order=DESC&start=$start\">";
			echo $lang["printers"][16]."</a></th>";
	
			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");
				$printer = new Printer;
				$printer->getfromDB($ID);
				echo "<tr class='tab_bg_2'>";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/printers/printers-info-form.php?ID=$ID\">";
				echo $printer->fields["name"]." (".$printer->fields["ID"].")";
				echo "</a></b></td>";
				echo "<td>". getDropdownName("glpi_dropdown_locations",$printer->fields["location"]) ."</td>";
				echo "<td>". getDropdownName("glpi_type_printers",$printer->fields["type"]) ."</td>";
				echo "<td>".$printer->fields["date_mod"]."</td>";
				echo "</tr>";
			}

			// Close Table
			echo "</table></center>";

			// Pager
			$parameters="field=$field&phrasetype=$phrasetype&contains=$contains&sort=$sort&order=$order";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<center><b>".$lang["printers"][17]."</b></center>";
			echo "<hr noshade>";
		}
	}
}


function showPrintersForm ($target,$ID) {

	GLOBAL $cfg_install, $cfg_layout, $lang;

	$printer = new Printer;

	echo "<center><form method='post' name='form' action=\"$target\">";
	echo "<table class='tab_cadre' cellpadding='2'>";
	echo "<tr><th colspan='2'><b>";
	if (empty($ID)) {
		echo $lang["printers"][3].":";
		$printer->getEmpty();
	} else {
		$printer->getfromDB($ID);
		echo $lang["printers"][4]." ID $ID:";
	}		
	echo "</b></th></tr>";
	
	echo "<tr><td class='tab_bg_1' valign='top'>";

	echo "<table cellpadding='0' cellspacing='0' border='0'>\n";

	echo "<tr><td>".$lang["printers"][5].":	</td>";
	echo "<td><input type='text' name='name' value=\"".$printer->fields["name"]."\" size=10></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["printers"][6].": 	</td><td>";
		dropdownValue("glpi_dropdown_locations", "location", $printer->fields["location"]);
	echo "</td></tr>";

	echo "<tr><td>".$lang["printers"][7].":	</td>";
	echo "<td><input type='text' name='contact_num' value=\"".$printer->fields["contact_num"]."\" size='5'></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["printers"][8].":	</td>";
	echo "<td><input type='text' name='contact' size='12' value=\"".$printer->fields["contact"]."\"></td>";
	echo "</tr>";

	echo "</table>";

	echo "</td>\n";	
	echo "<td class='tab_bg_1' valign='top'>";

	echo "<table cellpadding='0' cellspacing='0' border='0'";

	echo "<tr><td>".$lang["printers"][9].": 	</td><td>";
		dropdownValue("glpi_type_printers", "type", $printer->fields["type"]);
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["printers"][10].":	</td>";
	echo "<td><input type='text' name='serial' size='12' value=\"".$printer->fields["serial"]."\"></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["printers"][11].":</td>";
	echo "<td><input type='text' size='12' name='otherserial' value=\"".$printer->fields["otherserial"]."\"></td>";
	echo "</tr>";

		echo "<tr><td>".$lang["printers"][18].": </td><td>";

		// serial interface?
		echo "<table border='0' cellpadding='2' cellspacing='0'><tr>";
		echo "<td>";
		if ($printer->fields["flags_serial"] == 1) {
			echo "<input type='checkbox' name='flags_serial' value='1' checked>";
		} else {
			echo "<input type='checkbox' name='flags_serial' value='1'>";
		}
		echo "</td><td>".$lang["printers"][14]."</td>";
		echo "</tr></table>";

		// parallel interface?
		echo "<table border='0' cellpadding='2' cellspacing='0'><tr>";
		echo "<td>";
		if ($printer->fields["flags_par"] == 1) {
			echo "<input type='checkbox' name='flags_par' value='1' checked>";
		} else {
			echo "<input type='checkbox' name='flags_par' value='1'>";
		}
		echo "</td><td>".$lang["printers"][15]."</td>";
		echo "</tr></table>";
		
		echo "<tr><td>".$lang["printers"][23].":</td>";
		echo "<td><input type='text' size='12' name='ramSize' value=\"".$printer->fields["ramSize"]."\"></td>";
		echo "</tr>";

		echo "</td></tr>";

	echo "</table>";
	echo "</td>\n";	
	echo "</tr>";
	echo "<tr>";
	echo "<td class='tab_bg_1' valign='top' colspan='2'>";
	
	
	
		echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td valign='top'>";
	    echo "<tr><td>".$lang["printers"][20].":	</td>";
		echo "<td><input type='text' name='achat_date' readonly size='10' value='".$printer->fields["achat_date"]."'>";
		echo "&nbsp; <input name='button' type='button' class='button'  onClick=\"window.open('mycalendar.php?form=form&elem=achat_date','Calendrier','width=200,height=220')\" value='".$lang["buttons"][15]."...'>";
		echo "&nbsp; <input name='button_reset' type='button' class='button' onClick=\"document.forms['form'].achat_date.value='0000-00-00'\" value='reset'>";
    echo "</td></tr>";
		
		echo "<tr><td>".$lang["printers"][21].":	</td>";
		echo "<td><input type='text' name='date_fin_garantie' readonly size='10' value='".$printer->fields["date_fin_garantie"]."'>";
		echo "&nbsp; <input name='button' type='button' class='button' onClick=\"window.open('mycalendar.php?form=form&elem=date_fin_garantie','Calendrier','width=200,height=220')\" value='".$lang["buttons"][15]."...'>";
		echo "&nbsp; <input name='button_reset' type='button' class='button' onClick=\"document.forms['form'].date_fin_garantie.value='0000-00-00'\" value='reset'>";
    echo "</td></tr>";
		
		echo "<tr><td>".$lang["printers"][22].":	</td>";
		echo "<td>";
		if ($printer->fields["maintenance"] == 1) {
			echo " OUI <input type='radio' name='maintenance' value='1' checked>";
			echo "&nbsp; &nbsp; NON <input type='radio' name='maintenance' value=0>";
		} else {
			echo " OUI <input type='radio' name='maintenance' value=1>";
			echo "&nbsp; &nbsp; NON <input type='radio' name='maintenance' value='0' checked >";
		}
		echo "</td></tr>";
		
	echo "</table>";	
	echo "</td>\n";	
	echo "</tr>";
	echo "<tr>";
	echo "<td class='tab_bg_1' valign='top' colspan='2'>";

	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td valign='top'>";
	echo $lang["printers"][12].":	</td>";
	echo "<td align='center'><textarea cols='35' rows='4' name='comments' >".$printer->fields["comments"]."</textarea>";
	echo "</td></tr></table>";

	echo "</td>";
	echo "</tr>";
	
	if ($ID=="") {

		echo "<tr>";
		echo "<td class='tab_bg_2' valign='top' colspan='2'>";
		echo "<center><input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'></center>";
		echo "</td>";
		echo "</form></tr>";

		echo "</table></center>";

	} else {

		echo "<tr>";
		echo "<td class='tab_bg_2' valign='top'>";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<center><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'></center>";
		echo "</td></form>\n\n";
		echo "<form action=\"$target\" method='post'>\n";
		echo "<td class='tab_bg_2' valign='top'>\n";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<center><input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'></center>";
		echo "</td>";
		echo "</form></tr>";

		echo "</table></center>";

		showConnect($target,$ID,3);

		showPorts($ID,3);

		showPortsAdd($ID,3);
	}
}


function updatePrinter($input) {
	// Update a printer in the database

	$printer = new Printer;
	$printer->getFromDB($input["ID"]);

	// set new date and make sure it gets updated
	$updates[0]= "date_mod";
	$printer->fields["date_mod"] = date("Y-m-d H:i:s");
	
	// Pop off the last two attributes, no longer needed
	$null=array_pop($input);
	$null=array_pop($input);
	
	// Get all flags and fill with 0 if unchecked in form
	foreach ($printer->fields as $key => $val) {
		if (eregi("\.*flag\.*",$key)) {
			if (!isset($input[$key])) {
				$input[$key]=0;
			}
		}
	}	

	// Fill the update-array with changes
	$x=1;
	foreach ($input as $key => $val) {
		if ($printer->fields[$key] != $input[$key]) {
			$printer->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}

	$printer->updateInDB($updates);

}

function addPrinter($input) {
	// Add Printer, nasty hack until we get PHP4-array-functions

	$printer = new Printer;
	
	// dump status
	$null = array_pop($input);
	
	// fill array for update
	foreach ($input as $key => $val) {
		if (empty($printer->fields[$key]) || $printer->fields[$key] != $input[$key]) {
			$printer->fields[$key] = $input[$key];
		}
	}

	$printer->addToDB();

}

function deletePrinter($input) {
	// Delete Printer
	
	$printer = new Printer;
	$printer->deleteFromDB($input["ID"]);
	
} 	


?>
