# zm_movie_132
Export movies from zoneminder for version Zoneminder 1.32

- Used for passthrough option only

- Tested with Ubuntu 18.04 and zoneminder 1.32-3


FILES
- zm_movie_list.php: list movies. Use this as index

- zm_movie_encode.php: encode engine uses ffmpeg

- zm_movie_functions.php: miscellaneous functions called by scripts

- zm_movie_header.html: html header information

- zm_movie_make.php: setup movie parameters


INSTALL
- copy files in a subfolder under /var/www/html/. for example /var/www/html/zm132/

- change all ownership to www-data:www-data

- Point your browser to zm_movie_list.php

- for example http://XX.XX.XX.XX/zm132/zm_movie_list.php

MAKE A MOVIE
- Click button and Select Camera

- Choose start/stop time for the video (accuracy will be to closest keyframe)

Choose speed and FPS desired.

- If speed = 1, no reencoding will be necessary and video will be created extremely fast

- If speed > 1, you need to specify the FPS. Increase fps for fast video to reduce dropped frames. See ffmpeg wiki.

- use camera default frame rate if unsure

- Choose filename without extention

- The movies will appear in the index (zm_movie_list.php) where they can be accessed and deleted

NOTES
- I am not a programmer so this script will not meet any standards and is probably full of bugs. 

- The script zm_movie_zm132.php can be run directly from the command line.

- Arguments: CameraId StartTime EndTime Speed FPS Filename

- e.g. php zm_movie_zm132.php 1 "2019-11-08 06:45:00" "2019-11-08 06:50:00" 2 12 MyVideo

- If no movies are created, dates were not entered properly or permission errors.

- 2 Subfolders will be created, zm_movie for movies created and and zm_tmp for tmp files.
