<?php
namespace fierydevs\sitemap;

use yii\web\Url;

class SitemapGenerator extends \yii\base\Widget
{
	private $pf;
	
	public $output_file, $site, $cli, $frequency, $priority, $ignore_empty_content_type, $version;
	
	public function init(){
        parent::init();
		
        if ($this->output_file === null) {
			$this->output_file = 'sitemap.xml';
		}
		define("OUTPUT_FILE", $this->output_file);
		
		if($this->site === null){
			$this->site = \yii\helpers\Url::base(true);
		}
        define("SITE", $this->site);
		
        if ($this->cli === null) {
			$this->cli = false;
		}
		define("CLI", $this->cli);
		
        if ($this->frequency === null) {
			$this->frequency = 'weekly';
		}
		define("FREQUENCY", $this->frequency);
		
        if ($this->priority === null) {
			$this->priority = 0.5;
		}
		define("PRIORITY", $this->priority);
		
        if ($this->ignore_empty_content_type === null) {
			$this->ignore_empty_content_type = false;
		}
		define("IGNORE_EMPTY_CONTENT_TYPE", $this->ignore_empty_content_type);
		
        if ($this->version === null) {
			$this->version = 1.0;
		}
		define("VERSION", $this->version);
		
		define("NL", (CLI ? "\n" : "<br>"));
		define("AGENT", "Mozilla/5.0 (compatible; Plop PHP XML Sitemap Generator/" . VERSION . ")");
		define("SITE_SCHEME", parse_url(SITE, PHP_URL_SCHEME));
		define("SITE_HOST", parse_url(SITE, PHP_URL_HOST));
	}
	
    public function run()
    {
		// Define here the URLs to skip. All URLs that start with the defined URL 
		// will be skipped too.
		// Example: "https://www.example.com/print" will also skip
		//   https://www.example.com/print/bootmanager.html
		$skip_url = array (
						   SITE . "/print",
						   SITE . "/slide",
						  );

		// Print configuration
		echo "Plop PHP XML Sitemap Generator Configuration:" . NL;
		echo "VERSION: " . VERSION . NL;
		echo "OUTPUT_FILE: " . OUTPUT_FILE . NL;
		echo "SITE: " . SITE . NL;
		echo "CLI: " . (CLI ? "true" : "false"). NL;
		echo "IGNORE_EMPTY_CONTENT_TYPE: " . (IGNORE_EMPTY_CONTENT_TYPE ? "true" : "false") . NL;
		echo "DATE: " . date ("Y-m-d H:i:s") . NL;
		echo NL;
		
		// SITE configuration check    
		if (!SITE)
		{
			die ("ERROR: You did not set the SITE variable at line number " . 
				 "68 with the URL of your website!\n");
		}
		
		error_reporting (E_ERROR | E_WARNING | E_PARSE);

		$this->pf = fopen (OUTPUT_FILE, "w");
		if (!$this->pf)
		{
			echo "ERROR: Cannot create " . OUTPUT_FILE . "!" . NL;
			return;
		}

		fwrite ($this->pf, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
					 "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"\n" .
					 "        xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
					 "        xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9\n" .
					 "        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n" .
					 "<!-- Created with Plop PHP XML Sitemap Generator " . VERSION . " https://www.plop.at -->\n" .
					 "<!-- Date: " . date ("Y-m-d H:i:s") . " -->\n" .
					 "  <url>\n" .
					 "    <loc>" . SITE . "/</loc>\n" .
					 "    <changefreq>" . FREQUENCY . "</changefreq>\n" .
					 "  </url>\n");

		echo "Scanning..." . NL;
		$scanned = array();
		$this->Scan($this->GetEffectiveURL(SITE));
		
		fwrite ($this->pf, "</urlset>\n");
		fclose ($this->pf);

		echo "Done." . NL;
		echo OUTPUT_FILE . " created." . NL;
    }
	
	function GetPage ($url)
	{
		$ch = curl_init ($url);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_USERAGENT, AGENT);

		$data = curl_exec($ch);

		curl_close($ch);

		return $data;
	}

	function GetQuotedUrl ($str)
	{
		$quote = substr ($str, 0, 1);
		if (($quote != "\"") && ($quote != "'")) // Only process a string 
		{                                        // starting with singe or
			return $str;                         // double quotes
		}                                                 

		$ret = "";
		$len = strlen ($str);    
		for ($i = 1; $i < $len; $i++) // Start with 1 to skip first quote
		{
			$ch = substr ($str, $i, 1);
			
			if ($ch == $quote) break; // End quote reached

			$ret .= $ch;
		}
		
		return $ret;
	}

	function GetHREFValue ($anchor)
	{
		$split1  = explode ("href=", $anchor);
		$split2 = explode (">", $split1[1]);
		$href_string = $split2[0];

		$first_ch = substr ($href_string, 0, 1);
		if ($first_ch == "\"" || $first_ch == "'")
		{
			$url = $this->GetQuotedUrl($href_string);
		}
		else
		{
			$spaces_split = explode (" ", $href_string);
			$url          = $spaces_split[0];
		}
		return $url;
	}

	function GetEffectiveURL ($url)
	{
		// Create a curl handle
		$ch = curl_init ($url);

		// Send HTTP request and follow redirections
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_USERAGENT, AGENT);
		curl_exec($ch);

		// Get the last effective URL
		$effective_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		// ie. "http://example.com/show_location.php?loc=M%C3%BCnchen"

		// Decode the URL, uncoment it an use the variable if needed
		// $effective_url_decoded = curl_unescape($ch, $effective_url);
		// "http://example.com/show_location.php?loc=München"

		// Close the handle
		curl_close($ch);

		return $effective_url;
	}

	function ValidateURL ($url_base, $url)
	{
		global $scanned;
			
		$parsed_url = parse_url ($url);
			
		$scheme = $parsed_url["scheme"];
			
		// Skip URL if different scheme or not relative URL (skips also mailto)
		if (($scheme != SITE_SCHEME) && ($scheme != "")) return false;
			
		$host = $parsed_url["host"];
					
		// Skip URL if different host
		if (($host != SITE_HOST) && ($host != "")) return false;
		
		// Check for page anchor in url
		if ($page_anchor_pos = strpos ($url, "#"))
		{
			// Cut off page anchor
			$url = substr ($url, 0, $page_anchor_pos);
		}
			
		if ($host == "")    // Handle URLs without host value
		{
			if (substr ($url, 0, 1) == '/') // Handle absolute URL
			{
				$url = SITE_SCHEME . "://" . SITE_HOST . $url;
			}
			else // Handle relative URL
			{
				$path = parse_url ($url_base, PHP_URL_PATH);
				
				if (substr ($path, -1) == '/') // URL is a directory
				{
					// Construct full URL
					$url = SITE_SCHEME . "://" . SITE_HOST . $path . $url;
				}
				else // URL is a file
				{
					$dirname = dirname ($path);

					// Add slashes if needed
					if ($dirname[0] != '/')
					{
						$dirname = "/$dirname";
					}
		
					if (substr ($dirname, -1) != '/')
					{
						$dirname = "$dirname/";
					}

					// Construct full URL
					$url = SITE_SCHEME . "://" . SITE_HOST . $dirname . $url;
				}
			}
		}

		// Get effective URL, follow redirected URL
		$url = $this->GetEffectiveURL($url); 

		// Don't scan when already scanned    
		if (in_array ($url, $scanned)) return false;
		
		return $url;
	}

	// Skip URLs from the $skip_url array
	function SkipURL ($url)
	{
		global $skip_url;

		if (isset ($skip_url))
		{
			foreach ($skip_url as $v)
			{           
				if (substr ($url, 0, strlen ($v)) == $v) return true; // Skip this URL
			}
		}

		return false;            
	}

	function Scan ($url)
	{
		global $scanned, $pf;

		$scanned[] = $url;  // Add URL to scanned array

		if ($this->SkipURL($url))
		{
			echo "Skip URL $url" . NL;
			return false;
		}
		
		// Remove unneeded slashes
		if (substr ($url, -2) == "//") 
		{
			$url = substr ($url, 0, -2);
		}
		if (substr ($url, -1) == "/") 
		{
			$url = substr ($url, 0, -1);
		}


		echo "Scan $url" . NL;

		$headers = get_headers ($url, 1);

		// Handle pages not found
		if (strpos ($headers[0], "404") !== false)
		{
			echo "Not found: $url" . NL;
			return false;
		}

		// Handle redirected pages
		if (strpos ($headers[0], "301") !== false)
		{   
			$url = $headers["Location"];     // Continue with new URL
			echo "Redirected to: $url" . NL;
		}
		// Handle other codes than 200
		else if (strpos ($headers[0], "200") == false)
		{
			$url = $headers["Location"];
			echo "Skip HTTP code $headers[0]: $url" . NL;
			return false;
		}

		// Get content type
		if (is_array ($headers["Content-Type"]))
		{
			$content = explode (";", $headers["Content-Type"][0]);
		}
		else
		{
			$content = explode (";", $headers["Content-Type"]);
		}
		
		$content_type = trim (strtolower ($content[0]));
		
		// Check content type for website
		if ($content_type != "text/html") 
		{
			if ($content_type == "" && IGNORE_EMPTY_CONTENT_TYPE)
			{
				echo "Info: Ignoring empty Content-Type." . NL;
			}
			else
			{
				if ($content_type == "")
				{
					echo "Info: Content-Type is not sent by the web server. Change " .
						 "'IGNORE_EMPTY_CONTENT_TYPE' to 'true' in the sitemap script " .
						 "to scan those pages too." . NL;
				}
				else
				{
					echo "Info: $url is not a website: $content[0]" . NL;
				}
				return false;
			}
		}

		$html = $this->GetPage($url);
		$html = trim ($html);
		if ($html == "") return true;  // Return on empty page
		
		$html = preg_replace("/(\<\!\-\-.*\-\-\>)/sU", "", $html); // Remove commented text
		$html = str_replace ("\r", " ", $html);        // Remove newlines
		$html = str_replace ("\n", " ", $html);        // Remove newlines
		$html = str_replace ("\t", " ", $html);        // Remove tabs
		$html = str_replace ("<A ", "<a ", $html);     // <A to lowercase

		$first_anchor = strpos ($html, "<a ");    // Find first anchor

		if ($first_anchor === false) return true; // Return when no anchor found

		$html = substr ($html, $first_anchor);    // Start processing from first anchor

		$a1   = explode ("<a ", $html);
		foreach ($a1 as $next_url)
		{
			$next_url = trim ($next_url);
			
			// Skip empty array entry
			if ($next_url == "") continue; 
			
			// Get the attribute value from href
			$next_url = $this->GetHREFValue($next_url);
			
			// Do all skip checks and construct full URL
			$next_url = $this->ValidateURL($url, $next_url);
			
			// Skip if url is not valid
			if ($next_url == false) continue;

			if ($this->Scan($next_url))
			{
				// Add URL to sitemap
				fwrite ($this->pf, "  <url>\n" .
							 "    <loc>" . htmlentities ($next_url) ."</loc>\n" .
							 "    <changefreq>" . FREQUENCY . "</changefreq>\n" .
							 "    <priority>" . PRIORITY . "</priority>\n" .
							 "  </url>\n"); 
			}
		}
		return true;
	}
}