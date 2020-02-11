<?php
/**
 *PicoMaps
 * Add the Leaflet maps plugin in Pico CMS
 *
 * @author  Maloja
 * @license http://opensource.org/licenses/MIT The MIT License
 * @link    https://github.com/maloja/pico-leaflet

 */
class PicoLeaflet extends AbstractPicoPlugin {
    const API_VERSION = 2;
    protected $enabled = true;
    protected $dependsOn = array();

    /**
     * This private variables
     */
    private $p_keyword = 'map';
    private $p_count = 0;
    private $argp = array();
    private $argp_default = array (
                                    'center'    => '47.5833, 9.1175',
                                    'zoom'      => 10,
                                    'height'    => '300px',
                                    'width'     => '100%',
								  );
    private $plugin_path = '';

    /**
     * Triggered after Pico has prepared the raw file contents for parsing
     */
    public function onContentParsed(&$content) {
        $content = preg_replace_callback( '/\\<p\\>\s*\\(\\%\s*' . $this->p_keyword . ':(.*?)?' . '\s*\\%\\)\s*\\<\\/p\\>/si', function ($match) {
            if ($match[1]) $this->prepareARGP($match[1]);
            $out = $this->createOutput($this->p_count);
            $this->p_count++;
            return $out;
        }, $content);
    }

    /**
     * Triggered after Pico has rendered the page
     */
    public function onPageRendered(&$output ) {
        // act only if the keyword was found
        if ( $this->p_count > 0 ) {
            // add required javascripts in head tag
            $jsh  = '    <!-- PicoLeaflet -->' . "\n";
            $jsh .= '    <link href="' .  $this->plugin_path . 'vendor/leaflet/leaflet.css" rel="stylesheet">' . "\n";
            $jsh .= '    <script src="' . $this->plugin_path . 'vendor/leaflet/leaflet.js"></script>' . "\n";
            $jsh .= '    <link href="' .  $this->plugin_path . 'vendor/leaflet/leaflet-fullscreen/Control.FullScreen.css" rel="stylesheet">' . "\n";
            $jsh .= '    <script src="' . $this->plugin_path . 'vendor/leaflet/leaflet-fullscreen/Control.FullScreen.js"></script>' . "\n";
            $jsh .= '    <link href="' .  $this->plugin_path . 'vendor/leaflet/leaflet-gesturehandling/dist/leaflet-gesture-handling.min.css" rel="stylesheet">' . "\n";
            $jsh .= '    <script src="' . $this->plugin_path . 'vendor/leaflet/leaflet-gesturehandling/dist/leaflet-gesture-handling.min.js"></script>' . "\n";
			$jsh .= '    <script src="' . $this->plugin_path . 'vendor/leaflet/leaflet-gpx/gpx.js"></script>' . "\n";
            // $jsh .= '    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests"> ' . "\n";
            $jsh .= '</head>' . "\n" . '<body>' . "\n";
            $output = preg_replace('/\<\/head\>[\n|\r|\s]*?\<body\>/', $jsh, $output, 1);
        }
    }

    /**
     * INTERNAL FUNCTIONS
     */
    private function prepareARGP(&$in) {
        $tmp = explode(",\n", trim($in, ",\n:"));                        // cut leading ":" and trailing \n and create $tmp array
        $tmp = preg_replace('/\s+/', ' ', $tmp);                         // remove multiple spaces;
		$this->argp[$this->p_count] = $this->argp_default;               // set default values
		$this->argp[$this->p_count]['marker'] = array();
        foreach ($tmp as $d) {
            list($key, $value) = explode("=", $d);
            $key = strtolower(trim($key));
            $value = trim($value);
            if ($value == "") $value = 1;
            $value = preg_replace('/\\&quot\\;/','"', $value);          //replace HTML &quot; by a real Quote
            $value = preg_replace("/(?<= )(?=(?:(?:[^'\"]*['\"]){2})*[^`'\"]*$)/", ', ', $value);
            if(preg_match("/(width|height|center|zoom|marker|markerfile|gpxfile)/", $key) === 1) {
				if ($key == 'marker') {
    	            array_push($this->argp[$this->p_count][$key], $value );
        	    } else $this->argp[$this->p_count][$key] = $value;
			}
        }
    }

    private function createOutput(&$id) {
        if  ($this->plugin_path == "") $this->plugin_path = $this->getConfig('plugins_url') . 'PicoLeaflet/';
		$this->setMapSize($id);
        $out  = "\n<!-- PicoOpenStreetMap for map_{$id} -->\n";
        $out .= '<div style="max-width:' . $this->argp[$id]['width'] . '; padding-top:' . $this->argp[$id]['height'] . '; position: relative">' . "\n";
        $out .= '<div id="map_' . $id . '" tabindex="' . $id . '" class="map" style="position: absolute; top: 0; bottom: 0; left: 0; right: 0;"></div>' . "\n";
        $out .= "</div>\n";
        $out .= "<script>\n";

        //include the leaflet creator content
        $jsp = file_get_contents( $_SERVER['DOCUMENT_ROOT'] . '/plugins/PicoLeaflet/js/pico_leaflet.js');
        $this->fillWithARGP($jsp, $id);
        $out  .= $jsp;

        //----------------------------------------------------------------
        // add Markers direct out of the md-File itself
        $mrk = '';
        foreach ($this->argp[$id]['marker'] as $marker) {
            if (preg_match('/([0-9.]+)\s?,\s?([0-9.]+)(\s?,\s?\"(.*?)\")?/', $marker, $matches)) {
                $mrk .= "   new L.marker([ $matches[1] , $matches[2] ])";
                if ($matches[4]) $mrk .= ".bindPopup('$matches[4]').openPopup()";
                $mrk .= ",\n";
            }
        }
        // add Markers from markerfile
        if ($this->argp[$id]['markerfile']) {
            $mf = file_get_contents( $_SERVER['DOCUMENT_ROOT'] . trim($this->argp[$id]['markerfile'], "\'\" "));
            $anz = preg_match_all('/([0-9.]+)\s+([0-9.]+)\s+(.*)/', $mf, $matches);
            if ($anz > 0) {
                for ($i = 0; $i < $anz; $i++) {
                    $mrk .= "   new L.marker([ {$matches[1][$i]} , {$matches[2][$i]} ])";
                    if ($matches[3][$i]) $mrk .= ".bindPopup('{$matches[3][$i]}').openPopup()";
                    $mrk .= ",\n";
                }
            }
        }
        //add Markers Framework
        if ($mrk) {
            $out .= "var markers = new L.FeatureGroup([\n";
            $out .= $mrk;
            $out .= "]).addTo(map_$id);\n";
            $out .= "control.addOverlay(markers, 'Markierungen');\n";
        }

        // add Track from trackfile with simple xmlparser
        if ($this->argp[$id]['gpxfile']) {
            $gpxfile = trim($this->argp[$id]['gpxfile'], "\'\", ");
		
			$filelist = array();
            $path_parts = pathinfo($gpxfile);
            if ( $path_parts['extension'] == 'gpx') {
                array_push($filelist, $gpxfile);
            }
			
			for ( $i = 0; $i < count($filelist); $i++ ) {
                $trackname = 'Route: ' . pathinfo($filelist[$i], PATHINFO_FILENAME); ;
                $out .= "var mytrack = new L.GPX('$filelist[$i]', {
                            async: true,
                            parseElements: ['track', 'route', 'waypoint'],
                            marker_options: {
                                    startIconUrl: '{$this->plugin_path}vendor/leaflet/leaflet-gpx/pin-icon-start.png',
                                    endIconUrl:   '{$this->plugin_path}vendor/leaflet/leaflet-gpx/pin-icon-end.png',
                                    shadowUrl:    '{$this->plugin_path}vendor/leaflet/leaflet-gpx/pin-shadow.png',
                                    wptIconUrls:  { '': '{$this->plugin_path}vendor/leaflet/leaflet-gpx/pin-icon-wpt.png' },
                                    iconSize:     [ 25,  41 ],
                                    popupAnchor:  [ -2, -37 ],
                            }
                         }).addTo(map_$id);\n";
                $out .= "control.addOverlay(mytrack, '{$trackname}');\n";
            }
        }
        $out .=  "</script>\n";
        $out .= "<!-- EndPicoOpenStreetMap for $id -->\n";
        return  $out;
    }

    private function fillWithARGP(&$jsp, &$id) {
        $jsp = preg_replace_callback( '/\\<\\<\s?(\\$.*?)\s?\\>\\>/si', function ($match) use (&$id) {
            eval('$vat = ' . $match[1].';' );
            return $vat;
        }, $jsp);
    }

    private function setMapSize(&$id) {
        // width has to be 100%, 200px or 200 (which means also px) otherwise set default
        if (!preg_match('/^\d+((px|%)?$)/', $this->argp[$id]['width']))
            $this->argp[$id]['width'] = $this->argp_default['width'];
        if (preg_match('/^\d+$/', $this->argp[$id]['width'] ))
             $this->argp[$id]['width'] .= "px";

        //height has to be 200px or 200 (which means also px) or <1 as ratio otherwise set default
        if (!preg_match('/^\d+(\.\d+|px)?$/', $this->argp[$id]['height']))
            $this->argp[$id]['height'] = $this->argp_default['height'];
        elseif (preg_match('/^[0-9.]+$/', $this->argp[$id]['height'])) {
            $this->argp[$id]['height'] = (float)$this->argp[$id]['height'];
            if ($this->argp[$id]['height'] <=1) {
                $this->argp[$id]['height'] = 100 * (float)$this->argp[$id]['height'];
                $this->argp[$id]['height'] .= "%";
            }
            else $this->argp[$id]['height'] .= "px";
        }
    }
}





