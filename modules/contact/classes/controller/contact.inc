<?php
/**
 *
 * @version $Id: contact.inc 4481 2017-03-24 18:21:48Z kent $
 * @package zesk
 * @subpackage controllers
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Controller_Contact extends Controller_Template_Login {
	function action_index() {
		return $this->action_list();
	}
	function block_edit_contact(Contact $contact) {
		$person = $contact->Person;
		$template = new Template('contact/edit.tpl', array(
			'contact' => $contact,
			'person' => $person
		) + $this->variables());
		$content = $template->output();
		if ($template->json || $this->request->get('ajax')) {
			$this->auto_render = false;
			$this->request->content_type = 'application/json';
			$this->request->response = $content;
			return;
		}
		$vars = array();
		$vars['title'] = $person->fullName();
		$vars['content'] = HTML::tag('div', '.contact-edit', $content);
		
		$this->template->set($vars + $this->variables());
	}
	function action_new() {
		$contact = new Contact();
		$contact->Person = new Contact_Person();
		$this->block_edit_contact($contact);
	}
	function action_edit($id) {
		$contact = new Contact($id);
		if (!$contact->fetch()) {
			$this->response->redirect('/contact', __("That contact has been deleted."));
		}
		if (!$this->user->can($contact, "edit")) {
			$this->response->redirect('/contact', __("You don't have permission to edit that contact."));
		}
		$this->block_edit_contact($contact);
	}
	function action_view($id) {
		$contact = new Contact($id);
		if (!$contact->fetch()) {
			$this->response->redirect('/contact', __("That contact has been deleted."));
		}
		if (!$this->user->can($contact, "view")) {
			$this->response->redirect('/contact', __("You don't have permission to view that contact."));
		}
		$person = $contact->memberObject('Person', 'Contact_Person');
		$content = Template::instance('contact/view.tpl', array(
			'object' => $contact,
			'person' => $person
		));
		if ($this->request->get('ajax')) {
			$this->auto_render = false;
			$this->request->response = $content;
			return;
		}
		$vars = array();
		// $vars['title'] = $person->fullName();
		$vars['content'] = HTML::tag('div', '.pblist', $content);
		
		$this->template->body = Template::instance('blocks/section-top.tpl', $vars + $this->variables());
	}
	function action_list() {
		$options = array();
		
		$options['empty_list_string'] = HTML::tag('div', '.padding', __("No contacts match your query.") . ' ' . HTML::a($this->request->path(), __("View all contacts")));
		$widget = new Control_Contact_List($options);
		$content = $widget->execute();
		
		$vars = array();
		$n_contacts = $this->application->query_select('Contact')
			->where('User', $this->user)
			->what("*n_contacts", 'COUNT(X.ID)')
			->integer('n_contacts');
		if ($n_contacts === 0) {
			$vars['title'] = __("Contacts");
			$vars['content'] = Template::instance('contact/no-contacts.tpl');
		} else {
			$template = new Template("menus/tags.tpl", $this->variables());
			$this->template->menu = $template->output();
			$title = $template->get("tag_title", __('All contacts'));
			
			$vars['title'] = $title;
			$vars['upper_right'] = HTML::a('/contact/new', __("Add a new contact"));
			$vars['content'] = HTML::tag('div', '.contact-list', $content);
		}
		
		$this->template->set($vars + $this->variables());
	}
	function _action_default($action = null) {
		return $this->action_list();
	}
}
