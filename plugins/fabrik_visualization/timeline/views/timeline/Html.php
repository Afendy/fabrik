<?php
/**
 * Fabrik Timeline Viz HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.timeline
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Visualization\Timeline\Views;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html as HtmlHelper;
use Fabrik\Helpers\Text;
use \JFactory;
use \JHtml;
use \JComponentHelper;
use \JViewLegacy;

jimport('joomla.application.component.view');

/**
 * Fabrik Timeline Viz HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.timeline
 * @since       3.0
 */
class Html extends JViewLegacy
{
	/**
	 * @var string
	 */
	public $datePicker = '';

	/**
	 * Execute and display a template script.
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 */

	public function display($tpl = 'default')
	{
		$app   = JFactory::getApplication();
		$input = $app->input;
		$srcs  = HtmlHelper::framework();

		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model       = $this->getModel();
		$id          = $input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0)));
		$model->setId($id);
		$row = $model->getVisualization();

		if (!$model->canView())
		{
			echo Text::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		$js                   = $model->render();
		$this->containerId    = $this->get('ContainerId');
		$this->row            = $row;
		$this->showFilters    = $input->getInt('showfilters', 1) === 1 ? 1 : 0;
		$this->filters        = $model->getFilters();
		$this->advancedSearch = $model->getAdvancedSearchLink();
		$this->filterFormURL  = $model->getFilterFormURL();
		$params               = $model->getParams();
		$this->params         = $params;
		$this->width          = $params->get('timeline_width', '700');
		$this->height         = $params->get('timeline_height', '300');
		$tpl                  = $params->get('timeline_layout', 'bootstrap');
		$tmplPath             = '/plugins/fabrik_visualization/Timeline/Views/Timeline/tmpl/' . $tpl;
		$this->_setPath('template', JPATH_ROOT . $tmplPath);

		JHtml::stylesheet('media/com_fabrik/css/list.css');

		HtmlHelper::stylesheetFromPath($tmplPath . '/template.css');
		$srcs['FbListFilter']   = 'media/com_fabrik/js/listfilter.js';
		$srcs['Timeline']       = 'plugins/fabrik_visualization/timeline/timeline.js';
		$srcs['AdvancedSearch'] = 'media/com_fabrik/js/advanced-search.js';

		$js .= $model->getFilterJs();
		HtmlHelper::iniRequireJs($model->getShim());
		HtmlHelper::script($srcs, $js);

		Text::script('COM_FABRIK_ADVANCED_SEARCH');
		Text::script('COM_FABRIK_LOADING');
		$opts             = array('alt' => 'calendar', 'class' => 'calendarbutton', 'id' => 'timelineDatePicker_cal_img');
		$img              = HtmlHelper::image('calendar.png', 'form', $this->tmpl, $opts);
		$this->datePicker = '<input type="text" name="timelineDatePicker" id="timelineDatePicker" value="" />' . $img;

		// Check and add a general fabrik custom css file overrides template css and generic table css
		HtmlHelper::stylesheetFromPath('media/com_fabrik/css/custom.css');

		// Check and add a specific biz  template css file overrides template css generic table css and generic custom css
		HtmlHelper::stylesheetFromPath('plugins/fabrik_visualization/Timeline/Views/Timeline/tmpl/' . $tpl . '/custom.css');

		return parent::display();
	}
}
