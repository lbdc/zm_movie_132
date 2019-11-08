<!DOCTYPE html>
<html lang="en">
<head>
<?php
	include 'zm_movie_header.html';
?>
<style>
input.style-2 {
   width:40px;
}
input.style-1 {
   width:60px;
}

</style>

<script type="text/javascript" src="js/moment.js"></script>
<script type="text/javascript" src="js/transition.js"></script>
<script type="text/javascript" src="js/collapse.js"></script>
<script type="text/javascript" src="js/bootstrap-datetimepicker.min.js"></script>
<link rel="stylesheet" href="css/bootstrap-datetimepicker.min.css">

</head>
<body>
<?php
//	include 'navbar.html';
//
// Load camera information from DB
require "zm_movie_functions.php";
$camera = Load_Camera();
echo '<script> var camera = ';
echo json_encode($camera);
echo ';</script>';
?>
<div class="container" id="make_movie">
<h2>Movies</h2>
	<div class="table-responsive">
		<table class="table table-hover table-bordered table-condensed">
		<colgroup>
			<col class="col-md-2">
			<col class="col-md-8">
		</colgroup>
		<tbody>
			<tr><td colspan=2>
			<select class="form-control" id="sel_cam" name="sel_cam" onChange="sel_cam(this)">
				echo '<option value="Select">Select Camera</option>';
				<?php   
				foreach($camera as $key=>$value) {
					echo '<option value="'.$key.'">'.$camera[$key]["Name"].'</option>';
				};  ?> 
			</select></div>
			<form role="form" name="make_movie" method="GET">
                        <div class="form-group">
			</td></tr>
			<tr><td>Camera</td><td><input type="text" class="form-control" id="Camera" name="monitor" value="" readonly></input></td></tr>
			<tr><td>CameraId</td><td><input type="text" class="form-control" id="CameraId" name="monitorId" value="" readonly></input></td></tr>
			<tr><td>Video Start</td><td style="position: relative"><input type='text' class="form-control" name="start" id='start'/></td></tr>
<!--			<tr><td></td><td><input type="number" class="style-1" id="year" max="2050" min="2000" step="1" value=""><input type="number" class="style-2" id="month" max="12" min="1" step="1" value=""><input type="number" class="style-2" id="day" max="31" min="1" step="1" value=""><input type="number" class="style-2" id="hour" max="23" min="0" step="1" value=""><input type="number" class="style-2" id="minute" max="59" min="0" step="1" value=""><input type="number" class="style-2" id="second" max="59" min="1" step="1" value=""></td></tr>
-->			
			<tr><td>Video End</td><td style="position: relative"><input type='text' class="form-control" name="end" id='end'/></td></tr>

			<tr><th colspan =2>Encoder Parameters</th></tr>
			<tr><td>Speed</td><td><input type="number" name="Speed" max="1000" min="1" step="1" value="10"></td></tr>
			<tr><td>FPS</td><td><input type="number" name="MultiplierX" max="120" min="1" step="1" value="6"></td></tr>
			<tr><td>Filename</td><td><input type ="text" class="form-control" name="Filename" id="Filename" value=""></td></tr>
			</div>
		</tbody>
		</table>
		<button type="submit" name="mmovie" class="btn btn-primary btn-md">Make Movie</button>
		</form>
	</div>
</div>


<?php
$files = scandir('zm_tmp');
sort($files); // this does the sorting
foreach($files as $file){
   echo'<a href="zm_tmp/'.$file.'">'.$file.'</a>';
}
?>


<script type="text/javascript">
var x;
function sel_cam(sel) {
	x = sel.value;
	document.getElementById('Camera').value = camera[x]["Name"];
	document.getElementById('CameraId').value = camera[x]["Id"];
	document.getElementById('start').value = camera[x]["Starttime"];
	document.getElementById('end').value = camera[x]["Endtime"];
/* Parse Date&time for future date time picker
	var date = moment(camera[x]["Starttime"]).format('YYYY-MM-DD HH:mm:ss');
	document.getElementById('year').value = moment(date).format('YYYY');
	document.getElementById('month').value = moment(date).format('MM');
	document.getElementById('day').value = moment(date).format('DD');
	document.getElementById('hour').value = moment(date).format('HH');
	document.getElementById('minute').value = moment(date).format('mm');
	document.getElementById('second').value = moment(date).format('ss');
*/
}
</script>
<?php

	// POST data, call script, clear GET
$movie_path=""; //use local folder if not set
if(isset($_GET['mmovie'])) {
	$command='/usr/bin/php zm_movie_zm132.php '.$_GET["monitorId"].' "'.$_GET["start"].'" "'.$_GET["end"].'" '.$_GET["Speed"].' '.$_GET["MultiplierX"].' '.$_GET["Filename"];
	exec("($command) > /dev/null &");
	unset($_GET);
	$page=$_SERVER['PHP_SELF'];
	echo '<script>location.href="'.$page.'";</script>';
}
?> 
</body>
</html>
