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
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Rapsys\UserBundle\Utils\Slugger;

class DefaultController extends AbstractController {
	//Config array
	protected $config;

	//Context array
	protected $context;

	//Router instance
	protected $router;

	//Slugger instance
	protected $slugger;

	//Translator instance
	protected $translator;

	/**
	 * Inject container and translator interface
	 *
	 * @param ContainerInterface $container The container instance
	 * @param RouterInterface $router The router instance
	 * @param Slugger $slugger The slugger instance
	 * @param TranslatorInterface $translator The translator instance
	 */
	public function __construct(ContainerInterface $container, RouterInterface $router, Slugger $slugger, TranslatorInterface $translator) {
		//Retrieve config
		$this->config = $container->getParameter($this->getAlias());

		//Set the router
		$this->router = $router;

		//Set the slugger
		$this->slugger = $slugger;

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
			'site_url' => $router->generate($this->config['site']['url'])
		];
	}

	/**
	 * The contact page
	 *
	 * @desc Send a contact mail to configured contact
	 *
	 * @param Request $request The request instance
	 * @param MailerInterface $mailer The mailer instance
	 *
	 * @return Response The rendered view or redirection
	 */
	public function contact(Request $request, MailerInterface $mailer) {
		//Set section
		$section = $this->translator->trans('Contact');

		//Set title
		$title = $section.' - '.$this->translator->trans($this->config['site']['title']);

		//Create the form according to the FormType created previously.
		//And give the proper parameters
		$form = $this->createForm('Rapsys\AirBundle\Form\ContactType', null, [
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
					->from(new Address($data['mail'], $data['name']))
					//Set recipient
					//XXX: remove the debug set in vendor/symfony/mime/Address.php +46
					->to(new Address($this->config['contact']['mail'], $this->config['contact']['name']))
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
	 *
	 * @desc Welcome the user
	 *
	 * @return Response The rendered view
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
	 * The regulation page
	 *
	 * @desc Display the regulation policy
	 *
	 * @return Response The rendered view
	 */
	public function regulation() {
		//Set section
		$section = $this->translator->trans('Regulation');

		//Set title
		$title = $section.' - '.$this->context['site_title'];

		//Render template
		return $this->render('@RapsysAir/default/regulation.html.twig', ['title' => $title, 'section' => $section]+$this->context);
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
