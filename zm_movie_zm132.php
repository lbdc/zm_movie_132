<?php
//
// php make movie zm 1.32 with passthrough
//
// parameters $MonitorId,$Starttime,$Endtime,$Speed,$fps,$Filename
//
// Read from etc/zm/zm.conf (ubuntu) or etc/zm.conf (centos)
//
// set relative location of tmp folder and movie folders
$path_tmp="zm_tmp";
$path_movie="zm_movie";
if(is_dir($path_tmp) === false )
{
    mkdir($path_tmp);
}
if(is_dir($path_movie) === false )
{
    mkdir($path_movie);
}
if($argc == 1) {
	exit("Parameters: MonitorId Starttime Endtime Speed fps Filename example 1 \"2018-12-08 08:00:00\" \"2018-12-08 09:00:00\" 10 30 test.mp4\n"); }
if(file_exists("/etc/zm/zm.conf")) {
	$ini_file='/etc/zm/zm.conf';}
else if(file_exists("/etc/zm.conf")) {
	$ini_file='/etc/zm.conf';}
else { echo "No zoneminder configuration zm.conf found";}
//
// Parse ini file the long way (PHP deprecated # as comments in ini files)
//
$file = fopen($ini_file, "r");
while(!feof($file)) {
	$line = fgets($file);
	if($line[0] =="#" || strlen($line) <=1) {
		// skip line
	}
	else {
		$config_ini=explode("=", $line);
		$config[$config_ini[0]]=str_replace(PHP_EOL, null, $config_ini[1]);
	}
}
fclose($file);
define('ZM_HOST', $config['ZM_DB_HOST']);
define('ZMUSER', $config['ZM_DB_USER']);
define('ZMPASS', $config['ZM_DB_PASS']);
define('ZM_DB', $config['ZM_DB_NAME']);	
//
// Connect to ZM DB
//	
$con=mysqli_connect(ZM_HOST,ZMUSER, ZMPASS, ZM_DB);
if (mysqli_connect_errno()) {
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
//
// Query ZM DB for events and path with matching timestamps
//
$result = mysqli_query($con,"SELECT MonitorId, Events.Name,EventId, StartTime, EndTime,StorageId, Storage.Path FROM Storage,Frames, Events WHERE Events.Id=Frames.EventId AND Events.StorageId=Storage.Id AND Frames.TimeStamp > '$argv[2]' AND Frames.Timestamp < '$argv[3]' AND MonitorId=$argv[1] GROUP BY EventId");
//var_dump($result);
while($row = mysqli_fetch_assoc($result)) {
	$mon_Events[]=$row;
}
mysqli_close($con);
//
// cleanup tmp videos
// this is commented out as may /
//echo "cleanup tmp files\n";
// array_map( 'unlink', array_filter((array) glob($path_tmp . "/*") ) );
//
// Assign variables
if(isset($argv[4])) {
	$speed=1/$argv[4]; 
} else { $speed=1; }
if(isset($argv[5])) {
	$fps=$argv[5];
} else { $fps=30; }
$Filename = $argv[6];
//
// If speed changes, reencode. FPS has to increase proportionally to speed to eliminate frame drop.  	
//
// Dump list of events to ffmpeg input text file
//
// Open file, iterate through array, dump lines and close file
$file = fopen($path_movie . "/" . $Filename . ".txt","w+") or die("Unable to create file");
$videos=array();
for ($i = 0; $i < count($mon_Events); $i++) {	
	// Remove spaces
	$mon_Events[$i]["Name"] = str_replace(' ', '', $mon_Events[$i]["Name"]);
	// Truncate time
	$date = explode(" ",$mon_Events[$i]["StartTime"]);
	//echo string to file
	$videos[$i] = $mon_Events[$i]["Path"] . "/" . $mon_Events[$i]["MonitorId"] . "/" . $date[0] . "/" . $mon_Events[$i]["EventId"] . "/" . $mon_Events[$i]["EventId"] . "-video.mp4";
	$string = "file '" . $videos[$i] . "'\n";
	fwrite($file, $string);
}
fclose($file);
//
// truncate first and last movie to meet requested periods
// if only one movie, truncate one file
// if two or more movies, truncate first and last files
// 
// Convert time strings to time
// Substract requested time from video start time
$Start_req=strtotime($argv[2]);
$Start_event=strtotime($mon_Events[0]["StartTime"]);
$diff_start=$Start_req-$Start_event;
// End time
// get last event with count function 
$End_req=strtotime($argv[3]);
$count = count($mon_Events) -1;
$End_event=strtotime($mon_Events[$count]["StartTime"]);
// 
// Create ffmpeg part events
// if event count = 1 video event just process one video
// dump temp video in sub folder
if($count <1) {
	$diff_end=$End_req-$Start_req;
// If speed is maintained no renncoding necessary
	if($speed == 1) {
		$ffmpeg_command = "ffmpeg -y -ss " . $diff_start . " -i " . $videos[0]  . " -t " . $diff_end . " -c copy -map 0 " . $path_movie . "/" . $Filename . ".mp4"; 
		echo $ffmpeg_command . "\n";}	
	else {
		$ffmpeg_command = "ffmpeg -y -ss " . $diff_start . " -i " . $videos[0]  . " -t " . $diff_end . " -r " . $fps . " -filter:v setpts=" . $speed . "*PTS " . $path_movie . "/" . $Filename . ".mp4";} 
	echo "Only processing one file\n";
	echo $ffmpeg_command . "\n"; 
	shell_exec($ffmpeg_command);
	// no need to edit text file when processing one event 
}
if($count >= 1) {
// if event cound contains more than one video event, process first and last individually, dump in /tmp and edit ffmpeg input file for future concat
// 1st event movie
	$diff_end=$End_req-$Start_event;
	$ffmpeg_command = "ffmpeg -y -i " . $videos[0]  . " -ss " . $diff_start . " -c copy -map 0 " . $path_tmp . "/" . $mon_Events[0]["EventId"] . "-video.mp4"; 
	echo $ffmpeg_command . "\n";
	shell_exec($ffmpeg_command);
//
// Last event movie
	$diff_end=$End_req-$End_event;
	$ffmpeg_command = "ffmpeg -y -i " . $videos[$count]  . " -ss 0" . " -t " . $diff_end . " -c copy -map 0 " . $path_tmp ."/" . $mon_Events[$count]["EventId"] . "-video.mp4"; 
	echo $ffmpeg_command . "\n"; 
 	shell_exec($ffmpeg_command);
// Update ffmpeg text file
	$f = $path_movie . "/" . $Filename . ".txt";
	$arr = file($f);
//	$arr[0] = "file '" . $mon_Events[0]["EventId"] . "-video.mp4'\n"; // edit first line
	$arr[0] = "file '../" . $path_tmp . "/" . $mon_Events[0]["EventId"] . "-video.mp4'\n"; // edit first line
	if($count>0) {
//		$arr[$count] = "file '" . $mon_Events[$count]["EventId"]. "-video.mp4'"; } // edit last line 
		$arr[$count] = "file '../" . $path_tmp . "/" . $mon_Events[$count]["EventId"]. "-video.mp4'"; } // edit last line 
	file_put_contents($f, implode($arr)); // write back to file
//
// Concantenate and reencode if necessary
//
// If speed is maintained no renncoding necessary
if($speed == 1) {
	$ffmpeg_command="ffmpeg -y -f concat -safe 0 -i " . $path_movie . "/" . $Filename . ".txt -c copy " . $path_movie . "/" . $Filename . ".mp4";
	echo $ffmpeg_command . "\n";	
	shell_exec($ffmpeg_command);
} else {
// If speed changes, reencode. FPS has to increase proportionally to speed to eliminate frame drop.  	
	$ffmpeg_command="ffmpeg -y -f concat -safe 0 -i " . $path_movie . "/" . $Filename . ".txt -r " . $fps . " -filter:v setpts=" . $speed . "*PTS " . $path_movie .  "/" . $Filename . ".mp4";
	echo $ffmpeg_command . "\n";	
	shell_exec($ffmpeg_command);
}
}
//
// cleanup tmp videos
//
echo "cleanup tmp files\n";
unlink($path_tmp . "/" . $mon_Events[0]["EventId"] . "-video.mp4"); 
unlink($path_tmp . "/" . $mon_Events[$count]["EventId"] . "-video.mp4"); 
// To delete all files at once, uncomment this line. May create problems with timing of multiple encoding
// array_map( 'unlink', array_filter((array) glob($path_tmp . "/*") ) );
//
?>
