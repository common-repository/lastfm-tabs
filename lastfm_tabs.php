<?php
/*
Plugin Name: Last.fm Tabs
Plugin URI: http://kreativkonzentrat.de/blog/lastfm-tabs-2-0.html
Description: Last.fm track, album art, userinfo, shout and friends display
Version: 2.0.2
Author: Felix Moche
Author URI: http://kreativkonzentrat.de
*/

/*
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (!defined('LASTFM_BASENAME'))
	define('LASTFM_BASENAME', plugin_basename(__FILE__));

if (!defined('LASTFM_ABSPATH'))
	define('LASTFM_ABSPATH', dirname(__FILE__));

if (!defined('LASTFM_DIRNAME'))
	define('LASTFM_DIRNAME', basename(LASTFM_ABSPATH));
	
if (!defined('LASTFM_URI'))
	define('LASTFM_URI', plugins_url().'/'.LASTFM_DIRNAME);

include_once('lastfm_options.php');

/*
 * main class
 */
class lastfm_tabs{

	public $options;
	var $api_key = '21afc70179cc8d0aa11aae5a6253a535';
	var $transients = array();
	
	private function lastfm_tabs(){
		$this->__construct();
	}

	/*
	 * construct
	 */
	public function __construct(){
		$this->options = get_option('lastfm_tabs_options');
		$this->transients = array(
			'friends'		=> 'lastfm_friends',
			'loved'			=> 'lastfm_favorites',
			'shouts'		=> 'lastfm_shouts',
			'info'			=> 'lastfm_info',
			'charts'		=> 'lastfm_charts',
			'top_albums'	=> 'lastfm_top_albums',
			'top_artists'	=> 'lastfm_top_artists',
			'recently'		=> 'lastfm_recently',
			'widget'		=> 'lastfm_tabs_widget_cache',
			'shortcode'		=> 'lastfm_tabs_shortcode'
		);
		if (is_admin()){
			add_filter('plugin_action_links', array($this, 'action_links'), 10, 2);
			register_deactivation_hook(__FILE__,array($this, 'deactivate'));
			register_uninstall_hook(LASTFM_ABSPATH . '/lastfm_tabs.php' ,'uninstall');
		}
		add_action('plugins_loaded', array($this, 'init'));		
	}

	/*
	 * initialisation
	 */
	function init(){
		//load textdomain
		load_plugin_textdomain('lastfm_tabs', '',LASTFM_DIRNAME . '/languages');

		//sidebar widget
		wp_register_sidebar_widget('lastfm_tabs', 'Last.fm Tabs', array($this, 'widget_output'));
		
		//add shortcode
		add_shortcode('lastfm',  array($this,'shortcode_handler'));	

		add_action('publish_post', array($this, 'savepost_hook'));
		add_action('publish_page', array($this, 'savepost_hook'));
		add_action('wp_print_styles', array($this, 'style'));
		add_action('wp_enqueue_scripts', array($this, 'tabs'));
		add_action('wp_enqueue_scripts', array($this, 'hover'));
	}
	
	function savepost_hook(){
		delete_transient('lastfm_tabs_template_cache');
		delete_transient('lastfm_tabs_shortcode_cache');
	}

	/*
	 * callback function for shortcode
	 */
	function shortcode_handler($attr, $content){
		extract(shortcode_atts(array(
			'tab'	=> '',
			'titles'=> false,
			'count' => null,
			'cache' => false
			),
		$attr));
		//get desired tabs
		$tab = explode(',',str_replace(' ','', strtolower($tab)));
		$caller = 'shortcode';
		if ($cache == true){
			$transient = $this->getTransient('shortcode');
			$op = get_transient($transient);
		}
		else
			$op = false;
		if ($op === false){
			if ($this->getDebug() === true)
				printf(__('%s data not cached. ','lastfm_tabs'), 'Shortcode');
			if ($titles !== false)
				$titles = true;
			if (intval($count) < 1)
				$count=null;
			$op = '';		
			foreach($tab as $_tab){
				switch($_tab){
					case('recent'):
						$op .= $this->get_recently_played($count, $titles, $caller);
						break;
					case('friends'):
						$op .= $this->get_friends($count, $titles, $caller);
						break;
					case('info'):
						$op .= $this->get_userinfo($titles, $caller);
						break;
					case('charts'):
						$op .= $this->get_charts($count, $titles, $caller);
						break;
					case('artists'):
						$op .= $this->get_top_artists($count, $titles, $caller);
						break;
					case('albums'):
						$op .= $this->get_top_albums($count, $titles, $caller);
						break;
					case('shouts'):
						$op .= $this->get_shouts($count, $titles, $caller);
						break;
					case('loved'):
						$op .= $this->get_loved_tracks($count, $titles, $caller);
						break;
					default:
						break;
				}
			}
			$op = '<ul class="lastfm_widget">'.$op.'</ul>';
			if ($cache == true){
				set_transient($transient, $op, $this->getExpiration());
				echo '<br>set cache<br>';
			}
		}
		elseif ($this->getDebug() === true)
			printf(__('%s data was cached. ','lastfm_tabs'), 'Shortcode');
		return $op;
	}

	/*
	 * uninstall and deactivate
	 */
	function deactivate(){
		$this->clear_transients(false);
	}
	
	function uninstall(){
		$this->clear_transients(false);
		delete_option('lastfm_tabs_options');
	}
	
	/*
	 * set action links
	 */
	function action_links($links, $file){
		if ( $file != LASTFM_BASENAME )
			return $links;
		$settings_link = '<a href="options-general.php?page=lastfm-options">' . __('Settings') . '</a>';
		array_unshift($links, $settings_link);
		return $links;
	}
	
	/*
	 * clear transient cache
	 */
	public function clear_transients($echo=true){
		delete_transient('lastfm_tabs_template_cache');
		delete_transient('lastfm_tabs_shortcode_cache');
		
		foreach($this->transients as $transient){
			delete_transient($transient);
		}		
		if ($echo)
			echo '<div id="message" class="updated fade"><p>'.__('Caches cleared.','lastfm_tabs').'</p></div>';
	}

	/*
	 * Errorhandling
	*/	
	function handle_error($errno, $errstr, $errfile, $errline, array $errcontext){
		if (0 === error_reporting())
			return false;
		throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
	}

	function handle_xml_errors($errno, $errstr, $errfile, $errline){
		if (preg_match('/^DOMDocument::load\(\): (.+)$/', $errstr, $m) === 1)
			throw new Exception($m[1]);
	}

	private function getDebug(){
		if (isset($this->options['debugmode']) && intval($this->options['debugmode']))
			return true;
		else
			return false;
	}

	private function getEffect(){
		return $this->options['effect'];
	}

	private function getEffectXY(){
		return $this->options['effect_xy'];
	}

	private function getUsername(){
		return $this->options['username'];
	}

	private function getCoversize(){
		return $this->options['cover_size'];
	}

	private function getExpiration(){
		return $this->options['expiration'] * 60;
	}

	private function getNoCoverUrl(){
		return $this->options['nocoverurl'];
	}

	private function getTransient($name){
		return $this->transients[$name];
	}

	/*
	 * get artist image
	 */
	private function get_artist_image($artistname, $cover_size, $nocoverurl){
		$artistimage = new DOMDocument();
		try{
			set_error_handler(array($this,'handle_xml_errors'));
			if ($artistimage->load('http://ws.audioscrobbler.com/2.0/?method=artist.getinfo&artist='.urlencode($artistname).'&limit=1&api_key='.$this->api_key)){
				$t_image = $artistimage->getElementsByTagName('image')->item($cover_size);
				restore_error_handler();
				if (isset($t_image->nodeValue) && ($t_image->nodeValue) )
					return $t_image->nodeValue; 
				else
					return $nocoverurl;
			}
			else
				return $nocoverurl;
		}
		catch (Exception $e){
			if ($this->getDebug() === true)
				echo $e->getMessage();
			return $nocoverurl;
		}
	}
	
	/*
	 * get album by mbid
	 */
	private function get_album_image($mbid, $cover_size){
		$albumimage = new DOMDocument();
		try{
			set_error_handler(array($this,'handle_xml_errors'));
			if ($albumimage->load('http://ws.audioscrobbler.com/2.0/?method=album.getinfo&mbid='.$mbid.'&api_key='.$this->api_key)){
				$t_image = $albumimage->getElementsByTagName('image')->item($cover_size);
				restore_error_handler();
				if (isset($t_image->nodeValue) && ($t_image->nodeValue) )
					return $t_image->nodeValue; 
				else
					return false;
			}
			else
				return false;
		}
		catch (Exception $e){
			if ($this->getDebug() === true)
				echo $e->getMessage();
			return false;
		}
	}
	
	/*
	 * get album image by artist and album title
	 */
	private function get_album_image2($artistname, $albumtitle, $cover_size){
		$albumimage = new DOMDocument();
		try{
			set_error_handler(array($this,'handle_xml_errors'));
			if ($albumimage->load('http://ws.audioscrobbler.com/2.0/?method=album.getinfo&artist='.urlencode($artistname).'&album='.urlencode($albumtitle).'&api_key='.$this->api_key)){
				$t_image = $albumimage->getElementsByTagName('image')->item($cover_size);
				restore_error_handler();
				if (isset($t_image->nodeValue) && ($t_image->nodeValue) )
					return $t_image->nodeValue; 
				else
					return false;
			}
		}
		catch (Exception $e){
			if ($this->getDebug() === true)
				echo $e->getMessage();
			return false;
		}
	}
	
	/*
	 * get friends list
	 */
	private function get_friends($count=false, $echotitle=false, $caller=''){
		$transient = $this->getTransient('friends');
		if ($caller == '')
			$op = false;
		else
			$op = get_transient($transient);
		if ($op === false){
			$avatar_size = $this->getCoversize();
			$username = $this->getUsername();
			$effect = $this->getEffect();
			$effect_xy = $this->getEffectXY();
			$friends = new DOMDocument();
			$title = $this->options['friends_title'];
			if ($this->getDebug() === true)
				printf(__('%s data not cached. ','lastfm_tabs'), $title);
			if ($count !== false && intval($count) > 0)
				$friendscount = $count;
			else
				$friendscount = $this->options['friendscount'];
			try{
				set_error_handler(array($this,'handle_xml_errors'));
				if($friends->load('http://ws.audioscrobbler.com/2.0/?method=user.getfriends&user='.$username.'&limit='.$friendscount.'&api_key='.$this->api_key)){
					//cover info header
					if ($echotitle !== false)
						$op = '<h2>'.$title.'</h2>';
					$i=1;
					foreach ($friends->getElementsByTagName('user') as $node){
						$friendsavatar = $node->getElementsByTagName('image')->item($avatar_size)->nodeValue;
						$friendsurl = $node->getElementsByTagName('url')->item(0)->nodeValue;
						$friendsname = $node->getElementsByTagName('name')->item(0)->nodeValue;
						$op .= '<div class="lastfm_record">
									<a class="mosaic-overlay '.$effect .' ' .$effect_xy.'" href="'.$friendsurl.'" target="_blank" title="'.$friendsname.'">
										<div class="details">
											<p>'.$friendsname.'</p>
										</div>
									</a>
									<a href="'.$friendsurl.'" target="_blank"><img src="'.$friendsavatar.'" alt="'.$friendsname.'" title="'.$friendsname.'"/></a>
								</div>'."\n";
						if($i++ >= $friendscount)
							break;
					}
				}
				restore_error_handler();
			}
			catch (Exception $e){
				if ($this->getDebug() === true)
					echo $e->getMessage();
			}
			if ($caller != '')
				set_transient($transient, $op, $this->getExpiration());
		}
		elseif ($this->getDebug() === true)
			printf(__('%s data was cached. ','lastfm_tabs'), 'Friends');
		return '<li class="records_tab" id="lastfmfriends'.$caller.'">'.$op.'</li>'."\n";;
	}
	
	/*
	 * get list of favourite tracks
	 */
	private function get_loved_tracks($count=false, $echotitle=false, $caller=''){
		$transient = $this->getTransient('loved');
		if ($caller == '')
			$op = false;
		else
			$op = get_transient($transient);
		if ($op === false){
			$cover_size = $this->getCoversize();
			$nocoverurl = $this->getNoCoverUrl();
			$username = $this->getUsername();
			$effect_xy = $this->getEffectXY();
			$effect = $this->getEffect();
			$lovedtracks = new DOMDocument();
			$title = $this->options['loved_title'];
			if ($this->getDebug() === true)
				printf(__('%s data not cached. ','lastfm_tabs'), $title);
			if ($count !== false && intval($count) > 0)
				$lovedcount = $count;
			else
				$lovedcount = $this->options['lovedcount'];

			try{
				set_error_handler(array($this,'handle_xml_errors'));
				if ($lovedtracks->load('http://ws.audioscrobbler.com/2.0/?method=user.getlovedtracks&user='.$username.'&limit='.$lovedcount.'&api_key='.$this->api_key)){
					if ($echotitle !== false)
						$op = '<h2>'.$title.'</h2>';			
					$i=1;
					foreach ($lovedtracks->getElementsByTagName('track') as $node){
						$lovedurl = $node->getElementsByTagName('url')->item(0)->nodeValue;
						$t_artist = $node->getElementsByTagName('artist')->item(0);
						$artistname = $t_artist->getElementsByTagName('name')->item(0)->nodeValue;
						$artisturl = $t_artist->getElementsByTagName('url')->item(0)->nodeValue;
						$lovedtrack = $node->getElementsByTagName('name')->item(0)->nodeValue;
						$t_lovedcover = $node->getElementsByTagName('image')->item($cover_size);
						//set cover for not found cover art
						if (isset($t_lovedcover->nodeValue) && ($t_lovedcover->nodeValue) )
							$lovedcover = $t_lovedcover->nodeValue; 
						elseif (isset($this->options['enable_artistimage']) && intval($this->options['enable_artistimage'])) 
							$lovedcover = $this->get_artist_image($artistname, $cover_size, $nocoverurl);
						else 
							$lovedcover = $nocoverurl;
						$op .= '<div class="lastfm_record">
									<a class="mosaic-overlay '.$effect.' '.$effect_xy.'" href="'.$lovedurl.'" target="_blank" title="'.$lovedtrack.'">
										<div class="details">
											<p>'.$artistname.'</p>
											<p>'.$lovedtrack.'</p>
										</div>
									</a>
									<a href="'.$lovedurl.'" target="_blank">
										<img src="'.$lovedcover.'" alt="'.$lovedtrack.'" />
									</a>
								</div>'."\n";
						if($i++ >= $lovedcount)
							break;
					}
				}
				restore_error_handler();
			}
			catch (Exception $e){
				if ($this->getDebug() === true)
					echo $e->getMessage();
			}
			if ($caller != '')
				set_transient($transient, $op, $this->getExpiration());
		}
		elseif ($this->getDebug() === true)
			printf(__('%s data was cached. ','lastfm_tabs'), 'Favorites');
		return '<li class="records_tab" id="lastfmloved'.$caller.'">'.$op.'</li>'."\n";
	}
	
	/*
	 * get list of shouts
	 */
	private function get_shouts($count=false, $echotitle=false, $caller=''){
		$transient = $this->getTransient('shouts');
		if ($caller == '')
			$op = false;
		else
			$op = get_transient($transient);		
		if ($op === false){
			$username = $this->getUsername();
			$shouts = new DOMDocument();
			$title = $this->options['shouts_title'];
			if ($this->getDebug() === true)
				printf(__('%s data not cached. ','lastfm_tabs'), $title);
			if ($count !== false && intval($count) > 0)
				$shoutcount = $count;
			else
				$shoutcount = $this->options['shoutcount'];
			try{
				set_error_handler(array($this,'handle_xml_errors'));
				if ($shouts->load('http://ws.audioscrobbler.com/2.0/?method=user.getshouts&user='.$username.'&api_key='.$this->api_key)){	
					if ($echotitle !== false)
						$op = '<h2>'.$title.'</h2>';
					$i=1;
					foreach ($shouts->getElementsByTagName('shout') as $node){
						$shout = $node->getElementsByTagName('body')->item(0)->nodeValue;
						$author = $node->getElementsByTagName('author')->item(0)->nodeValue;
						$date = $node->getElementsByTagName('date')->item(0)->nodeValue;
						$op .= '<div class="lastfm_shout"><p>'.$shout.'</p><p>'.$author.'</p><p>'.$date.'</p></div>'."\n";
						if($i++ >= $shoutcount)
							break;
					}
				}
				restore_error_handler();
			}
			catch (Exception $e){
				if ($this->getDebug() === true)
					echo $e->getMessage();
			}
			if ($caller != '')
				set_transient($transient, $op, $this->getExpiration());
		}
		elseif ($this->getDebug() === true)
			printf(__('%s data was cached. ','lastfm_tabs'), 'Shouts');
		return '<li class="records_tab" id="lastfmshouts'.$caller.'">'.$op.'</li>'."\n";
	}
	
	/*
	 * get user information
	 */
	private function get_userinfo($echotitle=false, $caller=''){
		$transient = $this->getTransient('info');
		if ($caller == '')
			$op = false;
		else
			$op = get_transient($transient);		
		if ($op === false){
			$username = $this->getUsername();
			$cover_size = $this->getCoversize();
			$userinfo = new DOMDocument();
			$title = $this->options['info_title'];
			if ($this->getDebug() === true)
				printf(__('%s data not cached. ','lastfm_tabs'), $title);
			try{
				set_error_handler(array($this,'handle_xml_errors'));
				if ($userinfo->load('http://ws.audioscrobbler.com/2.0/?method=user.getinfo&user='.$username.'&api_key='.$this->api_key)){
					$id = $userinfo->getElementsByTagName('id')->item(0)->nodeValue;
					$name = $userinfo->getElementsByTagName('name')->item(0)->nodeValue; 
					$real = $userinfo->getElementsByTagName('realname')->item(0)->nodeValue;
					$user_url = $userinfo->getElementsByTagName('url')->item(0)->nodeValue;
					$userimage = $userinfo->getElementsByTagName('image')->item($cover_size)->nodeValue;
					$country = $userinfo->getElementsByTagName('country')->item(0)->nodeValue;
					$age = $userinfo->getElementsByTagName('age')->item(0)->nodeValue;
					$gender = $userinfo->getElementsByTagName('gender')->item(0)->nodeValue;
					$playcount = $userinfo->getElementsByTagName('playcount')->item(0)->nodeValue;
					$registered = $userinfo->getElementsByTagName('registered')->item(0)->nodeValue;
				}
				if ($echotitle !== false)
					$op = '<h2>'.$title.'</h2>';
				$op .=	"\n".'
						<p><img src="'.$userimage.'" alt="'.$name.'"/></p>
						<p>'. __('ID: ','lastfm_tabs').$id.'</p>
						<p>'. __('Nick: ','lastfm_tabs').$name.'</p>
						<p>'. __('Name: ','lastfm_tabs').$real.'</p>
						<p>'. __('Country: ','lastfm_tabs').$country.'</p>
						<p>'. __('Age: ','lastfm_tabs').$age.'</p>
						<p>'. __('Gender: ','lastfm_tabs').$gender.'</p>
						<p>'. __('Playcount: ','lastfm_tabs').$playcount.'</p>
						<p><a href="'.$user_url.'">'. __('Profilepage','lastfm_tabs').'</a></p>
						<p>'. __('Registered: ','lastfm_tabs').$registered.'</p>'."\n";
				if ($caller != '')
					set_transient($transient, $op, $this->getExpiration());
				restore_error_handler();
			}
			catch (Exception $e){
				if ($this->getDebug() === true)
					echo $e->getMessage();
			}			
		}
		elseif ($this->getDebug() === true)
			printf(__('%s data was cached. ','lastfm_tabs'), 'Profile');
		return '<li class="records_tab" id="lastfminfo'.$caller.'">'.$op.'</li>'."\n";
	}
	
	/*
	 * get charts
	 */
	private function get_charts($count=false, $echotitle=false, $caller=''){
		$transient = $this->getTransient('charts');
		if ($caller == '')
			$op = false;
		else
			$op = get_transient($transient);
		if ($op === false){
			$username = $this->getUsername();
			$nocoverurl = $this->getNoCoverUrl();
			$cover_size = $this->getCoversize();
			$effect = $this->getEffect();
			$effect_xy = $this->getEffectXY();
			$charts = new DOMDocument();
			$title = $this->options['charts_title'];
			if ($this->getDebug() === true)
				printf(__('%s data not cached. ','lastfm_tabs'), $title);
			if ($count !== false && intval($count) > 0)
				$chartscount = $count;
			else
				$chartscount = $this->options['chartscount'];
			try{
				set_error_handler(array($this,'handle_xml_errors'));
				if ($charts->load('http://ws.audioscrobbler.com/2.0/?method=user.getWeeklyAlbumChart&user='.$username.'&api_key='.$this->api_key)){
					if ($echotitle !== false)
						$op = '<h2>'.$title.'</h2>';
					$i=1;
					foreach ($charts->getElementsByTagName('album') as $node){
						$artistname = $node->getElementsByTagName('artist')->item(0)->nodeValue;
						$mbid = $node->getElementsByTagName('mbid')->item(0)->nodeValue;
						$albumname = $node->getElementsByTagName('name')->item(0)->nodeValue;
						$playcount = $node->getElementsByTagName('playcount')->item(0)->nodeValue;
						$url = $node->getElementsByTagName('url')->item(0)->nodeValue;
						
						if ($mbid != '')
							$chartcover = $this->get_album_image($mbid, $cover_size);
						else
							$chartcover = $this->get_album_image2($artistname, $albumname, $cover_size);

						if ( $chartcover == false && isset($this->options['enable_artistimage']) && intval($this->options['enable_artistimage']))
							$chartcover = $this->get_artist_image($artistname, $cover_size, $nocoverurl);
						elseif ( $chartcover == false )
							$chartcover = $nocoverurl;
			
						$op .= '<div class="lastfm_record">
									<a class="mosaic-overlay '.$effect.' '. $effect_xy .'" href="'.$url.'" target="_blank" title="'.$artistname.'">
										<div class="details">
											<p>'.$artistname.'</p>
											<p>'.$playcount.'</p>
										</div>
									</a>
									<a href="'.$url.'" target="_blank" title="'.$artistname.'">
										<img src="'.$chartcover.'" alt="'.$artistname.'" title="'.$artistname.'" />
									</a>
								</div>'."\n";
						if($i++ >= $chartscount)
							break;
					}
				}
				restore_error_handler();
			}
			catch (Exception $e){
				if ($this->getDebug() === true)
					echo $e->getMessage();
			}
			if ($caller != '')
				set_transient($transient, $op, $this->getExpiration());
		}
		elseif ($this->getDebug() === true)
			printf(__('%s data was cached. ','lastfm_tabs'), 'Charts');
		return '<li class="records_tab" id="lastfmcharts'.$caller.'">'.$op.'</li>'."\n";
	}	

	/*
	 * get top albums
	 */
	private function get_top_albums($count=false, $echotitle=false, $caller=''){
		$transient = $this->getTransient('top_albums');
		if ($caller == '')
			$op = false;
		else
			$op = get_transient($transient);
		if ($op === false){
			$username = $this->getUsername();
			$nocoverurl = $this->getNoCoverUrl();
			$cover_size = $this->getCoversize();
			$period = $this->options['top_albums_period'];
			$effect = $this->getEffect();
			$effect_xy = $this->getEffectXY();
			$title = $this->options['top_albums_title'];
			if ($this->getDebug() === true)
				printf(__('%s data not cached. ','lastfm_tabs'), $title);
			if ($count !== false && intval($count) > 0)
				$topalbumscount = $count;
			else
				$topalbumscount = $this->options['top_albums_count'];
			switch($period){
				case 0:
					$period = 'overall';
					break;
				case 1:
					$period = '7day';
					break;
				case 2:
					$period = '3month';
					break;
				case 3:
					$period = '6month';
					break;
				case 4:
				default:
					$period = '12month';
					break;
			}
			$topalbums = new DOMDocument();
			try{
				set_error_handler(array($this,'handle_xml_errors'));
				if ($topalbums->load('http://ws.audioscrobbler.com/2.0/?method=user.getTopAlbums&user='.$username.'&period='.$period.'&limit='.$topalbumscount.'&api_key='.$this->api_key)){
					if ($echotitle !== false)
						$op = '<h2>'.$title.'</h2>';
					$i=1;
					foreach ($topalbums->getElementsByTagName('album') as $node){
						$artistname = $node->getElementsByTagName('artist')->item(0);
						foreach ($artistname->getElementsByTagName('name') as $name)
							$artistname = $name->nodeValue;
						$albumname = $node->getElementsByTagName('name')->item(0)->nodeValue;
						$playcount = $node->getElementsByTagName('playcount')->item(0)->nodeValue;
						$url = $node->getElementsByTagName('url')->item(0)->nodeValue;
						$t_cover = $node->getElementsByTagName('image')->item($cover_size);				
						//set cover for not found cover art
						if (isset($t_cover->nodeValue) && ($t_cover->nodeValue) )
							$cover = $t_cover->nodeValue; 
						elseif (isset($this->options['enable_artistimage']) && intval($this->options['enable_artistimage']))
							$cover = $this->get_artist_image($artistname, $cover_size, $nocoverurl);
						else
							$cover = $nocoverurl;
						$op .= '<div class="lastfm_record">
									<a class="mosaic-overlay '.$effect . ' '. $effect_xy .'" href="'.$url.'" target="_blank" title="'.$albumname.'">
										<div class="details">
											<p>'.$albumname.'</p>
											<p>'.__('by ','lastfm_tabs') . $artistname.'</p>
											<p>'.sprintf(__("Played %d times.",'lastfm_tabs'), $playcount) .'</p>
										</div>
									</a>
									<a href="'.$url.'" target="_blank" title="'.$albumname.'">
										<img src="'.$cover.'" alt="'.$albumname.'" title="'.$albumname.'" />
									</a>
								</div>'."\n";
						if($i++ >= $topalbumscount)
							break;
					}
				}
				restore_error_handler();
			}
			catch (Exception $e){
				if ($this->getDebug() === true)
					echo $e->getMessage();
			}
			if ($caller != '')
				set_transient($transient, $op, $this->getExpiration());
		}
		elseif ($this->getDebug() === true)
			printf(__('%s data was cached. ','lastfm_tabs'), 'Top albums');
		return '<li class="records_tab" id="lastfmtopalbums'.$caller.'">'.$op.'</li>'."\n";
	}

	/*
	 * get top artists
	 */
	private function get_top_artists($count=false, $echotitle=false, $caller=''){
		$transient = $this->getTransient('top_artists');
		if ($caller == '')
			$op = false;
		else
			$op = get_transient($transient);
		if ($op === false){
			$title = $this->options['top_artists_title'];
			$username = $this->getUsername();
			$nocoverurl = $this->getNoCoverUrl();
			$cover_size = $this->getCoversize();
			$topartists = new DOMDocument();
			$period = $this->options['top_artists_period'];
			$effect = $this->getEffect();
			$effect_xy = $this->getEffectXY();
			if ($this->getDebug() === true)
				printf(__('%s data not cached. ','lastfm_tabs'), $title);
			if ($count !== false && intval($count) > 0)
				$topartistscount = $count;
			else
				$topartistscount = $this->options['top_artists_count'];
			switch($period){
				case 0:
					$period = 'overall';
					break;
				case 1:
					$period = '7day';
					break;
				case 2:
					$period = '3month';
					break;
				case 3:
					$period = '6month';
					break;
				case 4:
				default:
					$period = '12month';
					break;
			}
			try{
				set_error_handler(array($this,'handle_xml_errors'));
				if ($topartists->load('http://ws.audioscrobbler.com/2.0/?method=user.getTopArtists&user='.$username.'&period='.$period.'&limit='.$topartistscount.'&api_key='.$this->api_key)){
					if ($echotitle !== false)
						$op = '<h2>'.$title.'</h2>';
					$i=1;
					foreach ($topartists->getElementsByTagName('artist') as $node){
						$artistname = $node->getElementsByTagName('name')->item(0)->nodeValue;
						$playcount = $node->getElementsByTagName('playcount')->item(0)->nodeValue;
						$url = $node->getElementsByTagName('url')->item(0)->nodeValue;
						$t_cover = $node->getElementsByTagName('image')->item($cover_size);
						//set cover for not found cover art
						if (isset($t_cover->nodeValue) && ($t_cover->nodeValue) )
							$cover = $t_cover->nodeValue; 
						elseif (isset($this->options['enable_artistimage']) && intval($this->options['enable_artistimage']))
							$cover = $this->get_artist_image($artistname, $cover_size, $nocoverurl);
						else
							$cover = $nocoverurl;
						$op .= '<div class="lastfm_record">
									<a class="mosaic-overlay '.$effect.' '.$effect_xy.'" href="'.$url.'" target="_blank" title="'.$artistname.'">
										<div class="details">
											<p>'.$artistname.'</p>
											<p>'.sprintf(__("Played %d times.",'lastfm_tabs'), $playcount) .'</p>
										</div>
									</a>
									<a href="'.$url.'" target="_blank" title="'.$artistname.'">
										<img src="'.$cover.'" alt="'.$artistname.'" title="'.$artistname.'" />
									</a>
								</div>'."\n";
						if($i++ >= $topartistscount)
							break;
					}
				}
				restore_error_handler();
			}
			catch (Exception $e){
				if ($this->getDebug() === true)
					echo $e->getMessage();
			}
			if ($caller != '')
				set_transient($transient, $op, $this->getExpiration());
		}
		elseif ($this->getDebug() === true)
			printf(__('%s data was cached. ','lastfm_tabs'), 'Top artists');
		return '<li class="records_tab" id="lastfmtopartists'.$caller.'">'.$op.'</li>'."\n";
	}
	
	/*
	 * get recently played tracks
	 */	
	private function get_recently_played($count=false, $echotitle=false, $caller=''){
		$transient = $this->getTransient('recently');
		if ($caller == '')
			$op = false;
		else
			$op = get_transient($transient);
		if ($op === false ){
			$cover_size = $this->getCoversize();
			$nocoverurl = $this->getNoCoverUrl();
			$username = $this->getUsername();
			$effect = $this->getEffect();
			$effect_xy = $this->getEffectXY();
			$coverinfo = new DOMDocument();
			$title = $this->options['recently_title'];		
			if ($this->getDebug() === true)
				printf(__('%s data not cached. ','lastfm_tabs'), $title);
			if ($count !== false && intval($count) > 0)
				$trackcount = $count;
			else
				$trackcount = $this->options['trackcount'];
			if ($echotitle !== false)
				$op = '<h2>'.$title.'</h2>';
			try{
				set_error_handler(array($this,'handle_xml_errors'));
				if ($coverinfo->load('http://ws.audioscrobbler.com/2.0/?method=user.getrecenttracks&user='.$username.'&limit='.$trackcount.'&api_key='.$this->api_key)){
					$i = 1;
					foreach ($coverinfo->getElementsByTagName('track') as $node){
						$song = $node->getElementsByTagName('name')->item(0)->nodeValue;
						$artist = $node->getElementsByTagName('artist')->item(0)->nodeValue;
						$url = $node->getElementsByTagName('url')->item(0)->nodeValue;
						$t_cover = $node->getElementsByTagName('image')->item($cover_size);
						//set cover for not found cover art
						if (isset($t_cover->nodeValue) && ($t_cover->nodeValue) )
							$cover = $t_cover->nodeValue; 
						elseif (isset($this->options['enable_artistimage']) && intval($this->options['enable_artistimage']))
							$cover = $this->get_artist_image($artist, $cover_size, $nocoverurl);
						else
							$cover = $nocoverurl;						
						$op .= '<div class="lastfm_record">
									<a class="mosaic-overlay '.$effect . ' '. $effect_xy .'" href="'.$url.'" target="_blank" title="'.$artist.'">
										<div class="details">
											<p>'.$song.'</p>
											<p>'.$artist.'</p>
										</div>
									</a>
									<a href="'.$url.'" target="_blank">
										<img src="'.$cover.'" alt="'.$song.'" title="'.$artist.' - '.$song.'"/>
									</a>
								</div>'."\n";
						if($i++ >= $trackcount)
							break;
					}
				}
				restore_error_handler();
			}
			catch (Exception $e){
				if ($this->getDebug() === true)
					echo $e->getMessage();
			}
			if ($caller != '')
				set_transient($transient, $op, $this->getExpiration());
		}
		elseif ($this->getDebug() === true)
			printf(__('%s data was cached. ','lastfm_tabs'), 'Recently played');
		return '<li class="records_tab" id="lastfmcovers'.$caller.'">'.$op.'</li>'."\n";
	}
	
	/*
	 * build widget output
	 */
	function tabs_main($args){
		extract($args);
		$widgettitle = $this->options['widget_title'];		
		
		$tabnav = '';
		$widgets = '';
		
		//build navigation and tabs if enabled
		if (isset($this->options['enable_recently']) && intval($this->options['enable_recently'])){
			$widgets .= $this->get_recently_played(); 
			$tabnav .= '<li><a href="#lastfmcovers">'.$this->options['recently_title'].'</a></li>';
		}
		if (isset($this->options['enable_lovedtracks']) && intval($this->options['enable_lovedtracks'])){
			$widgets .= $this->get_loved_tracks();
			$tabnav .= '<li><a href="#lastfmloved">'.$this->options['loved_title'].'</a></li>';
		}
		if (isset($this->options['enable_charts']) && intval($this->options['enable_charts'])){
			$widgets .= $this->get_charts(); 
			$tabnav .= '<li><a href="#lastfmcharts">'.$this->options['charts_title'].'</a></li>';
		}
		if (isset($this->options['enable_top_albums']) && intval($this->options['enable_top_albums'])){
			$widgets .= $this->get_top_albums(); 
			$tabnav .= '<li><a href="#lastfmtopalbums">'.$this->options['top_albums_title'].'</a></li>';
		}
		if (isset($this->options['enable_top_artists']) && intval($this->options['enable_top_artists'])){
			$widgets .= $this->get_top_artists(); 
			$tabnav .= '<li><a href="#lastfmtopartists">'.$this->options['top_artists_title'].'</a></li>';
		}
		if (isset($this->options['enable_friends']) && intval($this->options['enable_friends'])){
			$widgets .= $this->get_friends();
			$tabnav .= '<li><a href="#lastfmfriends">'.$this->options['friends_title'].'</a></li>';
		}
		if (isset($this->options['enable_info']) && intval($this->options['enable_info'])){
			$widgets .= $this->get_userinfo();
			$tabnav .= '<li><a href="#lastfminfo">'.$this->options['info_title'].'</a></li>';
		}
		if (isset($this->options['enable_shouts']) && intval($this->options['enable_shouts'])){
			$widgets .= $this->get_shouts(); 
			$tabnav .= '<li><a href="#lastfmshouts">'.$this->options['shouts_title'].'</a></li>';
		}		
		//tabbed navigation		
		$tabnav = '<ul class="idTabs molastfm clearfix">'.$tabnav.'</ul>'."\n";
		//widget start
		$widget_output = $before_widget . $before_title . $widgettitle . $after_title. "\n";
		$widget_output .= '<div id="lastfmrecords">'.$tabnav.'<ul id="lastfm_inside">';
	
		//echo cache info in debug mode
		if ($this->getDebug() === true){
			$timestamp = time();
			$extime = date("H:i:s", $timestamp);
			printf(__('Cacherefresh done at %s','lastfm_tabs'), $extime);
		}
		$widget_output .= $widgets.'</ul></div>' . $after_widget . "\n";
		return $widget_output;
	}
	
	/*
	 * widget output
	 */
	function widget_output($args){
		$transient = $this->getTransient('widget');
		$op = get_transient($transient);
		if ($op === false){
			if ($this->getDebug() === true)
				_e('Data not cached.','lastfm_tabs');
			$op = $this->tabs_main($args, $this->options);
			set_transient($transient, $op, $this->getExpiration());
		}
		elseif ($this->getDebug() === true)
			printf(__('%s data was cached. ','lastfm_tabs'), 'Widget');
		echo $op;
	}

	/*
	 * template tag function
	 */
	public function lastfm_tabs_tag($functions=array(), $count=false, $echotitle=false){
		if ($echotitle !== false)
			$echotitle = true;
		if ($count === false || intval($count) <= 0)		
			$count = 3;
		$caller = 'template';
/*
		$transient = 'lastfm_tabs_template_cache';		
		$op = get_transient($transient);
*/
		$op = false;
		if ($op === false){
			if ($this->getDebug() === true)
				_e('Data not cached.','lastfm_tabs');
			$op = '';
			if (!is_array($functions))
				$functions = array($functions);
			foreach($functions as $function){
				switch($function){
					case('recent'):
						$op .= $this->get_recently_played($count, $echotitle, $caller);
						break;
					case('friends'):
						$op .= $this->get_friends($count, $echotitle, $caller);
						break;
					case('info'):
						$op .= $this->get_userinfo($echotitle, $caller);
						break;
					case('charts'):
						$op .= $this->get_charts($count, $echotitle, $caller);
						break;
					case('artists'):
						$op .= $this->get_top_artists($count, $echotitle, $caller);
						break;
					case('albums'):
						$op .= $this->get_top_albums($count, $echotitle, $caller);
						break;
					case('shouts'):
						$op .= $this->get_shouts($count, $echotitle, $caller);
						break;
					case('loved'):
						$op .= $this->get_loved_tracks($count, $echotitle, $caller);
						break;
					default:
						$op .= __('Invalid function call','lastfm_tabs') . ' "' . $function .'". ' . __('Use ','lastfm_tabs') . ' friends, info, charts, artists, albums, shouts, recent '.__('or','lastfm_tabs').' loved';
						return $op;
				}
		}
		$op = '<ul class="lastfm_widget">'.$op.'</ul>';
//		set_transient($transient, $op, $this->getExpiration());
		}
/*
		elseif ($this->getDebug() === true)
			_e('Data was cached.','lastfm_tabs');
*/
		return $op;
	}
	
	/*
	 * enable slide/fade effect
	 */
	function hover(){
		if (isset($this->options['fancyness']) && intval($this->options['fancyness'])){
			//wp_register_script('fancy_hover',LASTFM_URI.'/fancy_hover.js',array('jquery'),'1.0');
			wp_register_script('mosaic',LASTFM_URI.'/js/mosaic.min.js',array('jquery'),'1.0.1');
			//wp_register_script('lastfm_ajax',LASTFM_URI.'/js/ajax.js',array('jquery'),'1.0.1');
			//wp_enqueue_script('lastfm_ajax');
			wp_enqueue_script('mosaic');
		}
	}

	/*
	 * enable tabbed navigation?
	 */
	function tabs(){
		if (isset($this->options['enable_tabs']) && intval($this->options['enable_tabs'])){
			wp_register_script('idtabs',LASTFM_URI.'/js/idtab.js',array('jquery'),'2.2');
			wp_enqueue_script('idtabs');
		}
	}
	
	/*
	 * optional stylesheets
	 */
	function style(){
		if (isset($this->options['use_stylesheet']) && !intval($this->options['use_stylesheet']))
			return;
		if (@file_exists(STYLESHEETPATH.'/lastfm.css'))
			$css_file = get_stylesheet_directory_uri() . '/lastfm.css';
		elseif (@file_exists(TEMPLATEPATH.'/lastfm.css'))
			$css_file = get_template_directory_uri() . '/lastfm.css';	
		else
			$css_file = plugins_url('css/lastfm_tabs.css', __FILE__);
		wp_enqueue_style('lastfm_tabs_mosaic', $css_file, false, '1.0.1', 'all');
		add_action('wp_head', array($this,'mosaic_init'));
	}

	/*
	 * mosaic initialisation
	 */	
	function mosaic_init(){
		$origin = $this->getEffectXY();
		?>
		<script type="text/javascript">
			jQuery(function(){
				jQuery('.lastfm_record').mosaic({
					animation	: '<?php echo $this->options['effect'];?>',
					speed		: <?php echo $this->options['effect_duration'];?>,
					<?php switch($origin){
						case 'left':
							echo 'anchor_x : \'left\'';
							break;
						case 'right':
							echo 'anchor_x : \'right\'';
							break;
						case 'top':
							echo 'anchor_y : \'top\'';
							break;
						default:
						case 'bottom':
							echo 'anchor_y : \'bottom\'';
							break;
					}?>
				});
			});	
		</script>
	<?php
	}
}

$lastfm_tabs = new lastfm_tabs();
$lastfm_options = new lastfm_options($lastfm_tabs);

function lastfm_tabs_render($functions=array(), $count=false, $echotitle=false, $return=false){
	$lastfm_tabs = new lastfm_tabs();
	if ($return === true)
		return $lastfm_tabs->lastfm_tabs_tag($functions, $count, $echotitle);
	else
		echo $lastfm_tabs->lastfm_tabs_tag($functions, $count, $echotitle);
}

?>