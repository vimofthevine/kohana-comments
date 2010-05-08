<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Comment controller
 *
 * @package     Comments
 * @author      Kyle Treubig
 * @copyright   (c) 2010 Kyle Treubig
 * @license     MIT
 */
class Controller_Comments_Core extends Controller {

	/**
	 * @var Supported formats
	 */
	protected $supported_formats = array(
		'.xhtml',
		'.json',
		'.xml',
		'.rss',
	);

	/**
	 * Perform format check
	 */
	public function before() {
		// Test to ensure the format requested is supported
		if ( ! in_array($this->request->param('format'), $this->supported_formats))
			throw new Kohana_Exception('File not found');

		$group = $this->request->param('group');
		$config = Kohana::config('comments.'.$group);
		$this->model    = $config['model'];
		$this->per_page = $config['per_page'];
		$this->view     = $config['view'];

		return parent::before();
	}

	/**
	 * Create new comment
	 */
	public function action_create() {
		Kohana::$log->add(Kohana::DEBUG, 'Executing Controller_Comments_Core::action_create');

		$id = $this->request->param('id', 0);

		if ($id == 0)
		{
			Kohana::$log->add(Kohana::INFO, 'Attempt to create comment without a defined parent');
			$this->request->response = FALSE;
			return;
		}

		$comment = Sprig::factory($this->model)->values($_POST);
		$comment->parent = $id;

		try
		{
			$comment->create();
			$this->request->response = TRUE;
		}
		catch (Validate_Exception $e)
		{
			// Setup HMVC view with data
			$form = View::factory($this->view.'/form')
				->set('legend', __('Post a Comment'))
				->set('submit', __('Create'))
				->set('comment', $comment)
				->set('errors', count($_POST) ? $e->array->errors('comments') : array() );

			// Set request response
			$this->request->response = $form;
		}
	}

	/**
	 * List comments
	 */
	protected function create_list($state = 'ham', $admin = FALSE) {
		Kohana::$log->add(Kohana::DEBUG, 'Executing Controller_Comments_Core::create_list');

		// Get parent id
		$parent_id = $this->request->param('id', 0);

		// Get total number of comments
		if ($parent_id == 0)
		{
			Kohana::$log->add(Kohana::DEBUG, 'Fetching all '.$state.' comments');
			$total = Sprig::factory($this->model, array(
				'state' => $state,
			))->load(NULL, FALSE)->count();
		}
		else
		{
			Kohana::$log->add(Kohana::DEBUG, 'Fetching '.$state.' comments for parent id='.$parent_id);
			$total = Sprig::factory($this->model, array(
				'state'  => $state,
				'parent' => $parent_id,
			))->load(NULL, FALSE)->count();
		}

		// Check if there are any comments to display
		if ($total == 0)
		{
			$this->request->response = FALSE;
			return;
		}

		// Determine pagination offset
		$page = $this->request->param('page', 1);
		$offset = ($page - 1) * $this->per_page;

		// Create query
		$query = DB::select()->offset($offset);

		// Create query
		if ($parent_id == 0)
		{
			$comments = Sprig::factory($this->model, array(
				'state' => $state,
			))->load($query, $this->per_page);
		}
		else
		{
			$comments = Sprig::factory($this->model, array(
				'state'  => $state,
				'parent' => $parent_id,
			))->load($query, $this->per_page);
		}

		// If no comments found (bad offset/page)
		if (count($comments) == 0)
		{
			Kohana::$log->add(Kohana::INFO, 'No comments found for state='.$state.', page='.$page);
			$this->request->response = FALSE;
			return;
		}

		$pagination = Pagination::factory(array(
			'current_page'   => array('source'=>'route', 'key'=>'page'),
			'total_items'    => $total,
			'items_per_page' => $this->per_page,
		));

		// Setup admin view
		$admin_view = View::factory($this->view.'/admin')
			->set('is_ham', ($state == 'ham'))
			->set('is_spam', ($state == 'spam'));

		// Setup view with data
		$list = View::factory($this->view.'/list')
			->set('legend', ucfirst($state).' Comments')
			->set('admin', $admin ? $admin_view : '')
			->set('pagination', $pagination)
			->set('comments', $comments);

		// Set request response
		$this->request->response = $list;
	}

	/**
	 * Retrieve public list of good comments
	 */
	public function action_public() {
		Kohana::$log->add(Kohana::DEBUG, 'Executing Controller_Comments_Core::action_public');
		$id = $this->request->param('id', 0);
		if ($id == 0)
		{
			Kohana::$log->add(Kohana::INFO, 'Attempt to load all public comments without a defined parent');
			$this->request->response = FALSE;
			return;
		}
		else
		{
			$this->create_list('ham', FALSE);
		}
	}

	/**
	 * Retrieve good comments
	 */
	public function action_ham() {
		Kohana::$log->add(Kohana::DEBUG, 'Executing Controller_Comments_Core::action_ham');
		$this->classify();
		$this->create_list('ham', TRUE);
	}

	/**
	 * Retrieve moderation queue
	 */
	public function action_queue() {
		Kohana::$log->add(Kohana::DEBUG, 'Executing Controller_Comments_Core::action_queue');
		$this->classify();
		$this->create_list('queued', TRUE);
	}

	/**
	 * Retrieve spam comments
	 */
	public function action_spam() {
		Kohana::$log->add(Kohana::DEBUG, 'Executing Controller_Comments_Core::action_spam');
		$this->classify();
		$this->create_list('spam', TRUE);
	}

	/**
	 * Perform classification changes
	 */
	protected function classify() {
		Kohana::$log->add(Kohana::DEBUG, 'Executing Controller_Comments_Core::classify');

		$id = 0;
		$comment = NULL;

		if (isset($_POST['classify_id']))
		{
			$id = $_POST['classify_id'];
			$comment = Sprig::factory($this->model, array('id' => $id))->load();

			// If comment is invalid
			if ( ! $comment->loaded())
			{
				$this->request->response = FALSE;
				return;
			}
		}

		if (isset($_POST['classify_option']) AND $_POST['classify_option'] != 'nop')
		{
			$option = $_POST['classify_option'];
			Kohana::$log->add(Kohana::DEBUG, 'Performing classification change, '.$option);

			$B8 = B8::factory();
			try {
				switch ($option)
				{
				case 'learn_ham':
					$probability_before = $B8->classify($comment->text);
					$B8->learn($comment->text, B8::HAM);
					$probability_after = $B8->classify($comment->text);
					Kohana::$log->add(Kohana::INFO, 'Comment learned as ham.  Probability before='.$probability_before.', after='.$probability_after);
					break;
				case 'learn_spam':
					$probability_before = $B8->classify($comment->text);
					$B8->learn($comment->text, B8::SPAM);
					$probability_after = $B8->classify($comment->text);
					Kohana::$log->add(Kohana::INFO, 'Comment learned as spam.  Probability before='.$probability_before.', after='.$probability_after);
					break;
				case 'unlearn_ham':
					$probability_before = $B8->classify($comment->text);
					$B8->unlearn($comment->text, B8::HAM);
					$probability_after = $B8->classify($comment->text);
					Kohana::$log->add(Kohana::INFO, 'Comment unlearned as ham.  Probability before='.$probability_before.', after='.$probability_after);
					break;
				case 'unlearn_spam':
					$probability_before = $B8->classify($comment->text);
					$B8->unlearn($comment->text, B8::SPAM);
					$probability_after = $B8->classify($comment->text);
					Kohana::$log->add(Kohana::INFO, 'Comment unlearned as spam.  Probability before='.$probability_before.', after='.$probability_after);
					break;
				}
			}
			catch (Exception $e)
			{
				Kohana::$log->add(Kohana::ERROR, 'Error occured classifying comment, '.$option);
				$this->request->response = FALSE;
				return;
			}
		}

		if (isset($_POST['classify_ham']))
		{
			Kohana::$log->add(Kohana::DEBUG, 'Approving comment, id='.$id);

			try
			{
				$comment->state = 'ham';
				$comment->update();
			}
			catch (Exception $e)
			{
				Kohana::$log->add(Kohana::ERROR, 'Error occured approving comment, id='.$id);
				$this->request->response = FALSE;
				return;
			}
		}

		if (isset($_POST['classify_spam']))
		{
			Kohana::$log->add(Kohana::DEBUG, 'Marking comment as spam, id='.$id);

			try
			{
				$comment->state = 'spam';
				$comment->update();
			}
			catch (Exception $e)
			{
				Kohana::$log->add(Kohana::ERROR, 'Error occured marking comment as spam, id='.$id);
				$this->request->response = FALSE;
				return;
			}
		}
	}

	/**
	 * Edit a comment
	 */
	public function action_update() {
		Kohana::$log->add(Kohana::DEBUG, 'Executing Controller_Comments_Core::action_update');

		$id = $this->request->param('id');
		$comment = Sprig::factory($this->model, array('id' => $id))->load();

		// If comment is invalid
		if ( ! $comment->loaded())
		{
			$this->request->response = FALSE;
			return;
		}

		$comment->values($_POST);

		// Setup view with data
		$form = View::factory($this->view.'/form')
			->set('legend', __('Modify Comment'))
			->set('submit', __('Save'))
			->set('comment', $comment);

		if (count($_POST))
		{
			try
			{
				$comment->update();
				$this->request->response = TRUE;
				return;
			}
			catch (Validate_Exception $e)
			{
				$form->errors = count($_POST) ? $e->array->errors('comments') : array();
			}
		}

		// Set request response
		$this->request->response = $form;
	}

	/**
	 * Delete a comment
	 */
	public function action_delete() {
		Kohana::$log->add(Kohana::DEBUG, 'Executing Controller_Comments_Core::action_create');

		// If deletion is not desired
		if (isset($_POST['no']))
		{
			$this->request->response = FALSE;
			return;
		}

		$id = $this->request->param('id');
		$comment = Sprig::factory($this->model, array('id' => $id))->load();

		// If comment is invalid
		if ( ! $comment->loaded())
		{
			$this->request->response = FALSE;
			return;
		}

		// If deletion is confirmed
		if (isset($_POST['yes']))
		{
			try
			{
				$comment->delete();
				$this->request->response = TRUE;
				return;
			}
			catch (Exception $e)
			{
				Kohana::$log->add(Kohana::ERROR, 'Error occured deleting comment, id='.$comment->id.', '.$e->getMessage());
				$this->request->response = FALSE;
				return;
			}
		}

		// Setup view with data
		$confirm = View::factory($this->view.'/delete')
			->set('comment', $comment);

		// Set request response
		$this->request->response = $confirm;
	}

	/**
	 * Generate comment report
	 */
	public function action_report() {
		Kohana::$log->add(Kohana::DEBUG, 'Executing Controller_Comments_Core::action_create');
	}

}	// End of Controller_Comments_Core

