/**
 * Facebook Likebox Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbLikebox = new Class({
	Extends: FbElement,
	initialize: function (element, options) {
		this.plugin = 'fbLikebox';
		this.parent(element, options);
	}
});