gifserver
=========

``gifserver`` is a service written in PHP that transcodes videos to GIFs on the fly. The gif animation will have 9 frames in a loop to represent the complete video

**1.** Add your video url http://gifserver.levy.se/?video=http://levy.se/test.mp4

**2.** Add ``&format=json`` like http://gifserver.levy.se/?video=http://levy.se/test.mp4&format=json


* Webserver with php
* The service is a wrapper around ffmpeg ``$brew install ffmpeg`` if you have Homebrew installed
* ImageMagic installed with php module ``$brew reinstall php55-imagick`` where php55 is your PHP version
* Apace/webserver? Make sure it [can run cmd](http://stackoverflow.com/questions/21610417/osx-apache-allow-execution-of-shell-command-for-php-script-include-path )



> I spent hours googling for a service that creates an animated gif from a video, that represents the complete video.
> I only found solutions that animated the first 10 frames, the first 30 seconds etc.
> So i decided to create ``gifserver``that will represent the complete video with 9 looping frames,no matter how long the original file is.
