<?php

/**
 * This is the edit-action, it will display a form with the item data to edit
 *
 * @package		backend
 * @subpackage	subname
 *
 * @author		authorname
 * @since		versionname
 */
class Backendmodulenameactionname extends BackendBaseActionEdit
{
	/**
	 * Execute the action
	 */
	public function execute()
	{
		// call parent, this will probably add some general CSS/JS or other required files
		parent::execute();

		// load the data
		$this->loadData();

		// load the form
		$this->loadForm();

		// validate the form
		$this->validateForm();

		// parse
		$this->parse();

		// display the page
		$this->display();
	}

	/**
	 * Loads the data
	 */
	private function loadData()
	{
		// get the id
		$this->id = $this->getParameter('id', 'int');

		// item does not exist
		if(!BackendmodulenameModel::exists($this->id)) $this->redirect(BackendModel::createURLForAction('index') . '&error=non-existing');

		// the data
		$this->record = BackendmodulenameModel::get($this->id);
	}

	/**
	 * Load the form
	 */
	private function loadForm()
	{
		// create form
		$this->frm = new BackendForm('edit');

		// set hidden values
		$rbtVisibleValues[] = array('label' => BL::lbl('Hidden'), 'value' => 'N');
		$rbtVisibleValues[] = array('label' => BL::lbl('Published'), 'value' => 'Y');

		// create elements
		$this->frm->addText('title', $this->record['title'], null, 'inputText title', 'inputTextError title');
		$this->frm->addRadiobutton('visible', $rbtVisibleValues, $this->record['visible']);

		// meta
		$this->meta = new BackendMeta($this->frm, $this->record['meta_id'], 'title', true);
	}

	/**
	 * Parse the form
	 */
	protected function parse()
	{
		// call parent
		parent::parse();

		// parse the form
		$this->frm->parse($this->tpl);

		// assign the item
		$this->tpl->assign('item', $this->record);

		// get url
		$url = BackendModel::getURLForBlock($this->URL->getModule(), 'detail');
		$url404 = BackendModel::getURL(404);

		// parse additional variables
		if($url404 != $url) $this->tpl->assign('detailURL', SITE_URL . $url);
	}

	/**
	 * Validate the form
	 */
	private function validateForm()
	{
		// is the form submitted?
		if($this->frm->isSubmitted())
		{
			// set callback for generating an unique URL
			$this->meta->setUrlCallback('BackendmodulenameModel', 'getURL', array($this->record['id']));

			// cleanup the submitted fields, ignore fields that were added by hackers
			$this->frm->cleanupFields();

			// validation
			$this->frm->getField('title')->isFilled(BL::err('FieldIsRequired'));

			// validate meta
			$this->meta->validate();

			// no errors?
			if($this->frm->isCorrect())
			{
				// get the values
				$item['title'] = $this->frm->getField('title')->getValue();
				$item['meta_id'] = $this->meta->save(true);
				$item['language'] = BL::getWorkingLanguage();
				$item['visible'] = $this->frm->getField('visible')->getValue();

				// update
				BackendmodulenameModel::update($item, $this->id);
				$item['id'] = $this->id;

				// trigger event
				BackendModel::triggerEvent($this->getModule(), 'after_edit', $item);

				// everything is saved, so redirect to the index
				$this->redirect(BackendModel::createURLForAction('index') . '&report=edited&highlight=row-' . $item['id']);
			}
		}
	}
}
