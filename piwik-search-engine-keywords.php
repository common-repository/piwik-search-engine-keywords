<?php

/*
  Plugin Name: PiwikSearchEngineKeywords

  Plugin URI: https://wordpress.org/extend/plugins/piwik-search-engine-keywords/
  Description: Displays incoming queries from search engines like Google.
  Version: 0.4.0
  Author: Maxim
  Author URI: http://www.virtual-maxim.de

 */




/*
 * Options in WP database (Name: __CLASS__):
 *
 * showbox - is output box visible? (1/0)
 * widgettitle - title of widget box
 * postboxtitle - title of output box in a post
 * keywordslimit - number of maximum keywords in output box
 * configfile - path to piwik configuration file (config.ini.php)
 * nocss - do not include css-file
 * linkson - show keywords as links
 * separator - keywords separator
 */

//define('WP_DEBUG', true);
//
// if you don't include 'index.php', you must also define PIWIK_DOCUMENT_ROOT
//define('PIWIK_INCLUDE_PATH', $_SERVER['DOCUMENT_ROOT'] . "/piwik");
define('PIWIK_ENABLE_DISPATCH', false);
define('PIWIK_ENABLE_ERROR_HANDLER', false);
define('PIWIK_ENABLE_SESSION_START', false);


/**
 *  define class if not exists
 */
if (!class_exists('PiwikSearchEngineKeywords'))
{

    /**
     *  Plugin class
     *
     */
    class PiwikSearchEngineKeywords
    {

        private $PluginDir;
        private $piwikDir;
        private $keywordsLimit;
        private $outputBoxTitle;
        private $noCSS;
        private $showBox;
        private $showAsLinks;
        private $separator;
        private $isready;
        private $authCode;
        private $siteID;

        /**
         * Constructor
         *
         */
        public function __construct()
        {
            $this->isready = false;

            // set plugin path
            $this->PluginDir = WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__));

            load_plugin_textdomain('psek', false, dirname(plugin_basename(__FILE__)) . '/languages');

            $options = get_option(__CLASS__);
            $this->keywordsLimit = $options['keywordslimit'];
            $this->outputBoxTitle = $options['postboxtitle'];
            $this->noCSS = $options['nocss'];
            $this->showBox = $options['showbox'];
            $this->showAsLinks = $options['linkson'];
            $this->separator = $options['separator'];
            $this->authCode = $options['authcode'];
            $this->piwikDir = $options['piwikdir'];
            $this->siteID = $options['siteID'];

            // add '/' if needed
            if ($options['piwikdir'][strlen($options['piwikdir']) - 1] != '/')
                $options['piwikdir'] = $options['piwikdir'] . '/';

            register_activation_hook(__FILE__, array($this, 'Install'));

            add_action('widgets_init', array($this, 'ActivateWidget'));

            add_action('admin_menu', array($this, 'AddMenu'));

            if ((int) ($this->noCSS) == 0)
                add_action('wp_head', array($this, 'IncludeCSS'));

            add_filter('the_content', array($this, 'ShowKeywords'));
        }

        function Message_1()
        {
            echo "<br /><div id='psek_warning' class='updated fade'><p>" . __('Settings saved', 'psek') . '.</p></div>';
        }

        private function ShowMessage($msg_nr)
        {
            add_action('admin_notices', array($this, ('Message_' . $msg_nr)));
        }

        /**
         *  Include CSS-file in wordpress-head
         *  @return string
         */
        function IncludeCSS()
        {
            echo '<link type="text/css" rel="stylesheet" href="' . $this->PluginDir . '/style.css" />';
        }

        /**
         *  Register widget
         */
        public function ActivateWidget()
        {
            if (function_exists('register_sidebar_widget'))
            {
                register_sidebar_widget(array(__CLASS__, 'widgets'), array($this, 'WidgetOutput'));
                register_widget_control(array(__CLASS__, 'widgets'), array($this, 'WidgetControl'), 300, 150);
            }
        }

        public function Install()
        {
            $options = array();
            $options['showbox'] = 0;
            $options['nocss'] = 0;
            $options['linkson'] = 0;
            $options['keywordslimit'] = 10;
            $options['piwikdir'] = "";
            $options['separator'] = "";
            $options['siteID'] = "1";
            $options['authcode'] = "authentication code";
            $options['postboxtitle'] = __('Top keywords:', 'psek');

            add_option(__CLASS__, $options);
        }

        public function AddMenu()
        {
            add_options_page('PiwikSEK ' . __('Settings', 'psek'), 'PiwikSEK', 9, __FILE__, array($this, 'OptionPage'));
        }
        
        
        
        // make API-request to get last keywords
        private function getKeywordsViaPiwikAPI($url = "")
        {          
                // make API-request for the keyword data
                $request = $this->piwikDir."?module=API&method=Referers.getKeywordsForPageUrl&format=php&filter_limit=".$this->keywordsLimit."&token_auth=".$this->authCode."&date=previous1&period=week&idSite=".$this->siteID."&url=".urlencode($url);
                
                $keywords = "";
                
                if(function_exists('curl_init'))
                {          
                    $ch = curl_init();
                    $timeout = 2; // Timeout in seconds
                    curl_setopt($ch, CURLOPT_URL, $request);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
                    $keywords = curl_exec($ch);
                    curl_close($ch);
                }
                else if(ini_get('allow_url_fopen') != 0)
                {
                    // max. request time = 3 second
                    @ini_set("default_socket_timeout", $timeout = 2);
                    $keywords = file_get_contents($request);
                }
                else 
                {
                    return "Install curl_init (recommended) or set allow_url_fopen on 1.";
                }
     
                    
                return @unserialize($keywords);
        }
        
        

        /**
         *
         * @return string
         */
        public function GetKeywords($separator_on = false)
        {

            $pageUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];      
            //$pageUrl = 'http://www.virtual-maxim.de/ini-dateien-lesen-schreiben/';
            // $pageUrl = "http://localhost";
             //echo $_SERVER['HTTP_HOST']."<br>";
            
            $i = 0;
            $keywords = "";
            
            $result = $this->getKeywordsViaPiwikAPI($pageUrl);
            
            //print_r($result);
            
            if($result == '')
                return "";
           
            if($result['result']=='error')
            {
                return "Error: ".$result['message'];
            }
            
            
            foreach($result as $key => $keyword)
            {
                $keyword = htmlspecialchars($keyword[0]);
                
                if($keyword!="Keyword not defined" && $keyword != "Suchbegriff nicht definiert")
                {
                    // show keyword as a URL
                    if (($this->showAsLinks) == 1)
                        $keyword = '<a href="' . htmlspecialchars($pageUrl) . '" >' . $keyword . '</a>';


                    if ($i < count($result) - 1)
                        $keywords = $keywords . '<li>' . $keyword . '</li>' . (($separator_on == true && $this->separator != "") ? '<li class="psek_separator">' . $this->separator . '</li>' : '');
                    else
                        $keywords = $keywords . '<li>' . $keyword . '</li>';
                }
                
                $i++;
            }

            return $keywords;
        }

        /**
         * Show keywords in a <div>-area
         *
         * @param string $content
         * @return string
         */
        function ShowKeywords($content)
        {
            $output = '';
            if (is_single() && (int) ($this->showBox) == 1)
            {
                $keywords = $this->GetKeywords(true);

                if ($keywords != "")
                {
                    $output = '<div class="psek_post_out" ><p>' . $this->outputBoxTitle . '</p><ul>' . $keywords . '</ul></div>';
                }
            }
            return $content . $output;
        }

        /**
         *  Widget output. Show keywords in a <div>-area
         *
         * @param string $args
         * @return string
         */
        function WidgetOutput($args)
        {
            if (is_single ())
            {
                $keywords = $this->GetKeywords();

                if ($keywords == "")
                    return;

                extract($args);

                $options = get_option(__CLASS__);
                $title = $options['widgettitle'];

                echo $before_widget . $before_title . $title . $after_title;
                echo '<div class="psek_widget_out"><ul>' . $keywords . '</ul></div>';
                echo $after_widget;
            }
        }

        /**
         * Widget control menu
         */
        function WidgetControl()
        {
            $options = get_option(__CLASS__);
            if ($_POST['PSEK-widgetsubmit'])
            {
                $options['widgettitle'] = strip_tags(stripslashes($_POST['PSEK-widgettitle']));
                update_option(__CLASS__, $options);
            }

            $widgettitle = htmlspecialchars($options['widgettitle'], ENT_QUOTES);
            echo '<p style="text-align:right;"><label for="PSEK-widgettitle">' . __('Title', 'psek') . ': <input style="width: 200px;" id="PSEK-widgettitle" name="PSEK-widgettitle" type="text" value="' . $widgettitle . '" /></label></p>';
            echo '<input type="hidden" id="PSEK-widgetsubmit" name="PSEK-widgetsubmit" value="1" />';
        }
        

        /**
         * Show plugins options
         */
        function OptionPage()
        {
            $options = get_option(__CLASS__);
            if ($_POST['PSEK-optionssubmit'])
            {
                $options['showbox'] = isset($_POST['PSEK-showbox']) ? 1 : 0;
                $options['nocss'] = isset($_POST['PSEK-nocss']) ? 1 : 0;
                $options['linkson'] = isset($_POST['PSEK-linkson']) ? 1 : 0;
                $options['keywordslimit'] = htmlspecialchars($_POST['PSEK-keywordslimit'], ENT_QUOTES);
                $options['piwikdir'] = htmlspecialchars($_POST['PSEK-piwikdir'], ENT_QUOTES);
                $options['authcode'] = htmlspecialchars($_POST['PSEK-authcode'], ENT_QUOTES);
                $options['siteID'] = htmlspecialchars($_POST['PSEK-siteID'], ENT_QUOTES);
                $options['postboxtitle'] = htmlspecialchars($_POST['PSEK-postboxtitle'], ENT_QUOTES);
                $options['separator'] = htmlspecialchars($_POST['PSEK-separator'], ENT_QUOTES);
                
                if ($options['piwikdir'][strlen($options['piwikdir']) - 1] != '/')
                    $options['piwikdir'] = $options['piwikdir'] . '/';

                update_option(__CLASS__, $options);
            }

            echo '<div style="width:800px"> 
                
                    <div style="clear:both;">
                        <h3 ><span>PiwikSEK ' . __('Settings', 'psek') . '</span></h3>
                       
                        <form name="psek_form" method="post" action="">
                        <label for="PSEK-piwikdir">' . __('Path to Piwik-Directory', 'psek') . ':</label>
                        <input type="text" size="70" id="PSEK-piwikdir" name="PSEK-piwikdir" value="' . (($options['piwikdir'] == "") ? 'http://' . $_SERVER['HTTP_HOST'] . '/piwik/' : $options['piwikdir']) . '" /> <br />
                        <label for="PSEK-authcode">' . __('token_auth', 'psek') . ':</label>
                        <input type="text" size="70" id="PSEK-authcode" name="PSEK-authcode" value="' . (($options['authcode'] == "") ? '' : $options['authcode']) . '" /> <br />
                        <label for="PSEK-siteID">' . __('Website ID', 'psek') . ':</label>
                        <input type="text" size="70" id="PSEK-siteID" name="PSEK-siteID" value="' . (($options['siteID'] == "") ? '1' : $options['siteID']) . '" /> <br />
                        
                        <label for="PSEK-showbox">' . __('Output box in posts?', 'psek') . '</label>
                        <input type="checkbox" name="PSEK-showbox" id="PSEK-showbox" value="1" ' . (($options['showbox'] == "1") ? 'checked="checked"' : "") . ' /><br />
                        <label for="PSEK-nocss">' . __('Exclude CSS file?', 'psek') . '</label>
                        <input type="checkbox" name="PSEK-nocss" id="PSEK-nocss" value="1" ' . (($options['nocss'] == "1") ? 'checked="checked"' : "") . ' /><br />
                        <label for="PSEK-linkson">' . __('Show keywords as links?', 'psek') . '</label>
                        <input type="checkbox" name="PSEK-linkson" id="PSEK-linkson" value="1" ' . (($options['linkson'] == "1") ? 'checked="checked"' : "") . ' /><br />


                        <label for="PSEK-keywordslimit">' . __('Number of keywords', 'psek') . ':</label>
                        <input type="text" maxlength="2" size="2" id="PSEK-keywordslimit" name="PSEK-keywordslimit" value="' . $options['keywordslimit'] . '" /> <br />
                        <label for="PSEK-postboxtitle">' . __('Title of output box', 'psek') . ':</label>
                        <input type="text" size="70" id="PSEK-postboxtitle" name="PSEK-postboxtitle" value="' . $options['postboxtitle'] . '" /> <br />

                        <label for="PSEK-separator">' . __('Keywords separator', 'psek') . ':</label>
                        <input type="text" size="50" id="PSEK-separator" name="PSEK-separator" value="' . $options['separator'] . '" /> <br />


                        <input type="submit" value="' . __('Save', 'psek') . '" name="PSEK-optionssubmit" />
                    </form>
                    </div>
                    
                 
                    <hr style="margin: 20px;">
                  
                  <div style="border:1px solid #8F8F8F; width:180px; padding:8px; float:left; ">
                  <h3>' . __('Donation', 'psek') . '</h3>
                  <p>' . __('If you like PiwikSEK, you can help the development with a donation or a gift.', 'psek') . '</p>
                    <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
                    <input type="hidden" name="cmd" value="_s-xclick" />
                    <input type="hidden" name="hosted_button_id" value="W583B3ATYTACW" />
                    <input type="image" src="https://www.paypal.com/de_DE/DE/i/btn/btn_donate_SM.gif" name="submit" alt="Jetzt einfach, schnell und sicher online bezahlen – mit PayPal." />
                    <img alt="pixel"  src="https://www.paypal.com/de_DE/i/scr/pixel.gif" width="1" height="1" /></form>
                    <hr>
                    <a alt="Amazon Wishlist" href="http://www.amazon.de/registry/wishlist/2XHCAF5KVO61D/">' . __('My Amazon.de wishlist', 'psek') . '</a>
                  </div>
                  
                  <div style="border:1px solid #8F8F8F; width:180px; margin-left: 220px; padding:8px; ">
                  <h3>' . __('Contact', 'psek') . '</h3>
                  <a alt="Project page" href="https://wordpress.org/extend/plugins/piwik-search-engine-keywords/">' . __('Project page', 'psek') . '</a>
                      <br />
                    <a alt="Support page" href="https://wordpress.org/support/plugin/piwik-search-engine-keywords">' . __('Support', 'psek') . '</a>
                   </div>
                  
                    <div style="clear:both;"></div>
                   </div>
                  ';
        }

    }

}

// create a new instance of plugin class
if (class_exists('PiwikSearchEngineKeywords'))
{
    new PiwikSearchEngineKeywords();
}
?>