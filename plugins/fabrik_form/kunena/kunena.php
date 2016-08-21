<?php
/**
 * Fabrik Form Kunena interface
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.kunena
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Form;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Fabrik\Helpers\Text;
use \KunenaForumMessage;
use \KunenaForumTopic;
use \CKunenaPost;
use \RuntimeException;
use \JFile;

/**
 * Creates a thread in kunena forum
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.kunena
 * @since       3.0
 */
class Kunena extends Form
{
	/**
	 * Run right at the end of the form processing
	 * form needs to be set to record in database for this to hook to be called
	 *
	 * @return	bool
	 */
	public function onAfterProcess()
	{
		$params = $this->getParams();
		$app = $this->app;
		$formModel = $this->getModel();
		$input = $app->input;
		jimport('joomla.filesystem.file');
		$define = COM_FABRIK_BASE . 'libraries/kunena/bootstrap.php';

		if (JFile::exists($define))
		{
			// Kunenea 3.x
			require_once $define;
			$this->post3x();
		}
		else
		{
			$define = COM_FABRIK_BASE . 'components/com_kunena/lib/kunena.defines.php';

			if (JFile::exists($define))
			{
				require_once $define;
				$this->post2x();
			}
			else
			{
				throw new RuntimeException('could not find the Kunena component', 404);
			}
		}
	}

	/**
	 * Post to Kunena 2.x
	 *
	 * @return  void
	 */
	protected function post2x()
	{
		$params = $this->getParams();
		$app = $this->app;
		$formModel = $this->getModel();
		$input = $app->input;
		$w = new Worker;

		$catId = $params->get('kunena_category', 0);

		$files[] = COM_FABRIK_BASE . 'components/com_kunena/class.kunena.php';
		$files[] = COM_FABRIK_BASE . 'components/com_kunena/lib/kunena.defines.php';
		$files[] = COM_FABRIK_BASE . 'components/com_kunena/lib/kunena.link.class.php';
		$files[] = COM_FABRIK_BASE . 'components/com_kunena/lib/kunena.smile.class.php';

		foreach ($files as $file)
		{
			require_once $file;
		}

		if (JFile::exists(KUNENA_PATH_FUNCS . '/post.php'))
		{
			$postfile = KUNENA_PATH_FUNCS . '/post.php';
		}
		else
		{
			$postfile = KUNENA_PATH_TEMPLATE_DEFAULT . '/post.php';
		}

		$action = 'post';

		// Added action in request
		$input->set('action', $action);
		$input->set('catid', $catId);
		$msg = $w->parseMessageForPlaceHolder($params->get('kunena_content'), $formModel->fullFormData);
		$subject = $params->get('kunena_title');
		$input->set('message', $msg);
		$subject = $w->parseMessageForPlaceHolder($subject, $formModel->fullFormData);

		// Added subject in request
		$input->set('subject', $subject);
		$origId = $input->get('id');
		$input->set('id', 0);

		ob_start();
		include $postfile;
		$topic = new CKunenaPost;

		// Public CKunenaPost::display() will call protected method CKunenaPost::post() if $app->input action is 'post'
		$topic->display();
		ob_end_clean();
		$input->set('id', $origId);
	}

	/**
	 * Post to Kunena 3.x
	 *
	 * @return  void
	 */

	protected function post3x()
	{
		// Load front end language file as well
		$lang = $this->lang;
		$lang->load('com_kunena', JPATH_SITE . '/components/com_kunena');

		$params = $this->getParams();
		$app = $this->app;
		$formModel = $this->getModel();
		$input = $app->input;

		$user = $this->user;
		$now = $this->date;
		$w = new Worker;

		$catId = $params->get('kunena_category', 0);

		// Added action in request
		$msg = $w->parseMessageForPlaceHolder($params->get('kunena_content'), $formModel->fullFormData);
		$subject = $params->get('kunena_title');
		$subject = $w->parseMessageForPlaceHolder($subject, $formModel->fullFormData);

		// Added subject in request
		$origId = $input->get('id');
		$input->set('id', 0);

		$topic = new KunenaForumTopic;
		$topic->category_id = $catId;
		$topic->subject = $subject;
		$topic->first_post_time = $topic->last_post_time = $now->toUnix();
		$topic->first_post_userid = $topic->last_post_userid = $user->get('id');
		$topic->first_post_message = $topic->last_post_message = $msg;
		$topic->posts = 1;

		if ($topic->save())
		{
			$message = new KunenaForumMessage;
			$message->setTopic($topic);

			$message->subject = $subject;
			$message->catid = $catId;
			$message->name = $user->get('name');
			$message->time = $now->toUnix();
			$message->message = $msg;

			if (!$message->save())
			{
				$app->enqueueMessage(Text::_('PLG_FORM_KUNENA_ERR_DIDNT_SAVE_MESSAGE') . ': ' . $message->getError(), 'error');
			}
		}
		else
		{
			$app->enqueueMessage(Text::_('PLG_FORM_KUNENA_ERR_DIDNT_SAVE_TOPIC') . ': ' . $topic->getError(), 'error');
		}

		$input->set('id', $origId);
	}
}
