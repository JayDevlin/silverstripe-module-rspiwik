<?php
/**
 * Extend SiteTree to provide Piwik tracking
 */
class RSPiwikSiteTreeExtension extends SiteTreeDecorator {
	
	/**
	 * @param controller $controller
	 */
	public function contentControllerInit($controller) {
		$serverURL = $this->getPiwikServerURL();
		$siteID = $this->getPiwikSiteID();
		$trackingType = $this->getPiwikTrackingType();
		
		if( Director::isLive() && !empty($serverURL) && !empty($siteID) && !empty($trackingType) ) {
			if( $trackingType == "JavaScript" ) {
				Requirements::javascript(
					$serverURL."/piwik.js"
				);
				Requirements::customScript(
					$this->getJavascriptTracking($serverURL, $siteID)
				);
			}
			if( $trackingType == "Ajax" ) {
				Requirements::insertHeadTags(
					"<script type=\"text/javascript\">\n".
					$this->getAjaxTracking($serverURL, $siteID).
					"\n</script>"
				);
			}
			if( $trackingType == "Image" ) {
				Requirements::customScript(
					"//]]></script>".
					$this->getImageTracking($serverURL, $siteID).
					"<script type=\"text/javascript\">//<![CDATA["
				);
			}
			if( $trackingType == "API" ) {
				$this->getAPITracking($serverURL, $siteID);
			}
		}
	}
	
	/**
	 * @return string 
	 */
	protected function getPiwikServerURL() {
		$serverURL = $this->owner->SiteConfig->PiwikServerURL;
		
		if( !empty($serverURL) ) {
			// adjust protocol
			$serverURL = str_replace( array("http://", "https://"), array("", ""), $serverURL );
			$serverURL = Director::protocol().$serverURL;
			
			// remove trailing slash
			if( substr($serverURL, -1, 1)=="/" ) $serverURL = substr($serverURL, 0, -1);
			
			return $serverURL;
		} else {
			return null;
		}
	}
	
	/**
	 * @return int
	 */
	protected function getPiwikSiteID() {
		$siteID = (int)$this->owner->SiteConfig->PiwikSiteID;
		
		return $siteID;
	}
	
	/**
	 * @return string
	 */
	protected function getPiwikTrackingType() {
		$trackingType = $this->owner->SiteConfig->PiwikTrackingType;
		
		return $trackingType;
	}
	
	/**
	 * @param string $serverURL
	 * @param int $siteID
	 * @return string
	 */
	protected function getJavascriptTracking($serverURL, $siteID) {
		$piwikTracking = <<<JS
try {
var pkBaseURL = '{$serverURL}/';
var piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", {$siteID});
piwikTracker.trackPageView();
piwikTracker.enableLinkTracking();
} catch( err ) {}
JS;
		
		return $piwikTracking;
	}
	
	/**
	 * @param string $serverURL
	 * @param int $siteID
	 * @return string
	 */
	protected function getAjaxTracking($serverURL, $siteID) {
		$piwikTracking = <<<JS
var _paq = _paq || [];
(function(){
var u="{$serverURL}/";
_paq.push(['setSiteId', {$siteID}]);
_paq.push(['setTrackerUrl', u+'piwik.php']);
_paq.push(['trackPageView']);
_paq.push(['enableLinkTracking']);
var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0]; g.type='text/javascript'; g.defer=true; g.async=true; g.src=u+'piwik.js';
s.parentNode.insertBefore(g,s);
})();
JS;
		
		return $piwikTracking;
	}
	
	/**
	 * @param string $serverURL
	 * @param int $siteID
	 * @return string
	 */
	protected function getImageTracking($serverURL, $siteID) {
		$options = array();
		$options[] = "idsite={$siteID}";
		$options[] = "rec=1";
		$options[] = "action_name=".$this->getPageTitle();
		$options = implode("&", $options);
		$piwikTracking = Convert::raw2xml($serverURL."/piwik.php?".$options);
		
		$piwikTracking = <<<HTML
<img src="{$piwikTracking}" style="border:0" alt="" />
HTML;
		
		return $piwikTracking;
	}
	
	/**
	 * @param string $serverURL
	 * @param int $siteID
	 * @return PiwikTracker
	 */
	protected function getAPITracking($serverURL, $siteID) {
		$apiPath = dirname(__FILE__)."/PiwikTracker/PiwikTracker.php";
		
		if( file_exists($apiPath) && !class_exists('PiwikTracker') ) {
			require_once $apiPath;
		}
		
		if( class_exists('PiwikTracker') ) {
			// temporally remove url parameter from query string
			$queryString = $this->fixQueryString();
			
			// cast PiwikTracker
			PiwikTracker::$URL = $serverURL;
			$piwikTracker = new PiwikTracker($siteID);
			$piwikTracker->doTrackPageView( $this->getPageTitle() );
			
			// restore query string
			$_SERVER['QUERY_STRING'] = $queryString;

			return $piwikTracker;
		}
		
		return false;
	}
	
	/**
	 * @return string
	 */
	protected function getPageTitle() {
		$page = $this->owner;
		
		if( !empty($page) ) {
			// try some kind of wrapper method first if it exists
			if( $page->hasMethod("getMetaTagsTitle") ) {
				return $page->getMetaTagsTitle();
			}
			
			// otherwise return vanilla title
			// @see SiteTree->MetaTags()
			return Convert::raw2xml( !empty($page->MetaTitle) ? $page->MetaTitle : $page->Title );
		}
	}
	
	/**
	 * remove $_GET['url'] from $_SERVER['QUERY_STRING']
	 * @see PiwikTracker::getCurrentQueryString()
	 * @return string
	 */
	protected function fixQueryString() {
		$queryString = !empty($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
		
		if( !empty($_SERVER['QUERY_STRING']) && !empty($_GET['url']) ) {
			$_SERVER['QUERY_STRING'] = str_replace("url=".$_GET['url'], "", $_SERVER['QUERY_STRING']);
			if( !empty($_SERVER['QUERY_STRING']) && substr($_SERVER['QUERY_STRING'], 0, 1)=="&" ) {
				$_SERVER['QUERY_STRING'] = substr($_SERVER['QUERY_STRING'], 1);
			}
		}
		
		return $queryString;
	}
}