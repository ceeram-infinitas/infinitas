<?php
	/**
	 * CategoriesController for the management and display of categories and
	 * related data.
	 *
	 * @filesource
	 * @copyright Copyright (c) 2009 Carl Sutton ( dogmatic69 )
	 * @link http://infinitas-cms.org
	 * @package Infinitas.Contents.controllers
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @since 0.7a
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 */

	class GlobalCategoriesController extends ContentsAppController {
		public $name = 'GlobalCategories';

		public function index() {
			if(isset($this->params['category'])){
				$this->paginate['GlobalCategory']['conditions']['GlobalCategory.slug'] = $this->params['category'];
			}

			$categories = $this->paginate();
			// redirect if there is only one category.
			if (count($categories) == 1 && Configure::read('Cms.auto_redirect')) {
				$this->redirect(
					array(
						'controller' => 'categories',
						'action' => 'view',
						$categories[0]['GlobalCategory']['id']
					)
				);
			}

			$this->set('categories', $categories);
		}

		public function view() {
			$conditions = array();
			if(isset($this->params['category'])){
				$conditions['GlobalCategory.slug'] = $this->params['category'];
			}

			$category = $this->GlobalCategory->find(
				'first',
				array(
					'conditions' => $conditions
				)
			);

			// redirect if there is only one content item.
			if ((isset($category['Content']) && count($category['Content']) == 1) && Configure::read('Cms.auto_redirect')) {
				$this->redirect(
					array(
						'controller' => 'contents',
						'action' => 'view',
						$category['Content'][0]['id']
					)
				);
			}

			else if(empty($category)){
				$this->Session->setFlash(__('Invalid category', true));
				$this->redirect($this->referer());
			}

			$this->set('title_for_layout', $category['GlobalCategory']['title']);
			$this->set('category', $category);
		}

		public function admin_index() {
			$categories = $this->paginate(null, $this->Filter->filter);

			$filterOptions = $this->Filter->filterOptions;
			$filterOptions['fields'] = array(
				'title',
				'parent_id' => array(null => __('All', true), 0 => __('Top Level Categories', true)) + $this->GlobalCategory->generatetreelist(),
				'group_id' => array(null => __('Public', true)) + $this->GlobalCategory->Group->find('list'),
				'active' => (array)Configure::read('CORE.active_options')
			);

			$this->set(compact('filterOptions', 'categories'));
		}

		public function admin_view($id = null) {
			if (!$id) {
				$this->Infinitas->noticeInvalidRecord();
			}
			$this->set('category', $this->GlobalCategory->read(null, $id));
		}

		public function admin_add() {
			parent::admin_add();
		}

		public function admin_edit($id = null) {
			unset($this->data['GlobalContent']['global_category_id']);
			
			parent::admin_edit($id);
		}

		public function admin_delete($id = null) {
			if (!$id) {
				$this->Infinitas->noticeInvalidRecord();
			}

			$count = $this->GlobalCategory->find('count', array('conditions' => array('Category.parent_id' => $id)));
			if ($count > 0) {
				$this->notice(
					sprintf(__('That %s has sub-categories', true), $this->prettyModelName),
					array(
						'level' => 'warning',
						'redirect' => true
					)
				);
			}

			$category = $this->GlobalCategory->read(null, $id);

			if (!empty($category['Content'])) {
				$this->notice(
					sprintf(__('That %s has content items, remove them before continuing', true), $this->prettyModelName),
					array(
						'level' => 'warning',
						'redirect' => true
					)
				);
			}

			return parent::admin_delete($id);
		}
	}