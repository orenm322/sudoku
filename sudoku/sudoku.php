<?php

@set_time_limit ( 0 );

class Sudoku
{
	public $key;
	public $val;
	public $row;
	public $col;
	public $section;
	public $locked;
	public $poss_vals;
	
	function __construct($p_key, $p_locked, $p_val) {           
    	$this->key = $p_key;
    	$this->locked = $p_locked;
    	$this->val = $p_val;
    	$this->setRow();
    	$this->setCol();
    	$this->setSection();           
    }
    
	public function setRow() {
		$this->row = $this->key[0]; //using first character in string or key value
	}
	
	public function setCol() {
		$this->col = $this->key[1]; //using second character in string or key value
	}
	
	public function setSection() {
		$temp_row = floor( $this->row/3 );
		if($this->row % 3 == 0)
			$temp_row--;
		
		$this->section = $temp_row * 3 + ceil( $this->col/3 ); //calculate which of the 9 boxes this field belongs to
	}

}

function checkConflict($sd1, $sd2)
{
	if( $sd1->row == $sd2->row ||
		$sd1->col == $sd2->col ||
		$sd1->section == $sd2->section  ) //compares two fields and checks if there are related and could cause conflict with same values assigned
		return true;
	else
		return false; //the fields are not related to each other; so no conflict with their values
}

function validate($row, $col)
{
  GLOBAL $new_grid;
  GLOBAL $sd;
  $curr_key = "$row$col";
  foreach($new_grid as $temp_key=>$temp_value)
  {
	if($curr_key != $temp_key && $sd["$temp_key"]->locked == 'N' )
	{
	   $check_yn = checkConflict($sd["$curr_key"], $sd["$temp_key"] );
	   if($check_yn == true && $new_grid["$curr_key"] == $new_grid["$temp_key"] ) //both field have same row, column or section & they both have the same value; value for current key is not valid
  			return false;      	   
	}
  }
  return true; //value for current key does not have any conflicts with previous values, so it returns true
}

function getNextField($row, $col)
{
  GLOBAL $sd;
  GLOBAL $new_grid;
  if( isset($new_grid["$row$col"] )  ) //field was already assigned; need to determine the next field to solve for
  {
  	if($col == 9)
	   if($row == 9) {
	      	return ""; //assume solved
	   }
	   else 
	      return getNextField($row+1, 1); //recursive call; go to new row, first column		
  	else
	   return getNextField($row, $col+1); //recursive call; go to same row, next column
  }
  else {
	return $sd["$row$col"]; //field was not assigned; this will be the next field to solve for
  }
}

function printSolution()
{
	GLOBAL $new_grid;
	GLOBAL $orig_fields;
	
	echo "<h3>The Solution:</h3>";
	echo "<table border='1' width='80%'>\n";
	for($b=0; $b < 3; $b++)
	{
	   echo "<tr>\n";
	   for($a=0; $a < 3; $a++)
	   {
		echo "<td>\n";     
		echo "<table border='1' width='100%' style='font-size:18px;'>\n";
		for($y=1; $y <= 3; $y++)
		{
	   		echo "<tr align='center'>\n";
	   		for($x=1; $x <=3; $x++)
	   		{
				$temp_r = 3*$b + $y;
				$temp_c = 3*$a + $x;
				$temp_name = $temp_r . $temp_c;
				$temp_value = $new_grid[$temp_name];
				if(in_array("$temp_name", $orig_fields) )
				   $temp_value = "<b>$temp_value</b>"; //make bold a field to indicate it was part of the original puzzle
				echo "<td>$temp_value</td>\n";
	   		}
	   		echo "</tr>\n";
		}
		echo "</table>\n";
		echo "</td>\n";
	   }
	   echo "</tr>\n";   
	}
	echo "</table>\n";
}

function solve($row, $col)
{  
  GLOBAL $new_grid;
  GLOBAL $orig_fields;
  GLOBAL $sd;
  
  $solved = false;
  $temp_vals = $sd["$row$col"]->poss_vals;
  $cnt = count($temp_vals);
  
  for($c=0; $c < $cnt && $solved == false; $c++) {
  	$new_grid["$row$col"] = $temp_vals[$c];
	$sd["$row$col"]->val = $temp_vals[$c];
	$valid_yn = validate($row, $col);
	if($valid_yn == true)
	{
	   $next_field = getNextField($row, $col);
	   if( $next_field == "") {
	      $solved = true;
	   }
	   else {
	      $new_row = $next_field->row;
  	      $new_col = $next_field->col;
	      $solved = solve($new_row, $new_col); //recursive function call
	   }
	   
	}
	if($solved == false) {
	  unset($new_grid["$row$col"]);
	  unset($sd["$row$col"]->val);
	}	
  	
  }
  
  return $solved;
}

function setInitialPuzzle()
{
	//in order to speed up solving the solution; we will only check values for each field that does not already conflict with the inital board
	GLOBAL $sd;
	GLOBAL $orig_fields;
	foreach($sd as $field) {
		$poss_vals = array();
		if($field->locked == 'N') {
			//no value assigned
			$used_vals = array();
			foreach($orig_fields as $key) {
				if( checkConflict($field, $sd["$key"]) && in_array($sd["$key"]->val, $used_vals) == false ) {
						$used_vals[] = $sd["$key"]->val; //assign the value the field cannot be
				}
			}
			for($x=1; $x<=9; $x++) {
				if( in_array($x, $used_vals) == false )
					$poss_vals[] = $x; //assign the possible value for that field
			}
		}
		else {
			$used_vals = array();
		}
		
		$field->poss_vals = $poss_vals; //save possible values 
	}
	
}

?>

<html>
<body>

<?php

if(isset($_POST['submitted']) ) {
  $start_time = time();
  $orig_grid = $_POST['hid'];
  $new_grid = array();
  $orig_fields = array();
  foreach($orig_grid as $key)
  {
    $value = $_POST["txt_" . $key];
    if(trim($value) != "") {
		$orig_fields[] = $key;
		$new_grid["$key"] = $value;
		$locked = 'Y'; //part of original puzzle
		$val = $value;
    }
    else {
    	$locked = 'N';
    	$val = null;
    }
    $sd["$key"] = new Sudoku($key, $locked, $val);
  }
  setInitialPuzzle();
  $next_field = getNextField(1, 1);
  $new_row = $next_field->row;
  $new_col = $next_field->col;
  $ret = solve($new_row, $new_col);
  if($ret == true) {
    printSolution();
    $end_time = time();
    $minutes = (int) ( ($end_time - $start_time) / 60 );
    $seconds = ($end_time - $start_time) % 60;
    if($seconds < 10) 
		$seconds = '0' . $seconds;
    echo "<p>Time elapsed $minutes:$seconds</p>";
  }
  else {
    echo "<p>No Solution!</p>";
  }

  echo "<p><b>By Oren Mordechai</b></p>";
  
}

?>

</body>
</html>