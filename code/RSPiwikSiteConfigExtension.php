<?php
/**
 * Extend SiteConfig for data fields
 */
class RSPiwikSiteConfigExtension extends DataExtension {
	public static $db = array(
		'PiwikServerURL' => 'Text',
		'PiwikSiteID' => 'Int',
		'PiwikTrackingType' => "Enum('Disabled,JavaScript,Ajax,Image,API','JavaScript')",
	);
	
	/**
	 * @param FieldSet $fieldSet 
	 */
	function updateCMSFields(FieldList $fields) {
		$fieldList = new FieldList(
			new TextField('PiwikServerURL', _t('RSPiwik.PiwikServerURL', 'Piwik Server URL')),
			new NumericField('PiwikSiteID', _t('RSPiwik.PiwikSiteID', 'Piwik Site ID')),
			$PiwikTrackingType = new DropdownField('PiwikTrackingType', _t('RSPiwik.PiwikTrackingType', 'Piwik Tracking Type'))
		);

		$PiwikTrackingType->setSource(array(
			'Disabled' => _t('RSPiwik.Disabled','Disabled'),
			'JavaScript' => _t('RSPiwik.JavaScriptTracking','JavaScript Tracking')." ("._t('RSPiwik.Default','Default').")",
			'Ajax' => _t('RSPiwik.AjaxTracking','Ajax Tracking'),
			'Image' => _t('RSPiwik.ImageTracking','Image Tracking'),
			'API' => _t('RSPiwik.APITracking','API Tracking'),
		));

		$fields->addFieldsToTab('Root.Piwik', $fieldList);
	}
}