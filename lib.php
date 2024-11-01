<?php

	// Some functions should be disabled in php configuration for security reasons
	// Checks for alternative methods to bypass these security purposed limitations 
	function owap_get_file($path, &$http_header)
	{
		# function file_get_contents()
		if(function_exists('file_get_contents')) 
		{
			$ret = @file_get_contents($path);
			$http_header = $http_response_header[0];
		}		
		# function file()
		else if(function_exists('file')) 
		{
			$lines = @file($path);
			$http_header = $http_response_header[0];
			if (!empty($lines))
			foreach ($lines as $line_num => $line)
				$ret .= $line;
		}		
		# function readfile()
		else if(function_exists('readfile')) 
		{			
			ob_start();
			@readfile($path);
			$http_header = $http_response_header[0];
			$ret = ob_get_contents();
			ob_end_clean();						
		}
		else
		{		
			echo "<h4><font color=red>WARNING:</font> <font color=blue>Sorry your PHP configuration not appropriate for the WP OnlyWire Auto Poster plugin.";
			echo "<br>file_get_contents(),readfile() and file() functions have been disabled for security reasons by your server's admin.</font></h4>";
		}
		
		return $ret;
	}
?>