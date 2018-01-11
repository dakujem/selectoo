<?php


namespace Dakujem\Selectoo\Examples;

use Nette\Application\Responses\JsonResponse,
	Nette\Application\UI\Form,
	Tracy\Debugger;


/**
 * Example Presenter class.
 *
 * This Presenter exists purely for example purposes.
 *
 *
 * @author Andrej Rypák <xrypak@gmail.com> [dakujem](https://github.com/dakujem)
 */
class Dkj_ExamplePresenter extends Presenter
{
	/** @var Dkj_UserAjaxSelectooFactory */
	private $selectooFactory;

	/** @var Dkj_UserRepository	 */
	private $repo;


	public function injectServices(Dkj_UserRepository $repo, Dkj_UserAjaxSelectooFactory $factory)
	{
		$this->repo = $repo;
		$this->selectooFactory = $factory;
		return $this;
	}


	public function actionDefault()
	{
		// print out the form
	}


	/**
	 *
	 * https://select2.org/data-sources/formats
	 * 
	 *
	 * @param type $q
	 */
	public function actionApi($q = null)
	{
		$users = $this->repo->queryUsers($q);

		$res = ['results' => $users];
		$this->sendResponse(new JsonResponse($res));
		$this->terminate();
	}


	protected function createComponentForm()
	{
		$form = new Form();

		$form->addText('foo', 'Foo');
		$form->addText('bar', 'Bar');

		$selectoo = $this->selectooFactory->create('Selectoo!');

		$form->addComponent($selectoo, 'selectoo1');


		$form->addSubmit('go', 'Hello world!');

		$form->onSubmit[] = function($form) {
			Debugger::barDump($form->getValues(), 'form values');
		};

		return $form;
	}

}
