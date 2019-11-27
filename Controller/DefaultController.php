<?php

namespace Rapsys\AirBundle\Controller;

use Rapsys\AirBundle\Entity\Application;
use Rapsys\AirBundle\Entity\Session;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\NamedAddress;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DefaultController extends AbstractController {
	//Config array
	protected $config;

	//Context array
	protected $context;

	//Translator instance
	protected $translator;

	/**
	 * Inject container and translator interface
	 */
	public function __construct(ContainerInterface $container, TranslatorInterface $translator, RouterInterface $router) {
		//Retrieve config
		$this->config = $container->getParameter($this->getAlias());

		//Set the translator
		$this->translator = $translator;

		//Set the context
		$this->context = [
			'copy_long' => $translator->trans($this->config['copy']['long']),
			'copy_short' => $translator->trans($this->config['copy']['short']),
			'site_ico' => $this->config['site']['ico'],
			'site_logo' => $this->config['site']['logo'],
			'site_png' => $this->config['site']['png'],
			'site_svg' => $this->config['site']['svg'],
			'site_title' => $translator->trans($this->config['site']['title']),
			'site_url' => $router->generate('rapsys_air_index')
		];
	}

	/**
	 * The contact page
	 */
	public function contact(Request $request, MailerInterface $mailer) {
		//Set section
		$section = $this->translator->trans('Contact');

		//Set title
		$title = $section.' - '.$this->translator->trans($this->config['site']['title']);

		//Create the form according to the FormType created previously.
		//And give the proper parameters
		$form = $this->createForm('Rapsys\AirBundle\Form\ContactType', null, [
			// To set the action use $this->generateUrl('route_identifier')
			'action' => $this->generateUrl('rapsys_air_contact'),
			'method' => 'POST'
		]);

		if ($request->isMethod('POST')) {
			// Refill the fields in case the form is not valid.
			$form->handleRequest($request);

			if ($form->isValid()) {
				//Get data
				$data = $form->getData();

				//Create message
				$message = (new TemplatedEmail())
					//Set sender
					->from(new NamedAddress($data['mail'], $data['name']))
					//Set recipient
					//XXX: remove the debug set in vendor/symfony/mime/Address.php +46
					->to(new NamedAddress($this->config['contact']['mail'], $this->config['contact']['name']))
					//Set subject
					->subject($data['subject'])

					//Set path to twig templates
					->htmlTemplate('@RapsysAir/mail/contact.html.twig')
					->textTemplate('@RapsysAir/mail/contact.text.twig')

					//Set context
					->context(
						[
							'subject' => $data['subject'],
							'message' => strip_tags($data['message']),
						]+$this->context
					);

				//Try sending message
				//XXX: mail delivery may silently fail
				try {
					//Send message
					$mailer->send($message);

					//Redirect on the same route with sent=1 to cleanup form
					return $this->redirectToRoute($request->get('_route'), ['sent' => 1]+$request->get('_route_params'));
				//Catch obvious transport exception
				} catch(TransportExceptionInterface $e) {
					if ($message = $e->getMessage()) {
						//Add error message mail unreachable
						$form->get('mail')->addError(new FormError($this->translator->trans('Unable to contact: %mail%: %message%', ['%mail%' => $this->config['contact']['mail'], '%message%' => $this->translator->trans($message)])));
					} else {
						//Add error message mail unreachable
						$form->get('mail')->addError(new FormError($this->translator->trans('Unable to contact: %mail%', ['%mail%' => $this->config['contact']['mail']])));
					}
				}
			}
		}

		//Render template
		return $this->render('@RapsysAir/form/contact.html.twig', ['title' => $title, 'section' => $section, 'form' => $form->createView(), 'sent' => $request->query->get('sent', 0)]+$this->context);
	}

	/**
	 * The index page
	 */
	public function index() {
		//Set section
		$section = $this->translator->trans('Index');

		//Set title
		$title = $section.' - '.$this->context['site_title'];

		//Render template
		return $this->render('@RapsysAir/default/index.html.twig', ['title' => $title, 'section' => $section]+$this->context);
	}

	/**
	 * The policy page
	 */
	public function policy() {
		//Set section
		$section = $this->translator->trans('Policy');

		//Set title
		$title = $section.' - '.$this->context['site_title'];

		//Render template
		return $this->render('@RapsysAir/default/policy.html.twig', ['title' => $title, 'section' => $section]+$this->context);
	}

	/**
	 * Return the bundle alias
	 *
	 * {@inheritdoc}
	 */
	public function getAlias() {
		return 'rapsys_air';
	}
}
