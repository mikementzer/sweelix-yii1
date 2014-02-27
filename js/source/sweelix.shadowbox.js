/**
 * File jquery.sweelix.shadowbox.js
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   2.0.0
 * @link      http://www.sweelix.net
 * @category  js
 * @package   sweelix.yii1.web.js
 */
if(typeof(sweelix) != 'object') {
	throw "Sweelix is missing";
}
(function($s){
	function SbWrapper(sweelix){
		this.version = "1.0";
		var config = sweelix.config('shadowbox');
		sweelix.register('shadowboxOpen', function(params) {
			if(typeof(Shadowbox) == 'undefined') {
				setTimeout(function(){ sweelix.raise('shadowBox', params); }, 250);
			} else {
				Shadowbox.open(params);
			}
		});
		sweelix.register('shadowboxClose',function() {
			Shadowbox.close();
		});
	};
	var sb = new SbWrapper($s);
	$s.info('sweelix.shadowbox : register module version ('+sb.version+')');

})(sweelix)

